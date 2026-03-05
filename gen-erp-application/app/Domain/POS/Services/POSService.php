<?php

namespace App\Domain\POS\Services;

use App\Domain\POS\Actions\CreatePOSSaleAction;
use App\Domain\POS\Actions\OpenPOSSessionAction;
use App\Domain\POS\Actions\ClosePOSSessionAction;
use App\Domain\POS\Actions\VoidPOSSaleAction;
use App\Domain\POS\Contracts\POSServiceInterface;
use App\Domain\POS\DTOs\CreatePOSSaleData;
use App\Domain\POS\DTOs\OpenSessionData;
use App\Domain\POS\DTOs\CloseSessionData;
use App\Domain\POS\Models\POSSale;
use App\Domain\POS\Models\POSSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class POSService implements POSServiceInterface
{
    public function __construct(
        private readonly OpenPOSSessionAction $openSessionAction,
        private readonly ClosePOSSessionAction $closeSessionAction,
        private readonly CreatePOSSaleAction $createSaleAction,
        private readonly VoidPOSSaleAction $voidSaleAction,
    ) {}

    public function openSession(OpenSessionData $data): POSSession
    {
        return $this->openSessionAction->execute($data);
    }

    public function closeSession(CloseSessionData $data): POSSession
    {
        return $this->closeSessionAction->execute($data);
    }

    public function createSale(CreatePOSSaleData $data): POSSale
    {
        return $this->createSaleAction->execute($data);
    }

    public function voidSale(POSSale $sale): POSSale
    {
        return $this->voidSaleAction->execute($sale);
    }

    public function getSessionSummary(POSSession $session): array
    {
        $completed = POSSale::where('pos_session_id', $session->id)
            ->where('status', 'completed');

        $totalSales = (int) (clone $completed)->sum('total_amount');
        $saleCount = (clone $completed)->count();

        $voidedCount = POSSale::where('pos_session_id', $session->id)
            ->where('status', 'voided')
            ->count();

        // Get payment breakdown
        $paymentBreakdown = POSSale::where('pos_session_id', $session->id)
            ->where('status', 'completed')
            ->join('payment_methods', 'pos_sales.payment_method_id', '=', 'payment_methods.id')
            ->selectRaw('payment_methods.name as method, SUM(pos_sales.total_amount) as total')
            ->groupBy('payment_methods.id', 'payment_methods.name')
            ->get()
            ->map(fn($item) => [
                'method' => $item->method,
                'total' => (int) $item->total,
            ])
            ->toArray();

        return [
            'session' => [
                'id' => $session->id,
                'branch_id' => $session->branch_id,
                'status' => $session->status,
                'opened_at' => $session->opened_at?->toISOString(),
                'closed_at' => $session->closed_at?->toISOString(),
                'opening_cash' => $session->opening_cash,
                'closing_cash' => $session->closing_cash,
            ],
            'total_sales' => $saleCount,
            'total_amount' => $totalSales,
            'voided_count' => $voidedCount,
            'average_sale' => $saleCount > 0 ? (int) ($totalSales / $saleCount) : 0,
            'payment_breakdown' => $paymentBreakdown,
        ];
    }

    public function getActiveSessions(int $companyId): LengthAwarePaginator
    {
        return POSSession::where('company_id', $companyId)
            ->where('status', 'open')
            ->with(['branch', 'openedBy'])
            ->latest('opened_at')
            ->paginate(15);
    }

    public function getSessionHistory(int $companyId, array $filters = []): LengthAwarePaginator
    {
        $query = POSSession::where('company_id', $companyId)
            ->with(['branch', 'openedBy', 'closedBy']);

        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('opened_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('opened_at', '<=', $filters['date_to']);
        }

        return $query->latest('opened_at')->paginate(15);
    }

    public function getSales(int $sessionId): LengthAwarePaginator
    {
        return POSSale::where('pos_session_id', $sessionId)
            ->with(['items.product', 'customer'])
            ->latest('sale_date')
            ->paginate(20);
    }
}
