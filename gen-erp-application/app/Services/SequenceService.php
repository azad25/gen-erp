<?php

namespace App\Services;

use App\Models\Company;
use App\Models\NumberSequence;
use Illuminate\Support\Facades\DB;

/**
 * Atomic document number generation with configurable format.
 */
class SequenceService
{
    /**
     * Get the next number for a document type — atomic, no gaps, no duplicates.
     */
    public function next(string $documentType, Company $company): string
    {
        return DB::transaction(function () use ($documentType, $company): string {
            $sequence = $this->getOrCreate($documentType, $company);

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
        return NumberSequence::withoutGlobalScopes()->firstOrCreate(
            [
                'company_id' => $company->id,
                'document_type' => $documentType,
            ],
            [
                'prefix' => strtoupper(substr($documentType, 0, 3)),
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
