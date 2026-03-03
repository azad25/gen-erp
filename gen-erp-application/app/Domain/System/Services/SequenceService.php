<?php

namespace App\Domain\System\Services;

use App\Domain\Auth\Models\Company;
use App\Domain\System\Models\NumberSequence;
use Illuminate\Support\Facades\DB;

/**
 * Atomic document number generation with configurable format.
 */
class SequenceService
{
    /**
     * Get the next number for a document type — atomic, no gaps, no duplicates.
     */
    public function next(string $documentType, Company|int $company): string
    {
        $companyId = $company instanceof Company ? $company->id : $company;
        
        return DB::transaction(function () use ($documentType, $companyId): string {
            $sequence = $this->getOrCreateById($documentType, $companyId);

            // Lock the row for atomic update
            $sequence = NumberSequence::withoutGlobalScopes()
                ->where('id', $sequence->id)
                ->lockForUpdate()
                ->first();

            $this->checkAndReset($sequence);

            $number = $this->format($sequence);

            $sequence->update(['next_number' => $sequence->next_number + 1]);

            return $number;
        });
    }

    /**
     * Preview what the next number will look like.
     */
    public function preview(NumberSequence $sequence): string
    {
        return $this->format($sequence);
    }

    /**
     * Get or create a sequence for a document type.
     */
    public function getOrCreate(string $documentType, Company $company): NumberSequence
    {
        return $this->getOrCreateById($documentType, $company->id);
    }

    /**
     * Get or create a sequence for a document type by company ID.
     */
    public function getOrCreateById(string $documentType, int $companyId): NumberSequence
    {
        // Define proper prefixes for different document types
        $prefixes = [
            'customer' => 'CUST',
            'supplier' => 'SUPP',
            'purchase_order' => 'PO',
            'sales_order' => 'SO',
            'invoice' => 'INV',
            'goods_receipt' => 'GRN',
            'payment' => 'RCP',
            'purchase_return' => 'PUR',
            'sales_return' => 'SRN',
            'credit_note' => 'CN',
        ];

        $defaultPrefix = $prefixes[$documentType] ?? strtoupper(substr($documentType, 0, 3));

        return NumberSequence::withoutGlobalScopes()->firstOrCreate(
            [
                'company_id' => $companyId,
                'document_type' => $documentType,
            ],
            [
                'prefix' => $defaultPrefix,
                'separator' => '-',
                'padding' => 4,
                'next_number' => 1,
                'reset_frequency' => 'never',
                'include_date' => false,
            ],
        );
    }

    /**
     * Format the number: {prefix}{sep}{date?}{sep}{padded_number}{sep}{suffix}
     */
    private function format(NumberSequence $sequence): string
    {
        $parts = [];

        if ($sequence->prefix) {
            $parts[] = $sequence->prefix;
        }

        if ($sequence->include_date) {
            $format = $sequence->date_format ?: 'Ymd';
            $parts[] = now()->format($format);
        }

        $parts[] = str_pad((string) $sequence->next_number, $sequence->padding, '0', STR_PAD_LEFT);

        if ($sequence->suffix) {
            $parts[] = $sequence->suffix;
        }

        return implode($sequence->separator ?? '-', $parts);
    }

    /**
     * Reset counter if reset_frequency triggers.
     */
    private function checkAndReset(NumberSequence $sequence): void
    {
        $now = now();

        if ($sequence->reset_frequency === 'yearly') {
            if ($sequence->last_reset_at === null || $sequence->last_reset_at->year < $now->year) {
                $sequence->update([
                    'next_number' => 1,
                    'last_reset_at' => $now->toDateString(),
                ]);
                $sequence->refresh();
            }
        } elseif ($sequence->reset_frequency === 'monthly') {
            if ($sequence->last_reset_at === null
                || $sequence->last_reset_at->year < $now->year
                || $sequence->last_reset_at->month < $now->month) {
                $sequence->update([
                    'next_number' => 1,
                    'last_reset_at' => $now->toDateString(),
                ]);
                $sequence->refresh();
            }
        }
    }
}
