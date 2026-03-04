<?php

namespace App\Domain\Project\Services;

use App\Domain\Project\DTOs\CreateTaskData;
use App\Domain\Project\DTOs\UpdateTaskData;
use App\Domain\Project\Models\Task;
use App\Domain\Project\Models\Project;
use App\Domain\Project\Models\BoardColumn;
use App\Domain\HR\Models\Employee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskService
{
    /**
     * Get all tasks for a project.
     */
    public function getProjectTasks(int $projectId, array $filters = []): LengthAwarePaginator
    {
        $query = Task::where('project_id', $projectId)
            ->with(['assignee', 'reporter', 'boardColumn', 'parentTask', 'subtasks']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['assignee_id'])) {
            $query->where('assignee_id', $filters['assignee_id']);
        }

        if (isset($filters['board_column_id'])) {
            $query->where('board_column_id', $filters['board_column_id']);
        }

        if (isset($filters['parent_task_id'])) {
            $query->where('parent_task_id', $filters['parent_task_id']);
        } elseif (isset($filters['only_parent_tasks']) && $filters['only_parent_tasks']) {
            $query->whereNull('parent_task_id');
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (isset($filters['overdue']) && $filters['overdue']) {
            $query->overdue();
        }

        if (isset($filters['due_today']) && $filters['due_today']) {
            $query->dueToday();
        }

        if (isset($filters['due_this_week']) && $filters['due_this_week']) {
            $query->dueThisWeek();
        }

        if (isset($filters['tags']) && is_array($filters['tags'])) {
            foreach ($filters['tags'] as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'position';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        
        if ($sortBy === 'position') {
            $query->orderBy('board_column_id')->orderBy('position');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate($filters['per_page'] ?? 50);
    }

    /**
     * Get a task by ID.
     */
    public function getTaskById(int $taskId): ?Task
    {
        return Task::with([
            'project',
            'assignee',
            'reporter',
            'boardColumn',
            'parentTask',
            'subtasks.assignee',
            'comments.user',
            'attachments',
            'checklists.items',
            'dependencies',
            'dependents',
            'watchers',
            'timeEntries'
        ])->find($taskId);
    }

    /**
     * Create a new task.
     */
    public function createTask(CreateTaskData $data): Task
    {
        // Get the project to validate access
        $project = Project::findOrFail($data->projectId);

        // If no board column specified, use the first column of the default board
        if (!$data->boardColumnId && !$data->boardId) {
            $defaultBoard = $project->boards()->where('is_default', true)->first();
            if ($defaultBoard) {
                $firstColumn = $defaultBoard->columns()->orderBy('position')->first();
                $data->boardColumnId = $firstColumn?->id;
            }
        } elseif ($data->boardId && !$data->boardColumnId) {
            $board = $project->boards()->findOrFail($data->boardId);
            $firstColumn = $board->columns()->orderBy('position')->first();
            $data->boardColumnId = $firstColumn?->id;
        }

        // Get next position in the column
        $position = 0;
        if ($data->boardColumnId) {
            $position = Task::where('board_column_id', $data->boardColumnId)->max('position') + 1;
        }

        $task = Task::create([
            'project_id' => $data->projectId,
            'board_id' => $data->boardId,
            'board_column_id' => $data->boardColumnId,
            'parent_task_id' => $data->parentTaskId,
            'title' => $data->title,
            'description' => $data->description,
            'status' => $data->status ?? Task::STATUS_TODO,
            'priority' => $data->priority ?? Task::PRIORITY_MEDIUM,
            'type' => $data->type ?? Task::TYPE_TASK,
            'assignee_id' => $data->assigneeId,
            'reporter_id' => $data->reporterId,
            'start_date' => $data->startDate,
            'due_date' => $data->dueDate,
            'estimated_hours' => $data->estimatedHours,
            'story_points' => $data->storyPoints,
            'position' => $position,
            'tags' => $data->tags ?? [],
            'settings' => $data->settings ?? [],
        ]);

        return $task->fresh(['assignee', 'reporter', 'boardColumn']);
    }

    /**
     * Update a task.
     */
    public function updateTask(int $taskId, UpdateTaskData $data): Task
    {
        $task = Task::findOrFail($taskId);

        $updateData = [];

        if ($data->title !== null) $updateData['title'] = $data->title;
        if ($data->description !== null) $updateData['description'] = $data->description;
        if ($data->status !== null) $updateData['status'] = $data->status;
        if ($data->priority !== null) $updateData['priority'] = $data->priority;
        if ($data->type !== null) $updateData['type'] = $data->type;
        if ($data->assigneeId !== null) $updateData['assignee_id'] = $data->assigneeId;
        if ($data->parentTaskId !== null) $updateData['parent_task_id'] = $data->parentTaskId;
        if ($data->startDate !== null) $updateData['start_date'] = $data->startDate;
        if ($data->dueDate !== null) $updateData['due_date'] = $data->dueDate;
        if ($data->estimatedHours !== null) $updateData['estimated_hours'] = $data->estimatedHours;
        if ($data->storyPoints !== null) $updateData['story_points'] = $data->storyPoints;
        if ($data->position !== null) $updateData['position'] = $data->position;
        if ($data->tags !== null) $updateData['tags'] = $data->tags;
        if ($data->settings !== null) {
            $updateData['settings'] = array_merge($task->settings ?? [], $data->settings);
        }

        // Handle board column change
        if ($data->boardColumnId !== null && $data->boardColumnId !== $task->board_column_id) {
            $updateData['board_column_id'] = $data->boardColumnId;
            // Get next position in the new column
            $updateData['position'] = Task::where('board_column_id', $data->boardColumnId)->max('position') + 1;
        }

        $task->update($updateData);

        return $task->fresh(['assignee', 'reporter', 'boardColumn']);
    }

    /**
     * Delete a task.
     */
    public function deleteTask(int $taskId): bool
    {
        $task = Task::findOrFail($taskId);
        return $task->delete();
    }

    /**
     * Move task to a different column.
     */
    public function moveTaskToColumn(int $taskId, int $columnId, ?int $position = null): Task
    {
        $task = Task::findOrFail($taskId);
        $column = BoardColumn::findOrFail($columnId);

        // Validate that the column belongs to the same project
        if ($column->board->project_id !== $task->project_id) {
            throw new \InvalidArgumentException('Column does not belong to the same project as the task');
        }

        // If no position specified, add to the end
        if ($position === null) {
            $position = Task::where('board_column_id', $columnId)->max('position') + 1;
        }

        $task->update([
            'board_column_id' => $columnId,
            'position' => $position,
        ]);

        // Update task status based on column
        if ($column->is_done_column && $task->status !== Task::STATUS_COMPLETED) {
            $task->update(['status' => Task::STATUS_COMPLETED]);
        }

        return $task->fresh(['boardColumn']);
    }

    /**
     * Assign task to an employee.
     */
    public function assignTask(int $taskId, int $employeeId): Task
    {
        $task = Task::findOrFail($taskId);
        $employee = Employee::findOrFail($employeeId);

        // Validate that the employee belongs to the same company
        if ($employee->company_id !== $task->project->company_id) {
            throw new \InvalidArgumentException('Employee does not belong to the same company as the task');
        }

        $task->update(['assignee_id' => $employeeId]);

        return $task->fresh(['assignee']);
    }

    /**
     * Unassign task.
     */
    public function unassignTask(int $taskId): Task
    {
        $task = Task::findOrFail($taskId);
        $task->update(['assignee_id' => null]);

        return $task->fresh(['assignee']);
    }

    /**
     * Add watcher to task.
     */
    public function addWatcher(int $taskId, int $employeeId): void
    {
        $task = Task::findOrFail($taskId);
        $employee = Employee::findOrFail($employeeId);

        // Validate that the employee belongs to the same company
        if ($employee->company_id !== $task->project->company_id) {
            throw new \InvalidArgumentException('Employee does not belong to the same company as the task');
        }

        $task->watchers()->syncWithoutDetaching([$employeeId]);
    }

    /**
     * Remove watcher from task.
     */
    public function removeWatcher(int $taskId, int $employeeId): void
    {
        $task = Task::findOrFail($taskId);
        $task->watchers()->detach($employeeId);
    }

    /**
     * Get task statistics for a project.
     */
    public function getProjectTaskStatistics(int $projectId): array
    {
        $totalTasks = Task::where('project_id', $projectId)->count();
        $completedTasks = Task::where('project_id', $projectId)->where('status', Task::STATUS_COMPLETED)->count();
        $inProgressTasks = Task::where('project_id', $projectId)->where('status', Task::STATUS_IN_PROGRESS)->count();
        $overdueTasks = Task::where('project_id', $projectId)->overdue()->count();
        $dueTodayTasks = Task::where('project_id', $projectId)->dueToday()->count();
        $dueThisWeekTasks = Task::where('project_id', $projectId)->dueThisWeek()->count();

        $tasksByStatus = Task::where('project_id', $projectId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $tasksByPriority = Task::where('project_id', $projectId)
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        $tasksByType = Task::where('project_id', $projectId)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        $tasksByAssignee = Task::where('project_id', $projectId)
            ->whereNotNull('assignee_id')
            ->with('assignee:id,first_name,last_name')
            ->get()
            ->groupBy('assignee_id')
            ->map(function ($tasks) {
                return [
                    'assignee' => $tasks->first()->assignee,
                    'count' => $tasks->count(),
                    'completed' => $tasks->where('status', Task::STATUS_COMPLETED)->count(),
                ];
            })
            ->values()
            ->toArray();

        return [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'in_progress_tasks' => $inProgressTasks,
            'pending_tasks' => $totalTasks - $completedTasks - $inProgressTasks,
            'overdue_tasks' => $overdueTasks,
            'due_today_tasks' => $dueTodayTasks,
            'due_this_week_tasks' => $dueThisWeekTasks,
            'completion_percentage' => $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0,
            'tasks_by_status' => $tasksByStatus,
            'tasks_by_priority' => $tasksByPriority,
            'tasks_by_type' => $tasksByType,
            'tasks_by_assignee' => $tasksByAssignee,
        ];
    }

    /**
     * Get employee's tasks across all projects.
     */
    public function getEmployeeTasks(int $employeeId, int $companyId, array $filters = []): LengthAwarePaginator
    {
        $query = Task::whereHas('project', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->where('assignee_id', $employeeId)
            ->with(['project', 'boardColumn', 'parentTask']);

        // Apply filters (same as getProjectTasks)
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (isset($filters['overdue']) && $filters['overdue']) {
            $query->overdue();
        }

        if (isset($filters['due_today']) && $filters['due_today']) {
            $query->dueToday();
        }

        if (isset($filters['due_this_week']) && $filters['due_this_week']) {
            $query->dueThisWeek();
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'due_date';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Bulk update task positions (for drag and drop).
     */
    public function bulkUpdateTaskPositions(array $taskPositions): void
    {
        foreach ($taskPositions as $taskPosition) {
            Task::where('id', $taskPosition['task_id'])
                ->update([
                    'board_column_id' => $taskPosition['board_column_id'],
                    'position' => $taskPosition['position'],
                ]);
        }
    }

    /**
     * Create subtask.
     */
    public function createSubtask(int $parentTaskId, CreateTaskData $data): Task
    {
        $parentTask = Task::findOrFail($parentTaskId);
        
        $data->projectId = $parentTask->project_id;
        $data->parentTaskId = $parentTaskId;
        $data->boardId = $parentTask->board_id;
        $data->boardColumnId = $parentTask->board_column_id;

        return $this->createTask($data);
    }

    /**
     * Get task hierarchy (parent with all subtasks).
     */
    public function getTaskHierarchy(int $taskId): Task
    {
        return Task::with(['subtasks' => function ($query) {
            $query->with(['subtasks', 'assignee'])->orderBy('position');
        }, 'assignee', 'parentTask'])
        ->findOrFail($taskId);
    }
}