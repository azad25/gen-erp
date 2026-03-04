<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Accounting\Actions\MonthEndClose;
use App\Domain\Auth\Models\Company;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * API controller for managing company lock dates and period close operations.
 */
class LockDateController extends Controller
{
    public function __construct(
        private readonly MonthEndClose $monthEndClose,
    ) {}

    /**
     * Get the current lock date for the company.
     */
    public function show(Company $company): JsonResponse
    {
        $this->authorize('view', $company);

        return response()->json([
            'data' => [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'lock_date' => $company->lock_date?->toDateString(),
                'lock_date_formatted' => $company->lock_date?->format('d M Y'),
                'is_locked' => $company->lock_date !== null,
                'days_since_lock' => $company->lock_date 
                    ? $company->lock_date->diffInDays(now()) 
                    : null,
            ],
        ]);
    }

    /**
     * Update the company lock date.
     */
    public function update(Request $request, Company $company): JsonResponse
    {
        $this->authorize('update', $company);

        $validated = $request->validate([
            'lock_date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $newLockDate = Carbon::parse($validated['lock_date']);

        // Validate that new lock date is not before current lock date
        if ($company->lock_date && $newLockDate->lt($company->lock_date)) {
            throw ValidationException::withMessages([
                'lock_date' => ['Lock date cannot be moved backwards. Current lock date: ' . $company->lock_date->format('d M Y')],
            ]);
        }

        $company->update([
            'lock_date' => $newLockDate,
        ]);

        return response()->json([
            'message' => 'Lock date updated successfully.',
            'data' => [
                'company_id' => $company->id,
                'lock_date' => $company->lock_date->toDateString(),
                'lock_date_formatted' => $company->lock_date->format('d M Y'),
                'is_locked' => true,
                'days_since_lock' => $company->lock_date->diffInDays(now()),
            ],
        ]);
    }

    /**
     * Perform month-end close operation.
     */
    public function monthEndClose(Request $request, Company $company): JsonResponse
    {
        $this->authorize('update', $company);

        $validated = $request->validate([
            'close_date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        try {
            $result = $this->monthEndClose->execute($company, Carbon::parse($validated['close_date']));

            return response()->json([
                'message' => 'Month-end close completed successfully.',
                'data' => [
                    'company_id' => $company->id,
                    'lock_date' => $company->fresh()->lock_date->toDateString(),
                    'lock_date_formatted' => $company->fresh()->lock_date->format('d M Y'),
                    'integrity_check_passed' => $result['integrity_check_passed'],
                    'issues_found' => $result['issues_found'] ?? [],
                    'invoices_checked' => $result['invoices_checked'] ?? 0,
                    'journal_entries_checked' => $result['journal_entries_checked'] ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Month-end close failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get lock date validation info (what would be blocked).
     */
    public function validateLockDate(Request $request, Company $company): JsonResponse
    {
        $this->authorize('view', $company);

        $validated = $request->validate([
            'proposed_lock_date' => ['required', 'date'],
        ]);

        $proposedDate = Carbon::parse($validated['proposed_lock_date']);

        // Count transactions that would be affected
        $affectedInvoices = \App\Domain\Invoice\Models\Invoice::where('company_id', $company->id)
            ->where('status', 'draft')
            ->where('invoice_date', '<=', $proposedDate)
            ->count();

        $affectedJournals = \App\Domain\Accounting\Models\JournalEntry::where('company_id', $company->id)
            ->where('status', 'draft')
            ->where('entry_date', '<=', $proposedDate)
            ->count();

        return response()->json([
            'data' => [
                'proposed_lock_date' => $proposedDate->toDateString(),
                'proposed_lock_date_formatted' => $proposedDate->format('d M Y'),
                'current_lock_date' => $company->lock_date?->toDateString(),
                'is_valid' => $company->lock_date === null || $proposedDate->gte($company->lock_date),
                'affected_transactions' => [
                    'draft_invoices' => $affectedInvoices,
                    'draft_journals' => $affectedJournals,
                    'total' => $affectedInvoices + $affectedJournals,
                ],
                'warnings' => $this->getLockDateWarnings($company, $proposedDate),
            ],
        ]);
    }

    /**
     * Get warnings for the proposed lock date.
     */
    private function getLockDateWarnings(Company $company, Carbon $proposedDate): array
    {
        $warnings = [];

        // Check if there are unposted invoices
        $unpostedInvoices = \App\Domain\Invoice\Models\Invoice::where('company_id', $company->id)
            ->where('status', 'draft')
            ->where('invoice_date', '<=', $proposedDate)
            ->count();

        if ($unpostedInvoices > 0) {
            $warnings[] = "There are {$unpostedInvoices} draft invoices dated on or before the proposed lock date.";
        }

        // Check if lock date is too far in the past
        $daysSincePeriod = $proposedDate->diffInDays(now());
        if ($daysSincePeriod > 45) {
            $warnings[] = "Lock date is {$daysSincePeriod} days ago. Consider running integrity checks.";
        }

        // Check if it's month-end
        if ($proposedDate->day !== $proposedDate->endOfMonth()->day) {
            $warnings[] = "Lock date is not at month-end. Consider using month-end close instead.";
        }

        return $warnings;
    }
}