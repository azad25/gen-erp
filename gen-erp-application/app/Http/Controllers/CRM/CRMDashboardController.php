<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Services\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CRMDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $company = CompanyContext::active();
        $period = $request->get('period', '30');
        
        $startDate = now()->subDays((int) $period);
        $endDate = now();

        $metrics = [
            'totalLeads' => $this->getTotalLeads($company->id, $startDate, $endDate),
            'qualifiedLeads' => $this->getQualifiedLeads($company->id, $startDate, $endDate),
            'totalOpportunities' => $this->getTotalOpportunities($company->id, $startDate, $endDate),
            'wonOpportunities' => $this->getWonOpportunities($company->id, $startDate, $endDate),
            'totalRevenue' => $this->getTotalRevenue($company->id, $startDate, $endDate),
            'conversionRate' => $this->getConversionRate($company->id, $startDate, $endDate),
            'averageDealSize' => $this->getAverageDealSize($company->id, $startDate, $endDate),
            'activitiesCompleted' => $this->getActivitiesCompleted($company->id, $startDate, $endDate),
        ];

        // Add some mock data if no data exists
        if ($metrics['totalLeads'] === 0) {
            $metrics = [
                'totalLeads' => 45,
                'qualifiedLeads' => 12,
                'totalOpportunities' => 8,
                'wonOpportunities' => 3,
                'totalRevenue' => 150000,
                'conversionRate' => 26.7,
                'averageDealSize' => 50000,
                'activitiesCompleted' => 28,
            ];
        }

        return Inertia::render('CRM/Dashboard/Index', [
            'metrics' => $metrics,
            'topPerformers' => $this->getTopPerformers($company->id, 'revenue', $startDate, $endDate),
        ]);
    }

    private function getTotalLeads(int $companyId, $startDate, $endDate): int
    {
        return DB::table('leads')
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    private function getQualifiedLeads(int $companyId, $startDate, $endDate): int
    {
        return DB::table('leads')
            ->where('company_id', $companyId)
            ->where('status', 'qualified')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    private function getTotalOpportunities(int $companyId, $startDate, $endDate): int
    {
        return DB::table('opportunities')
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    private function getWonOpportunities(int $companyId, $startDate, $endDate): int
    {
        return DB::table('opportunities')
            ->where('company_id', $companyId)
            ->where('status', 'won')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();
    }

    private function getTotalRevenue(int $companyId, $startDate, $endDate): int
    {
        return (int) DB::table('opportunities')
            ->where('company_id', $companyId)
            ->where('status', 'won')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->sum('amount') ?? 0;
    }

    private function getConversionRate(int $companyId, $startDate, $endDate): float
    {
        $totalLeads = $this->getTotalLeads($companyId, $startDate, $endDate);
        $qualifiedLeads = $this->getQualifiedLeads($companyId, $startDate, $endDate);
        
        return $totalLeads > 0 ? round(($qualifiedLeads / $totalLeads) * 100, 1) : 0;
    }

    private function getAverageDealSize(int $companyId, $startDate, $endDate): int
    {
        $wonOpportunities = $this->getWonOpportunities($companyId, $startDate, $endDate);
        $totalRevenue = $this->getTotalRevenue($companyId, $startDate, $endDate);
        
        return $wonOpportunities > 0 ? (int) ($totalRevenue / $wonOpportunities) : 0;
    }

    private function getActivitiesCompleted(int $companyId, $startDate, $endDate): int
    {
        return DB::table('crm_activities')
            ->where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->count();
    }

    private function getTopPerformers(int $companyId, string $metric, $startDate, $endDate): array
    {
        // Mock data for now - in real implementation, this would query actual performance data
        return [
            [
                'id' => 1,
                'name' => 'John Doe',
                'value' => 150000,
                'deals' => 5,
                'activities' => 25,
            ],
            [
                'id' => 2,
                'name' => 'Jane Smith',
                'value' => 120000,
                'deals' => 4,
                'activities' => 20,
            ],
            [
                'id' => 3,
                'name' => 'Mike Johnson',
                'value' => 95000,
                'deals' => 3,
                'activities' => 18,
            ],
        ];
    }
}