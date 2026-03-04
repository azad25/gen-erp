<?php

namespace App\Domain\Accounting\Actions;

use App\Domain\Auth\Models\Company;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

/**
 * Execute a month-end close for a company.
 *
 * Runs the integrity check, and if passed, advances the lock_date
 * to the end of the specified period. This prevents any modifications
 * to entries on or before the lock_date.
 */
class MonthEndClose
{
    /**
     * Close the books for the given period.
     *
     * @throws RuntimeException If integrity check fails
     * @return array{integrity_check_passed: bool, issues_found: array, invoices_checked: int, journal_entries_checked: int}
     */
    public function execute(Company $company, \Carbon\Carbon|string $closingDate, ?int $closedBy = null): array
    {
        // Validate the closing date
        $closingDate = $closingDate instanceof \Carbon\Carbon ? $closingDate : \Carbon\Carbon::parse($closingDate);
        
        if ($company->lock_date && $closingDate->lte($company->lock_date)) {
            throw new RuntimeException(
                __('Closing date :date must be after the current lock date :lock', [
                    'date' => $closingDate->format('d M Y'),
                    'lock' => $company->lock_date->format('d M Y'),
                ])
            );
        }

        // Count items to be checked
        $invoicesCount = \App\Domain\Invoice\Models\Invoice::where('company_id', $company->id)
            ->where('status', 'sent')
            ->where('invoice_date', '<=', $closingDate)
            ->count();

        $journalsCount = \App\Domain\Accounting\Models\JournalEntry::where('company_id', $company->id)
            ->where('status', 'posted')
            ->where('entry_date', '<=', $closingDate)
            ->count();

        // Run integrity check for this company
        $exitCode = Artisan::call('integrity:check', [
            '--company' => $company->id,
        ]);

        $output = Artisan::output();
        $issues = [];

        // Parse output for issues (basic parsing)
        if ($exitCode !== 0) {
            $lines = explode("\n", $output);
            foreach ($lines as $line) {
                if (str_contains($line, 'ERROR') || str_contains($line, 'FAIL')) {
                    $issues[] = trim($line);
                }
            }

            throw new RuntimeException(
                __('Integrity check failed. Please resolve all issues before closing the period.')
            );
        }

        // Set the lock date
        Company::withoutGlobalScopes()
            ->where('id', $company->id)
            ->update(['lock_date' => $closingDate]);

        return [
            'integrity_check_passed' => true,
            'issues_found' => $issues,
            'invoices_checked' => $invoicesCount,
            'journal_entries_checked' => $journalsCount,
        ];
    }
}
