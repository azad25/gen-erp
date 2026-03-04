<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Domain\Project\Services\ProjectService;
use App\Services\CompanyContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectsDashboardController extends Controller
{
    public function __construct(
        private ProjectService $projectService
    ) {}

    public function index(Request $request): Response
    {
        $company = CompanyContext::active();
        
        $dashboardData = $this->projectService->getDashboardData($company->id);
        
        // Add some mock data if no projects exist
        if ($dashboardData['total_projects'] === 0) {
            $dashboardData = [
                'total_projects' => 3,
                'active_projects' => 2,
                'completed_projects' => 1,
                'overdue_projects' => 0,
                'recent_projects' => [
                    [
                        'id' => 1,
                        'name' => 'Sample Project 1',
                        'status' => 'active',
                        'priority' => 'high',
                        'progress_percentage' => 75,
                        'project_manager' => (object) ['name' => 'John Doe'],
                        'end_date' => now()->addDays(30)->format('Y-m-d'),
                    ],
                    [
                        'id' => 2,
                        'name' => 'Sample Project 2',
                        'status' => 'planning',
                        'priority' => 'medium',
                        'progress_percentage' => 25,
                        'project_manager' => (object) ['name' => 'Jane Smith'],
                        'end_date' => now()->addDays(60)->format('Y-m-d'),
                    ],
                ],
                'projects_by_status' => [
                    'active' => 2,
                    'planning' => 1,
                ],
                'projects_by_priority' => [
                    'high' => 1,
                    'medium' => 2,
                ],
            ];
        }

        return Inertia::render('Projects/Dashboard', [
            'dashboardData' => $dashboardData,
        ]);
    }
}