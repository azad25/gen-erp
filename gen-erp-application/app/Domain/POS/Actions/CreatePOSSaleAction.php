<?php

namespace App\Domain\POS\Actions;

use App\Domain\POS\DTOs\CreatePOSSaleData;
use App\Domain\POS\Events\POSSaleCreated;
use App\Domain\POS\Exceptions\SessionClosedException;
use App\Domain\POS\Models\POSSale;
use App\Domain\POS\Models\POSSession;
use Illuminate\Support\Facades\DB;

class CreatePOSSaleAction
{
    public function execute(CreatePOSSaleData $data): POSSale
    {
        $session = POSSession::findOrFail($data->sessionId);

        if (!$session->isOpen()) {
            throw new SessionClosedException('Cannot create sale: session is not open.');
        }

        return DB::transaction(function () use ($data, $session) {
            $subtotal = $data->calculateSubtotal();
            $discountAmount = $data->calculateTotalDiscount();
            $taxAmount = $data->calculateTotalTax();
            $totalAmount = $data->calculateTotal();
            $changeAmount = $data->amountTendered - $totalAmount;

            // Generate unique sale number
            $saleNumber = $this->generateSaleNumber($session);

            $sale = POSSale::create([
                'company_id' => $session->company_id,
                'branch_id' => $session->branch_id,
                'pos_session_id' => $session->id,
                'customer_id' => $data->customerId,
                'sale_number' => $saleNumber,
                'sale_date' => now(),
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'amount_tendered' => $data->amountTendered,
                'change_amount' => $changeAmount,
                'payment_method_id' => $data->paymentMethodId,
                'status' => 'completed',
                'created_by' => auth()->id(),
            ]);

            foreach ($data->items as $itemData) {
                $sale->items()->create([
                    'company_id' => $session->company_id,
                    ...$itemData->toArray(),
                ]);
            }

            event(new POSSaleCreated($sale));

            return $sale->load('items');
        });
    }

    private function generateSaleNumber(POSSession $session): string
    {
        $date = now()->format('Ymd');
        $branchCode = $session->branch->code ?? 'BR';
        
        // Get the last sale number for today
        $lastSale = POSSale::where('pos_session_id', $session->id)
            ->whereDate('sale_date', now())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastSale ? (int) substr($lastSale->sale_number, -4) + 1 : 1;

        return sprintf('POS-%s-%s-%04d', $branchCode, $date, $sequence);
    }
}
