<?php

namespace App\Domain\Logistics\Services;

use App\Domain\Logistics\Contracts\ReturnServiceInterface;
use App\Domain\Logistics\DTOs\ReturnRequestData;
use App\Domain\Logistics\Models\Shipment;
use App\Domain\Logistics\Models\ShipmentReturn;
use App\Domain\Logistics\Enums\ReturnReason;
use App\Domain\Logistics\Events\ReturnRequested;
use App\Domain\Logistics\Events\ReturnApproved;
use App\Domain\Logistics\Events\ReturnRejected;
use App\Domain\Logistics\Exceptions\ShipmentException;
use App\Domain\Auth\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReturnService implements ReturnServiceInterface
{
    public function requestReturn(ReturnRequestData $data): ShipmentReturn
    {
        return DB::transaction(function () use ($data) {
            $shipment = Shipment::findOrFail($data->shipmentId);

            if (!$shipment->canBeReturned()) {
                throw new ShipmentException("Shipment cannot be returned in current status: {$shipment->status->label()}");
            }

            // Check if return already exists
            $existingReturn = ShipmentReturn::where('shipment_id', $data->shipmentId)
                ->whereIn('status', ['requested', 'approved'])
                ->first();

            if ($existingReturn) {
                throw new ShipmentException("Return request already exists for this shipment");
            }

            $return = ShipmentReturn::create([
                'company_id' => $shipment->company_id,
                'shipment_id' => $data->shipmentId,
                'reason' => $data->reason,
                'reason_details' => $data->reasonDetails,
                'status' => 'requested',
                'images' => $data->images,
                'requested_by' => $data->requestedBy,
                'requested_at' => now(),
            ]);

            // Auto-approve if reason allows it
            if ($data->reason->autoApprove()) {
                $this->approveReturn($return->id, $data->requestedBy);
            }

            // Fire event
            event(new ReturnRequested($return));

            return $return->load(['shipment', 'requestedBy']);
        });
    }

    public function approveReturn(int $returnId, int $approvedBy): ShipmentReturn
    {
        return DB::transaction(function () use ($returnId, $approvedBy) {
            $return = ShipmentReturn::findOrFail($returnId);

            if ($return->status !== 'requested') {
                throw new ShipmentException("Return request is not in requested status");
            }

            $return->update([
                'status' => 'approved',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);

            // Create return shipment if needed
            $this->createReturnShipment($return);

            // Fire event
            event(new ReturnApproved($return));

            return $return->fresh(['shipment', 'approvedBy']);
        });
    }

    public function rejectReturn(int $returnId, int $rejectedBy, string $reason = null): ShipmentReturn
    {
        $return = ShipmentReturn::findOrFail($returnId);

        if ($return->status !== 'requested') {
            throw new ShipmentException("Return request is not in requested status");
        }

        $return->update([
            'status' => 'rejected',
            'approved_by' => $rejectedBy,
            'approved_at' => now(),
            'reason_details' => $return->reason_details . ($reason ? "\n\nRejection reason: {$reason}" : ''),
        ]);

        // Fire event
        event(new ReturnRejected($return, $reason));

        return $return->fresh(['shipment', 'approvedBy']);
    }

    public function processRefund(int $returnId, float $amount, string $method): ShipmentReturn
    {
        $return = ShipmentReturn::findOrFail($returnId);

        if (!in_array($return->status, ['approved', 'received'])) {
            throw new ShipmentException("Return must be approved or received before processing refund");
        }

        $return->update([
            'status' => 'refunded',
            'refund_amount' => $amount,
            'refund_method' => $method,
            'refunded_at' => now(),
        ]);

        return $return->fresh();
    }

    public function markAsReceived(int $returnId): ShipmentReturn
    {
        $return = ShipmentReturn::findOrFail($returnId);

        if ($return->status !== 'approved') {
            throw new ShipmentException("Return must be approved before marking as received");
        }

        $return->update([
            'status' => 'received',
        ]);

        return $return->fresh();
    }

    public function getReturn(int $returnId): ShipmentReturn
    {
        return ShipmentReturn::with(['shipment', 'requestedBy', 'approvedBy', 'returnCarrier'])
            ->findOrFail($returnId);
    }

    public function listReturns(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = ShipmentReturn::with(['shipment', 'requestedBy'])
            ->forCompany($filters['company_id'] ?? auth()->user()->currentCompany->id);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['reason'])) {
            $query->where('reason', ReturnReason::from($filters['reason']));
        }

        if (isset($filters['shipment_id'])) {
            $query->where('shipment_id', $filters['shipment_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('requested_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('requested_at', '<=', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('return_number', 'like', "%{$search}%")
                  ->orWhereHas('shipment', function ($sq) use ($search) {
                      $sq->where('tracking_number', 'like', "%{$search}%");
                  });
            });
        }

        return $query->orderBy('requested_at', 'desc')->paginate($perPage);
    }

    public function uploadReturnImages(int $returnId, array $images): array
    {
        $return = ShipmentReturn::findOrFail($returnId);
        $uploadedImages = [];

        foreach ($images as $image) {
            $path = Storage::disk('public')->put('returns/' . $return->id, $image);
            $uploadedImages[] = Storage::disk('public')->url($path);
        }

        // Update return with new images
        $existingImages = $return->images ?? [];
        $allImages = array_merge($existingImages, $uploadedImages);
        
        $return->update(['images' => $allImages]);

        return $uploadedImages;
    }

    public function getReturnStatistics(array $filters = []): array
    {
        $companyId = $filters['company_id'] ?? auth()->user()->currentCompany->id;
        
        $query = ShipmentReturn::forCompany($companyId);

        if (isset($filters['date_from'])) {
            $query->whereDate('requested_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('requested_at', '<=', $filters['date_to']);
        }

        $totalReturns = $query->count();
        $approvedReturns = $query->clone()->where('status', 'approved')->count();
        $rejectedReturns = $query->clone()->where('status', 'rejected')->count();
        $completedReturns = $query->clone()->whereIn('status', ['received', 'refunded'])->count();

        // Return reasons breakdown
        $reasonBreakdown = $query->clone()
            ->selectRaw('reason, COUNT(*) as count')
            ->groupBy('reason')
            ->pluck('count', 'reason')
            ->toArray();

        // Average processing time
        $avgProcessingTime = $query->clone()
            ->whereNotNull('approved_at')
            ->get()
            ->avg(function ($return) {
                return $return->requested_at->diffInHours($return->approved_at);
            });

        return [
            'total_returns' => $totalReturns,
            'approved_returns' => $approvedReturns,
            'rejected_returns' => $rejectedReturns,
            'completed_returns' => $completedReturns,
            'approval_rate' => $totalReturns > 0 ? round(($approvedReturns / $totalReturns) * 100, 2) : 0,
            'completion_rate' => $approvedReturns > 0 ? round(($completedReturns / $approvedReturns) * 100, 2) : 0,
            'avg_processing_hours' => round($avgProcessingTime ?? 0, 1),
            'reason_breakdown' => $reasonBreakdown,
        ];
    }

    protected function createReturnShipment(ShipmentReturn $return): void
    {
        // This would create a return shipment with a carrier
        // For now, we'll just set a return tracking number
        $return->update([
            'return_tracking_number' => 'RET-' . strtoupper(\Str::random(8)),
        ]);
    }
}