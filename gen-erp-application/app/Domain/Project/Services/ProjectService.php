<?php

namespace App\Domain\Project\Services;

use App\Domain\Project\DTOs\CreateProjectData;
use App\Domain\Project\DTOs\UpdateProjectData;
use App\Domain\Project\Models\Project;
use App\Domain\Project\Models\Board;
use App\Domain\HR\Models\Employee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectService
{
    /**
     * Get all projects for a company.
     */
    public function getAllProjects(int $companyId, array $filters = []): LengthAwarePaginator
    {
        $query = Project::where('company_id', $companyId)
            ->with(['projectManager', 'members']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['project_manager_id'])) {
            $query->where('project_manager_id', $filters['project_manager_id']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('client_name', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (isset($filters['overdue']) && $filters['overdue']) {
            $query->overdue();
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get a project by ID.
     */
    public function getProjectById(int $projectId, int $companyId): ?Project
    {
        return Project::where('id', $projectId)
            ->where('company_id', $companyId)
            ->with([
                'projectManager',
                'members',
                'phases',
                'boards.columns',
                'tasks' => function ($query) {
                    $query->whereNull('parent_task_id')->with(['assignee', 'subtasks']);
                }
            ])
            ->first();
    }

    /**
     * Create a new project.
     */
    public function createProject(CreateProjectData $data): Project
    {
        $project = Project::create([
            'company_id' => $data->companyId,
            'name' => $data->name,
            'description' => $data->description,
            'status' => $data->status ?? Project::STATUS_PLANNING,
            'priority' => $data->priority ?? Project::PRIORITY_MEDIUM,
            'start_date' => $data->startDate,
            'end_date' => $data->endDate,
            'budget' => $data->budget,
            'currency' => $data->currency ?? 'USD',
            'client_name' => $data->clientName,
            'client_email' => $data->clientEmail,
            'client_phone' => $data->clientPhone,
            'project_manager_id' => $data->projectManagerId,
            'is_billable' => $data->isBillable ?? true,
            'hourly_rate' => $data->hourlyRate,
            'estimated_hours' => $data->estimatedHours,
            'color' => $data->color,
            'settings' => $data->settings ?? [],
        ]);

        // Create default board
        $this->createDefaultBoard($project);

        // Add project manager as member if specified
        if ($data->projectManagerId) {
            $this->addProjectMember($project->id, $data->projectManagerId, 'lead');
        }

        // Add initial members if specified
        if (!empty($data->memberIds)) {
            foreach ($data->memberIds as $memberId) {
                $this->addProjectMember($project->id, $memberId, 'member');
            }
        }

        return $project->fresh(['projectManager', 'members', 'boards']);
    }

    /**
     * Update a project.
     */
    public function updateProject(int $projectId, UpdateProjectData $data): Project
    {
        $project = Project::findOrFail($projectId);

        $project->update([
            'name' => $data->name ?? $project->name,
            'description' => $data->description ?? $project->description,
            'status' => $data->status ?? $project->status,
            'priority' => $data->priority ?? $project->priority,
            'start_date' => $data->startDate ?? $project->start_date,
            'end_date' => $data->endDate ?? $project->end_date,
            'budget' => $data->budget ?? $project->budget,
            'currency' => $data->currency ?? $project->currency,
            'client_name' => $data->clientName ?? $project->client_name,
            'client_email' => $data->clientEmail ?? $project->client_email,
            'client_phone' => $data->clientPhone ?? $project->client_phone,
            'project_manager_id' => $data->projectManagerId ?? $project->project_manager_id,
            'is_billable' => $data->isBillable ?? $project->is_billable,
            'hourly_rate' => $data->hourlyRate ?? $project->hourly_rate,
            'estimated_hours' => $data->estimatedHours ?? $project->estimated_hours,
            'color' => $data->color ?? $project->color,
            'settings' => array_merge($project->settings ?? [], $data->settings ?? []),
        ]);

        return $project->fresh(['projectManager', 'members']);
    }

    /**
     * Delete a project.
     */
    public function deleteProject(int $projectId): bool
    {
        $project = Project::findOrFail($projectId);
        return $project->delete();
    }

    /**
     * Add a member to a project.
     */
    public function addProjectMember(int $projectId, int $employeeId, string $role = 'member', ?float $hourlyRate = null): void
    {
        $project = Project::findOrFail($projectId);
        
        $project->members()->syncWithoutDetaching([
            $employeeId => [
                'role' => $role,
                'hourly_rate' => $hourlyRate,
                'joined_at' => now(),
            ]
        ]);
    }

    /**
     * Remove a member from a project.
     */
    public function removeProjectMember(int $projectId, int $employeeId): void
    {
        $project = Project::findOrFail($projectId);
        $project->members()->detach($employeeId);
    }

    /**
     * Update project member role.
     */
    public function updateProjectMemberRole(int $projectId, int $employeeId, string $role, ?float $hourlyRate = null): void
    {
        $project = Project::findOrFail($projectId);
        
        $pivotData = ['role' => $role];
        if ($hourlyRate !== null) {
            $pivotData['hourly_rate'] = $hourlyRate;
        }
        
        $project->members()->updateExistingPivot($employeeId, $pivotData);
    }

    /**
     * Get project statistics.
     */
    public function getProjectStatistics(int $projectId): array
    {
        $project = Project::with(['tasks', 'timeEntries'])->findOrFail($projectId);

        $totalTasks = $project->tasks()->count();
        $completedTasks = $project->tasks()->where('status', 'completed')->count();
        $overdueTasks = $project->tasks()->overdue()->count();
        $totalTimeLogged = $project->timeEntries()->sum('hours');
        $budgetUtilization = $project->getBudgetUtilizationPercentage();

        return [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'pending_tasks' => $totalTasks - $completedTasks,
            'overdue_tasks' => $overdueTasks,
            'completion_percentage' => $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0,
            'total_time_logged' => $totalTimeLogged,
            'estimated_hours' => $project->estimated_hours,
            'remaining_hours' => max(0, ($project->estimated_hours ?? 0) - $totalTimeLogged),
            'budget' => $project->budget,
            'budget_utilization' => $budgetUtilization,
            'is_overdue' => $project->isOverdue(),
            'days_remaining' => $project->end_date ? now()->diffInDays($project->end_date, false) : null,
        ];
    }

    /**
     * Get projects dashboard data.
     */
    public function getDashboardData(int $companyId): array
    {
        $totalProjects = Project::where('company_id', $companyId)->count();
        $activeProjects = Project::where('company_id', $companyId)->active()->count();
        $completedProjects = Project::where('company_id', $companyId)->completed()->count();
        $overdueProjects = Project::where('company_id', $companyId)->overdue()->count();

        $recentProjects = Project::where('company_id', $companyId)
            ->with(['projectManager'])
            ->latest()
            ->limit(5)
            ->get();

        $projectsByStatus = Project::where('company_id', $companyId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $projectsByPriority = Project::where('company_id', $companyId)
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        return [
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'completed_projects' => $completedProjects,
            'overdue_projects' => $overdueProjects,
            'recent_projects' => $recentProjects,
            'projects_by_status' => $projectsByStatus,
            'projects_by_priority' => $projectsByPriority,
        ];
    }

    /**
     * Create default board for a project.
     */
    private function createDefaultBoard(Project $project): Board
    {
        $board = $project->boards()->create([
            'name' => 'Main Board',
            'description' => 'Default project board',
            'type' => Board::TYPE_KANBAN,
            'is_default' => true,
        ]);

        $board->createDefaultColumns();

        return $board;
    }

    /**
     * Update project progress based on tasks.
     */
    public function updateProjectProgress(int $projectId): void
    {
        $project = Project::findOrFail($projectId);
        $project->updateProgress();
    }

    /**
     * Get employee's projects.
     */
    public function getEmployeeProjects(int $employeeId, int $companyId): Collection
    {
        return Project::where('company_id', $companyId)
            ->where(function ($query) use ($employeeId) {
                $query->where('project_manager_id', $employeeId)
                      ->orWhereHas('members', function ($q) use ($employeeId) {
                          $q->where('employee_id', $employeeId);
                      });
            })
            ->with(['projectManager', 'members'])
            ->get();
    }

    /**
     * Archive a project.
     */
    public function archiveProject(int $projectId): Project
    {
        $project = Project::findOrFail($projectId);
        $project->update(['status' => Project::STATUS_COMPLETED]);
        
        return $project;
    }

    /**
     * Duplicate a project.
     */
    public function duplicateProject(int $projectId, string $newName): Project
    {
        $originalProject = Project::with(['members', 'boards.columns'])->findOrFail($projectId);
        
        $newProject = $originalProject->replicate();
        $newProject->name = $newName;
        $newProject->status = Project::STATUS_PLANNING;
        $newProject->progress_percentage = 0;
        $newProject->actual_hours = 0;
        $newProject->save();

        // Duplicate members
        foreach ($originalProject->members as $member) {
            $newProject->members()->attach($member->id, [
                'role' => $member->pivot->role,
                'hourly_rate' => $member->pivot->hourly_rate,
                'joined_at' => now(),
            ]);
        }

        // Duplicate boards and columns (but not tasks)
        foreach ($originalProject->boards as $board) {
            $newBoard = $newProject->boards()->create([
                'name' => $board->name,
                'description' => $board->description,
                'type' => $board->type,
                'is_default' => $board->is_default,
                'settings' => $board->settings,
            ]);

            foreach ($board->columns as $column) {
                $newBoard->columns()->create([
                    'name' => $column->name,
                    'description' => $column->description,
                    'color' => $column->color,
                    'position' => $column->position,
                    'wip_limit' => $column->wip_limit,
                    'is_done_column' => $column->is_done_column,
                    'settings' => $column->settings,
                ]);
            }
        }

        return $newProject->fresh(['projectManager', 'members', 'boards']);
    }
}