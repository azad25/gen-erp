<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\POSSale;
use App\Models\POSSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Branch-level reporting: sales summaries, performance ranking, POS daily reports.
 */
class BranchReportService
{
    /**
     * @return array{total_sales: int, invoice_count: int, average_sale: int}
     */
    public function salesSummary(Company $company, Carbon $from, Carbon $to, ?Branch $branch = null): array
    {
        $query = \App\Models\Invoice::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereBetween('invoice_date', [$from, $to]);

        if ($branch) {
            $query->where('branch_id', $branch->id);
        }

        $total = (int) (clone $query)->sum('total_amount');
        $count = (clone $query)->count();

        return [
            'total_sales' => $total,
            'invoice_count' => $count,
            'average_sale' => $count > 0 ? (int) ($total / $count) : 0,
        ];
    }

    /**
     * Rank branches by revenue.
     *
     * @return Collection<int, array{branch_name: string, revenue: int, invoice_count: int}>
     */
    public function branchPerformanceRanking(Company $company, Carbon $from, Carbon $to): Collection
    {
        $branches = Branch::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->get();

        return $branches->map(function (Branch $branch) use ($company, $from, $to) {
            $summary = $this->salesSummary($company, $from, $to, $branch);

            return [
                'branch_name' => $branch->name,
                'revenue' => $summary['total_sales'],
                'invoice_count' => $summary['invoice_count'],
            ];
        })->sortByDesc('revenue')->values();
    }

    /**
     * Daily POS summary for a branch.
     *
     * @return array{total_sales: int, sale_count: int, sessions_opened: int, sessions_closed: int}
     */
    public function dailyPOSSummary(Branch $branch, Carbon $date): array
    {
        $totalSales = (int) POSSale::withoutGlobalScopes()
            ->where('branch_id', $branch->id)
            ->where('status', 'completed')
            ->whereDate('sale_date', $date)
            ->sum('total_amount');

        $saleCount = POSSale::withoutGlobalScopes()
            ->where('branch_id', $branch->id)
            ->where('status', 'completed')
            ->whereDate('sale_date', $date)
            ->count();

        $sessionsOpened = POSSession::withoutGlobalScopes()
            ->where('branch_id', $branch->id)
            ->whereDate('opened_at', $date)
            ->count();

        $sessionsClosed = POSSession::withoutGlobalScopes()
            ->where('branch_id', $branch->id)
            ->whereDate('closed_at', $date)
            ->count();

        return [
            'total_sales' => $totalSales,
            'sale_count' => $saleCount,
            'sessions_opened' => $sessionsOpened,
            'sessions_closed' => $sessionsClosed,
        ];
    }
}
