<?php

namespace App\Http\Controllers\Api\V1\CRM;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function metrics(Request $request): JsonResponse
    {
        $company = $request->user()->activeCompany();
        $period = $request->get('period', '30'); // days
        
        $startDate = now()->subDays((int) $period);
        $endDate = now();

        // Get CRM metrics
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

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    public function topPerformers(Request $request): JsonResponse
    {
        $company = $request->user()->activeCompany();
        $metric = $request->get('metric', 'revenue');
        $period = $request->get('period', '30');
        
        $startDate = now()->subDays((int) $period);
        $endDate = now();

        $performers = $this->getTopPerformers($company->id, $metric, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $performers,
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