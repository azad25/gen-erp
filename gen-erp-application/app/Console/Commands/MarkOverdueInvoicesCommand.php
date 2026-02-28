<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Console\Command;

/**
 * Marks invoices as overdue when due_date has passed and payment is incomplete.
 */
class MarkOverdueInvoicesCommand extends Command
{
    protected $signature = 'invoices:mark-overdue';

    protected $description = 'Mark sent/partial invoices as overdue when due_date < today';

    public function handle(): int
    {
        $companies = Company::withoutGlobalScopes()->get();
        $totalUpdated = 0;

        foreach ($companies as $company) {
            $updated = Invoice::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->whereIn('status', [InvoiceStatus::SENT, InvoiceStatus::PARTIAL])
                ->where('due_date', '<', now()->toDateString())
                ->update(['status' => InvoiceStatus::OVERDUE]);

            $totalUpdated += $updated;
        }

        $this->info("Marked {$totalUpdated} invoice(s) as overdue.");

        return self::SUCCESS;
    }
}
