<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\POSSale;
use App\Models\POSSaleItem;
use App\Models\POSSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * POS terminal operations: session management and sales processing.
 */
class POSService
{
    /**
     * Open a new POS session for a branch.
     */
    public function openSession(Branch $branch, User $user, int $openingCash): POSSession
    {
        $existingOpen = POSSession::withoutGlobalScopes()
            ->where('branch_id', $branch->id)
            ->where('status', 'open')
            ->exists();

        if ($existingOpen) {
            throw new RuntimeException(__('Branch already has an open POS session.'));
        }

        return POSSession::withoutGlobalScopes()->create([
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'opened_by' => $user->id,
            'opening_cash' => $openingCash,
            'status' => 'open',
            'opened_at' => now(),
        ]);
    }

    /**
     * Close a POS session — calculates expected vs actual cash.
     */
    public function closeSession(POSSession $session, User $user, int $closingCash): void
    {
        if (! $session->isOpen()) {
            throw new RuntimeException(__('Session is already closed.'));
        }

        // Expected = opening + cash sales - cash refunds
        $cashSales = POSSale::withoutGlobalScopes()
            ->where('pos_session_id', $session->id)
            ->where('status', 'completed')
            ->sum('total_amount');

        $cashRefunds = POSSale::withoutGlobalScopes()
            ->where('pos_session_id', $session->id)
            ->where('status', 'refunded')
            ->sum('total_amount');

        $expectedCash = $session->opening_cash + $cashSales - $cashRefunds;

        $session->update([
            'closed_by' => $user->id,
            'closing_cash' => $closingCash,
            'expected_cash' => $expectedCash,
            'cash_difference' => $closingCash - $expectedCash,
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    /**
     * Create a POS sale within an open session.
     *
     * @param  array<int, array{product_id?: int, variant_id?: int, description: string, quantity: float, unit_price: int, discount_amount?: int, tax_amount?: int}>  $items
     * @param  array{amount_tendered: int, payment_method_id?: int}  $paymentData
     */
    public function createSale(POSSession $session, array $items, array $paymentData, ?int $customerId = null): POSSale
    {
        if (! $session->isOpen()) {
            throw new RuntimeException(__('Cannot create sale: session is not open.'));
        }

        return DB::transaction(function () use ($session, $items, $paymentData, $customerId): POSSale {
            $subtotal = 0;
            $totalDiscount = 0;
            $totalTax = 0;

            foreach ($items as $item) {
                $lineTotal = (int) ($item['quantity'] * $item['unit_price']) - ($item['discount_amount'] ?? 0);
                $subtotal += (int) ($item['quantity'] * $item['unit_price']);
                $totalDiscount += $item['discount_amount'] ?? 0;
                $totalTax += $item['tax_amount'] ?? 0;
            }

            $totalAmount = $subtotal - $totalDiscount + $totalTax;

            $sale = POSSale::withoutGlobalScopes()->create([
                'company_id' => $session->company_id,
                'branch_id' => $session->branch_id,
                'pos_session_id' => $session->id,
                'customer_id' => $customerId,
                'sale_date' => now(),
                'subtotal' => $subtotal,
                'discount_amount' => $totalDiscount,
                'tax_amount' => $totalTax,
                'total_amount' => $totalAmount,
                'amount_tendered' => $paymentData['amount_tendered'],
                'payment_method_id' => $paymentData['payment_method_id'] ?? null,
                'status' => 'completed',
            ]);

            foreach ($items as $item) {
                $lineTotal = (int) ($item['quantity'] * $item['unit_price']) - ($item['discount_amount'] ?? 0) + ($item['tax_amount'] ?? 0);

                POSSaleItem::withoutGlobalScopes()->create([
                    'pos_sale_id' => $sale->id,
                    'company_id' => $session->company_id,
                    'product_id' => $item['product_id'] ?? null,
                    'variant_id' => $item['variant_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'line_total' => $lineTotal,
                ]);
            }

            return $sale->load('items');
        });
    }

    /**
     * Void a completed POS sale.
     */
    public function voidSale(POSSale $sale): void
    {
        if ($sale->status !== 'completed') {
            throw new RuntimeException(__('Only completed sales can be voided.'));
        }

        $sale->update(['status' => 'voided']);
    }

    /**
     * Get session summary: totals, counts.
     *
     * @return array{total_sales: int, sale_count: int, voided_count: int, average_sale: int}
     */
    public function getSessionSummary(POSSession $session): array
    {
        $completed = POSSale::withoutGlobalScopes()
            ->where('pos_session_id', $session->id)
            ->where('status', 'completed');

        $totalSales = (int) (clone $completed)->sum('total_amount');
        $saleCount = (clone $completed)->count();

        $voidedCount = POSSale::withoutGlobalScopes()
            ->where('pos_session_id', $session->id)
            ->where('status', 'voided')
            ->count();

        return [
            'total_sales' => $totalSales,
            'sale_count' => $saleCount,
            'voided_count' => $voidedCount,
            'average_sale' => $saleCount > 0 ? (int) ($totalSales / $saleCount) : 0,
        ];
    }
}
