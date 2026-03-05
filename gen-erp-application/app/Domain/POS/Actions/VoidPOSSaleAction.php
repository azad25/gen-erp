<?php

namespace App\Domain\POS\Actions;

use App\Domain\POS\Events\POSSaleVoided;
use App\Domain\POS\Exceptions\InvalidPOSSaleException;
use App\Domain\POS\Models\POSSale;

class VoidPOSSaleAction
{
    public function execute(POSSale $sale): POSSale
    {
        if ($sale->status !== 'completed') {
            throw new InvalidPOSSaleException('Only completed sales can be voided.');
        }

        $sale->update(['status' => 'voided']);

        event(new POSSaleVoided($sale));

        return $sale->fresh();
    }
}
