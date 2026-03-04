<?php

namespace App\Console\Commands;

use App\Domain\Accounting\Models\JournalEntry;
use App\Domain\Accounting\Models\JournalEntryLine;
use App\Domain\Inventory\Models\StockLevel;
use App\Support\Enums\JournalEntryStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Scans the database for financial data integrity violations.
 *
 * Checks:
 * 1. Unbalanced posted journal entries
 * 2. Posted invoices missing a journal entry
 * 3. Duplicate idempotency keys (should be caught by UNIQUE constraint, but belt-and-suspenders)
 * 4. Negative stock levels (when disallowed)
 * 5. Orphaned journal lines (referencing deleted entries)
 */
class IntegrityCheckCommand extends Command
{
    protected $signature = 'integrity:check
        {--company= : Limit check to a specific company_id}
        {--fix : Attempt to auto-fix certain violations (use with caution)}';

    protected $description = 'Run financial data integrity checks across the ledger and inventory';

    private int $errorCount = 0;

    private int $warningCount = 0;

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════');
        $this->info('  Financial Integrity Check');
        $this->info('  ' . now()->format('d M Y H:i:s'));
        $this->info('═══════════════════════════════════════════════');
        $this->newLine();

        $companyId = $this->option('company') ? (int) $this->option('company') : null;

        $this->checkUnbalancedEntries($companyId);
        $this->checkInvoicesWithoutJournal($companyId);
        $this->checkDuplicateIdempotencyKeys($companyId);
        $this->checkNegativeStock($companyId);
        $this->checkOrphanedJournalLines($companyId);

        $this->newLine();
        $this->info('═══════════════════════════════════════════════');

        if ($this->errorCount > 0) {
            $this->error("  ❌ {$this->errorCount} ERROR(S) found, {$this->warningCount} warning(s)");
            $this->info('═══════════════════════════════════════════════');

            return self::FAILURE;
        }

        if ($this->warningCount > 0) {
            $this->warn("  ⚠️  {$this->warningCount} WARNING(S), 0 errors");
        } else {
            $this->info('  ✅ All checks passed — no integrity violations found');
        }

        $this->info('═══════════════════════════════════════════════');

        return self::SUCCESS;
    }

    /**
     * Check 1: Posted journal entries where sum(debit) ≠ sum(credit).
     */
    private function checkUnbalancedEntries(?int $companyId): void
    {
        $this->info('🔍 Check 1: Unbalanced posted journal entries');

        $query = JournalEntry::withoutGlobalScopes()
            ->where('status', JournalEntryStatus::POSTED)
            ->when($companyId, fn ($q, $id) => $q->where('company_id', $id));

        $unbalanced = [];

        $query->with('lines')->chunk(500, function ($entries) use (&$unbalanced): void {
            foreach ($entries as $entry) {
                $totalDebit = $entry->lines->sum('debit');
                $totalCredit = $entry->lines->sum('credit');

                if ($totalDebit !== $totalCredit) {
                    $unbalanced[] = [
                        'id' => $entry->id,
                        'entry_number' => $entry->entry_number,
                        'company_id' => $entry->company_id,
                        'debit' => $totalDebit,
                        'credit' => $totalCredit,
                        'diff' => abs($totalDebit - $totalCredit),
                    ];
                }
            }
        });

        if (count($unbalanced) === 0) {
            $this->line('  ✅ No unbalanced entries');
        } else {
            $this->errorCount += count($unbalanced);
            $this->error("  ❌ Found " . count($unbalanced) . " unbalanced posted entries:");
            $this->table(
                ['ID', 'Entry#', 'Company', 'Debit', 'Credit', 'Difference'],
                array_map(fn ($e) => [$e['id'], $e['entry_number'], $e['company_id'], $e['debit'], $e['credit'], $e['diff']], $unbalanced),
            );
        }

        $this->newLine();
    }

    /**
     * Check 2: Invoices in sent/partial/paid status that have no linked journal entry.
     */
    private function checkInvoicesWithoutJournal(?int $companyId): void
    {
        $this->info('🔍 Check 2: Posted invoices missing journal entries');

        $missing = DB::table('invoices')
            ->whereIn('status', ['sent', 'partial', 'paid'])
            ->when($companyId, fn ($q, $id) => $q->where('invoices.company_id', $id))
            ->whereNotExists(function ($sub): void {
                $sub->select(DB::raw(1))
                    ->from('journal_entries')
                    ->where('reference_type', 'invoice')
                    ->whereColumn('reference_id', 'invoices.id');
            })
            ->select('id', 'company_id', 'invoice_number', 'status', 'total_amount')
            ->get();

        if ($missing->isEmpty()) {
            $this->line('  ✅ All posted invoices have journal entries');
        } else {
            $this->errorCount += $missing->count();
            $this->error("  ❌ Found {$missing->count()} invoices without journal entries:");
            $this->table(
                ['ID', 'Company', 'Invoice#', 'Status', 'Total'],
                $missing->map(fn ($i) => [$i->id, $i->company_id, $i->invoice_number, $i->status, $i->total_amount])->all(),
            );
        }

        $this->newLine();
    }

    /**
     * Check 3: Duplicate idempotency keys on journal_entries.
     */
    private function checkDuplicateIdempotencyKeys(?int $companyId): void
    {
        $this->info('🔍 Check 3: Duplicate idempotency keys');

        $duplicates = DB::table('journal_entries')
            ->select('idempotency_key', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('idempotency_key')
            ->when($companyId, fn ($q, $id) => $q->where('company_id', $id))
            ->groupBy('idempotency_key')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isEmpty()) {
            $this->line('  ✅ No duplicate idempotency keys');
        } else {
            $this->errorCount += $duplicates->count();
            $this->error("  ❌ Found {$duplicates->count()} duplicate idempotency keys:");
            $this->table(
                ['Idempotency Key', 'Count'],
                $duplicates->map(fn ($d) => [$d->idempotency_key, $d->cnt])->all(),
            );
        }

        $this->newLine();
    }

    /**
     * Check 4: Negative stock levels.
     */
    private function checkNegativeStock(?int $companyId): void
    {
        $this->info('🔍 Check 4: Negative stock levels');

        $negative = StockLevel::withoutGlobalScopes()
            ->where('quantity', '<', 0)
            ->when($companyId, fn ($q, $id) => $q->where('company_id', $id))
            ->with(['product:id,name,sku', 'warehouse:id,name'])
            ->get();

        if ($negative->isEmpty()) {
            $this->line('  ✅ No negative stock levels');
        } else {
            $this->warningCount += $negative->count();
            $this->warn("  ⚠️  Found {$negative->count()} negative stock levels:");
            $this->table(
                ['Product', 'SKU', 'Warehouse', 'Quantity'],
                $negative->map(fn ($s) => [
                    $s->product?->name ?? 'Unknown',
                    $s->product?->sku ?? '-',
                    $s->warehouse?->name ?? 'Unknown',
                    $s->quantity,
                ])->all(),
            );
        }

        $this->newLine();
    }

    /**
     * Check 5: Orphaned journal entry lines (no parent entry).
     */
    private function checkOrphanedJournalLines(?int $companyId): void
    {
        $this->info('🔍 Check 5: Orphaned journal entry lines');

        $orphaned = DB::table('journal_entry_lines')
            ->whereNotExists(function ($sub): void {
                $sub->select(DB::raw(1))
                    ->from('journal_entries')
                    ->whereColumn('journal_entries.id', 'journal_entry_lines.journal_entry_id');
            })
            ->when($companyId, fn ($q, $id) => $q->where('journal_entry_lines.company_id', $id))
            ->count();

        if ($orphaned === 0) {
            $this->line('  ✅ No orphaned journal lines');
        } else {
            $this->warningCount += $orphaned;
            $this->warn("  ⚠️  Found {$orphaned} orphaned journal entry lines");

            if ($this->option('fix')) {
                DB::table('journal_entry_lines')
                    ->whereNotExists(function ($sub): void {
                        $sub->select(DB::raw(1))
                            ->from('journal_entries')
                            ->whereColumn('journal_entries.id', 'journal_entry_lines.journal_entry_id');
                    })
                    ->when($companyId, fn ($q, $id) => $q->where('journal_entry_lines.company_id', $id))
                    ->delete();

                $this->info("  🔧 Deleted {$orphaned} orphaned lines");
            }
        }

        $this->newLine();
    }
}
