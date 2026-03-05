<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Project\DTOs\CreateProjectData;
use App\Domain\Project\DTOs\UpdateProjectData;
use App\Domain\Project\Services\ProjectService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @tags Projects
 */
class ProjectController extends Controller
{
    public function __construct(
        private ProjectService $projectService
    ) {}

    /**
     * Get all projects
     * 
     * @group Projects
     * @queryParam status string Filter by status (planning, active, on_hold, completed, cancelled)
     * @queryParam priority string Filter by priority (low, medium, high, urgent)
     * @queryParam project_manager_id integer Filter by project manager ID
     * @queryParam search string Search in name, description, or client name
     * @queryParam overdue boolean Filter overdue projects
     * @queryParam sort_by string Sort by field (default: created_at)
     * @queryParam sort_order string Sort order (asc, desc) (default: desc)
     * @queryParam per_page integer Items per page (default: 15)
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status', 'priority', 'project_manager_id', 'search', 'overdue',
            'sort_by', 'sort_order', 'per_page'
        ]);

        $projects = $this->projectService->getAllProjects(
            $request->user()->activeCompany()->id,
            $filters
        );

        return response()->json([
            'success' => true,
            'data' => $projects,
        ]);
    }

    /**
     * Get project details
     * 
     * @group Projects
     * @urlParam id integer required Project ID
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $project = $this->projectService->getProjectById($id, $request->user()->activeCompany()->id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => $project,
        ]);
    }

    /**
     * Create a new project
     * 
     * @group Projects
     * @bodyParam name string required Project name
     * @bodyParam description string Project description
     * @bodyParam status string Project status (planning, active, on_hold, completed, cancelled)
     * @bodyParam priority string Project priority (low, medium, high, urgent)
     * @bodyParam start_date string Project start date (Y-m-d format)
     * @bodyParam end_date string Project end date (Y-m-d format)
     * @bodyParam budget number Project budget
     * @bodyParam currency string Currency code (default: USD)
     * @bodyParam client_name string Client name
     * @bodyParam client_email string Client email
     * @bodyParam client_phone string Client phone
     * @bodyParam project_manager_id integer Project manager employee ID
     * @bodyParam is_billable boolean Is project billable (default: true)
     * @bodyParam hourly_rate number Hourly rate for billing
     * @bodyParam estimated_hours number Estimated hours
     * @bodyParam color string Project color (hex code)
     * @bodyParam settings object Additional project settings
     * @bodyParam member_ids array Initial team member IDs
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:planning,active,on_hold,completed,cancelled',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'client_name' => 'nullable|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'client_phone' => 'nullable|string|max:20',
            'project_manager_id' => 'nullable|exists:employees,id',
            'is_billable' => 'nullable|boolean',
            'hourly_rate' => 'nullable|numeric|min:0',
            'estimated_hours' => 'nullable|numeric|min:0',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings' => 'nullable|array',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:employees,id',
        ]);

        $data = CreateProjectData::fromArray(array_merge(
            $request->all(),
            ['company_id' => $request->user()->activeCompany()->id]
        ));

        $project = $this->projectService->createProject($data);

        return response()->json([
            'success' => true,
            'message' => 'Project created successfully',
            'data' => $project,
        ], Response::HTTP_CREATED);
    }

    /**
     * Update a project
     * 
     * @group Projects
     * @urlParam id integer required Project ID
     * @bodyParam name string Project name
     * @bodyParam description string Project description
     * @bodyParam status string Project status
     * @bodyParam priority string Project priority
     * @bodyParam start_date string Project start date
     * @bodyParam end_date string Project end date
     * @bodyParam budget number Project budget
     * @bodyParam currency string Currency code
     * @bodyParam client_name string Client name
     * @bodyParam client_email string Client email
     * @bodyParam client_phone string Client phone
     * @bodyParam project_manager_id integer Project manager employee ID
     * @bodyParam is_billable boolean Is project billable
     * @bodyParam hourly_rate number Hourly rate
     * @bodyParam estimated_hours number Estimated hours
     * @bodyParam color string Project color
     * @bodyParam settings object Additional settings
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:planning,active,on_hold,completed,cancelled',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'budget' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'client_name' => 'nullable|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'client_phone' => 'nullable|string|max:20',
            'project_manager_id' => 'nullable|exists:employees,id',
            'is_billable' => 'sometimes|boolean',
            'hourly_rate' => 'sometimes|numeric|min:0',
            'estimated_hours' => 'sometimes|numeric|min:0',
            'color' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings' => 'sometimes|array',
        ]);

        $data = UpdateProjectData::fromArray($request->all());
        $project = $this->projectService->updateProject($id, $data);

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully',
            'data' => $project,
        ]);
    }

    /**
     * Delete a project
     * 
     * @group Projects
     * @urlParam id integer required Project ID
     */
    public function destroy(int $id): JsonResponse
    {
        $this->projectService->deleteProject($id);

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully',
        ]);
    }

    /**
     * Add member to project
     * 
     * @group Projects
     * @urlParam id integer required Project ID
     * @bodyParam employee_id integer required Employee ID
     * @bodyParam role string Member role (default: member)
     * @bodyParam hourly_rate number Member hourly rate
     */
    public function addMember(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'role' => 'nullable|string|max:50',
            'hourly_rate' => 'nullable|numeric|min:0',
        ]);

        $this->projectService->addProjectMember(
            $id,
            $request->employee_id,
            $request->role ?? 'member',
            $request->hourly_rate
        );

        return response()->json([
            'success' => true,
            'message' => 'Member added to project successfully',
        ]);
    }

    /**
     * Remove member from project
     * 
     * @group Projects
     * @urlParam id integer required Project ID
     * @urlParam employeeId integer required Employee ID
     */
    public function removeMember(int $id, int $employeeId): JsonResponse
    {
        $this->projectService->removeProjectMember($id, $employeeId);

        return response()->json([
            'success' => true,
            'message' => 'Member removed from project successfully',
        ]);
    }

    /**
     * Update member role
     * 
     * @group Projects
     * @urlParam id integer required Project ID
     * @urlParam employeeId integer required Employee ID
     * @bodyParam role string required Member role
     * @bodyParam hourly_rate number Member hourly rate
     */
    public function updateMemberRole(Request $request, int $id, int $employeeId): JsonResponse
    {
        $request->validate([
            'role' => 'required|string|max:50',
            'hourly_rate' => 'nullable|numeric|min:0',
        ]);

        $this->projectService->updateProjectMemberRole(
            $id,
            $employeeId,
            $request->role,
            $request->hourly_rate
        );

        return response()->json([
            'success' => true,
            'message' => 'Member role updated successfully',
        ]);
    }

    /**
     * Get project statistics
     * 
     * @group Projects
     * @urlParam id integer required Project ID
     */
    public function statistics(int $id): JsonResponse
    {
        $statistics = $this->projectService->getProjectStatistics($id);

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * Get projects dashboard data
     * 
     * @group Projects
     */
    public function dashboard(Request $request): JsonResponse
    {
        $dashboardData = $this->projectService->getDashboardData($request->user()->activeCompany()->id);

        return response()->json([
            'success' => true,
            'data' => $dashboardData,
        ]);
    }

    /**
     * Archive a project
     * 
     * @group Projects
     * @urlParam id integer required Project ID
     */
    public function archive(int $id): JsonResponse
    {
        $project = $this->projectService->archiveProject($id);

        return response()->json([
            'success' => true,
            'message' => 'Project archived successfully',
            'data' => $project,
        ]);
    }

    /**
     * Duplicate a project
     * 
     * @group Projects
     * @urlParam id integer required Project ID
     * @bodyParam name string required New project name
     */
    public function duplicate(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $project = $this->projectService->duplicateProject($id, $request->name);

        return response()->json([
            'success' => true,
            'message' => 'Project duplicated successfully',
            'data' => $project,
        ], Response::HTTP_CREATED);
    }

    /**
     * Get project board with columns and tasks
     * 
     * @group Projects
     * @urlParam id integer required Project ID
     */
    public function board(Request $request, int $id): JsonResponse
    {
        $project = $this->projectService->getProjectById($id, $request->user()->activeCompany()->id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found',
            ], Response::HTTP_NOT_FOUND);
        }

        // Load board with columns and tasks
        $project->load([
            'board.columns.tasks' => function ($query) {
                $query->with(['assignees', 'tags', 'attachments'])
                    ->orderBy('position');
            },
            'board.columns' => function ($query) {
                $query->orderBy('position');
            }
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'project' => $project,
                'board' => $project->board,
            ],
        ]);
    }
}
