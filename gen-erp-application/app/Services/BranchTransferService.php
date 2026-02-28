<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\StockTransfer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Wraps stock transfers with branch awareness.
 */
class BranchTransferService
{
    /**
     * Create a transfer between branches.
     *
     * @param  array<int, array{product_id: int, quantity: float, variant_id?: int}>  $items
     */
    public function createTransfer(Branch $fromBranch, Branch $toBranch, array $items, ?string $notes = null): StockTransfer
    {
        if ($fromBranch->id === $toBranch->id) {
            throw new RuntimeException(__('Cannot transfer stock to the same branch.'));
        }

        if ($fromBranch->company_id !== $toBranch->company_id) {
            throw new RuntimeException(__('Cannot transfer stock between different companies.'));
        }

        if (! $fromBranch->warehouse_id || ! $toBranch->warehouse_id) {
            throw new RuntimeException(__('Both branches must have a primary warehouse.'));
        }

        return DB::transaction(function () use ($fromBranch, $toBranch, $items, $notes): StockTransfer {
            $transfer = StockTransfer::withoutGlobalScopes()->create([
                'company_id' => $fromBranch->company_id,
                'from_warehouse_id' => $fromBranch->warehouse_id,
                'to_warehouse_id' => $toBranch->warehouse_id,
                'status' => 'pending',
                'notes' => $notes,
            ]);

            foreach ($items as $item) {
                $transfer->items()->create([
                    'company_id' => $fromBranch->company_id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                ]);
            }

            return $transfer->load('items');
        });
    }

    /**
     * Get pending transfers for a branch (as sender or receiver).
     *
     * @return Collection<int, StockTransfer>
     */
    public function pendingTransfers(Branch $branch): Collection
    {
        return StockTransfer::withoutGlobalScopes()
            ->where('company_id', $branch->company_id)
            ->where('status', 'pending')
            ->where(function ($q) use ($branch): void {
                $q->where('from_warehouse_id', $branch->warehouse_id)
                    ->orWhere('to_warehouse_id', $branch->warehouse_id);
            })
            ->get();
    }
}
