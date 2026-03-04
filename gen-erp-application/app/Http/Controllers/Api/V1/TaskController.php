<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Project\DTOs\CreateTaskData;
use App\Domain\Project\DTOs\UpdateTaskData;
use App\Domain\Project\Services\TaskService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @tags Tasks
 */
class TaskController extends Controller
{
    public function __construct(
        private TaskService $taskService
    ) {}

    /**
     * Get project tasks
     * 
     * @group Tasks
     * @urlParam projectId integer required Project ID
     * @queryParam status string Filter by status
     * @queryParam priority string Filter by priority
     * @queryParam type string Filter by type
     * @queryParam assignee_id integer Filter by assignee
     * @queryParam board_column_id integer Filter by board column
     * @queryParam parent_task_id integer Filter by parent task
     * @queryParam only_parent_tasks boolean Show only parent tasks
     * @queryParam search string Search in title and description
     * @queryParam overdue boolean Filter overdue tasks
     * @queryParam due_today boolean Filter tasks due today
     * @queryParam due_this_week boolean Filter tasks due this week
     * @queryParam tags array Filter by tags
     * @queryParam sort_by string Sort by field (default: position)
     * @queryParam sort_order string Sort order (default: asc)
     * @queryParam per_page integer Items per page (default: 50)
     */
    public function index(Request $request, int $projectId): JsonResponse
    {
        $filters = $request->only([
            'status', 'priority', 'type', 'assignee_id', 'board_column_id',
            'parent_task_id', 'only_parent_tasks', 'search', 'overdue',
            'due_today', 'due_this_week', 'tags', 'sort_by', 'sort_order', 'per_page'
        ]);

        $tasks = $this->taskService->getProjectTasks($projectId, $filters);

        return response()->json([
            'success' => true,
            'data' => $tasks,
        ]);
    }

    /**
     * Get task details
     * 
     * @group Tasks
     * @urlParam id integer required Task ID
     */
    public function show(int $id): JsonResponse
    {
        $task = $this->taskService->getTaskById($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => $task,
        ]);
    }

    /**
     * Create a new task
     * 
     * @group Tasks
     * @bodyParam project_id integer required Project ID
     * @bodyParam title string required Task title
     * @bodyParam description string Task description
     * @bodyParam status string Task status
     * @bodyParam priority string Task priority
     * @bodyParam type string Task type
     * @bodyParam assignee_id integer Assignee employee ID
     * @bodyParam reporter_id integer Reporter user ID
     * @bodyParam board_id integer Board ID
     * @bodyParam board_column_id integer Board column ID
     * @bodyParam parent_task_id integer Parent task ID (for subtasks)
     * @bodyParam start_date string Start date (Y-m-d format)
     * @bodyParam due_date string Due date (Y-m-d format)
     * @bodyParam estimated_hours number Estimated hours
     * @bodyParam story_points integer Story points
     * @bodyParam tags array Task tags
     * @bodyParam settings object Additional settings
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:todo,in_progress,in_review,testing,completed,cancelled',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'type' => 'nullable|in:task,bug,feature,improvement,epic,story',
            'assignee_id' => 'nullable|exists:employees,id',
            'reporter_id' => 'nullable|exists:users,id',
            'board_id' => 'nullable|exists:boards,id',
            'board_column_id' => 'nullable|exists:board_columns,id',
            'parent_task_id' => 'nullable|exists:tasks,id',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'story_points' => 'nullable|integer|min:0',
            'tags' => 'nullable|array',
            'settings' => 'nullable|array',
        ]);

        $data = CreateTaskData::fromArray($request->all());
        $task = $this->taskService->createTask($data);

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully',
            'data' => $task,
        ], Response::HTTP_CREATED);
    }

    /**
     * Update a task
     * 
     * @group Tasks
     * @urlParam id integer required Task ID
     * @bodyParam title string Task title
     * @bodyParam description string Task description
     * @bodyParam status string Task status
     * @bodyParam priority string Task priority
     * @bodyParam type string Task type
     * @bodyParam assignee_id integer Assignee employee ID
     * @bodyParam board_column_id integer Board column ID
     * @bodyParam parent_task_id integer Parent task ID
     * @bodyParam start_date string Start date
     * @bodyParam due_date string Due date
     * @bodyParam estimated_hours number Estimated hours
     * @bodyParam story_points integer Story points
     * @bodyParam position integer Position in column
     * @bodyParam tags array Task tags
     * @bodyParam settings object Additional settings
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:todo,in_progress,in_review,testing,completed,cancelled',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'type' => 'sometimes|in:task,bug,feature,improvement,epic,story',
            'assignee_id' => 'nullable|exists:employees,id',
            'board_column_id' => 'sometimes|exists:board_columns,id',
            'parent_task_id' => 'nullable|exists:tasks,id',
            'start_date' => 'sometimes|date',
            'due_date' => 'sometimes|date|after_or_equal:start_date',
            'estimated_hours' => 'sometimes|numeric|min:0',
            'story_points' => 'sometimes|integer|min:0',
            'position' => 'sometimes|integer|min:0',
            'tags' => 'sometimes|array',
            'settings' => 'sometimes|array',
        ]);

        $data = UpdateTaskData::fromArray($request->all());
        $task = $this->taskService->updateTask($id, $data);

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully',
            'data' => $task,
        ]);
    }

    /**
     * Delete a task
     * 
     * @group Tasks
     * @urlParam id integer required Task ID
     */
    public function destroy(int $id): JsonResponse
    {
        $this->taskService->deleteTask($id);

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully',
        ]);
    }

    /**
     * Move task to column
     * 
     * @group Tasks
     * @urlParam id integer required Task ID
     * @bodyParam board_column_id integer required Board column ID
     * @bodyParam position integer Position in column
     */
    public function move(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'board_column_id' => 'required|exists:board_columns,id',
            'position' => 'nullable|integer|min:0',
        ]);

        $task = $this->taskService->moveTaskToColumn(
            $id,
            $request->board_column_id,
            $request->position
        );

        return response()->json([
            'success' => true,
            'message' => 'Task moved successfully',
            'data' => $task,
        ]);
    }

    /**
     * Assign task to employee
     * 
     * @group Tasks
     * @urlParam id integer required Task ID
     * @bodyParam employee_id integer required Employee ID
     */
    public function assign(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        $task = $this->taskService->assignTask($id, $request->employee_id);

        return response()->json([
            'success' => true,
            'message' => 'Task assigned successfully',
            'data' => $task,
        ]);
    }

    /**
     * Unassign task
     * 
     * @group Tasks
     * @urlParam id integer required Task ID
     */
    public function unassign(int $id): JsonResponse
    {
        $task = $this->taskService->unassignTask($id);

        return response()->json([
            'success' => true,
            'message' => 'Task unassigned successfully',
            'data' => $task,
        ]);
    }

    /**
     * Add watcher to task
     * 
     * @group Tasks
     * @urlParam id integer required Task ID
     * @bodyParam employee_id integer required Employee ID
     */
    public function addWatcher(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        $this->taskService->addWatcher($id, $request->employee_id);

        return response()->json([
            'success' => true,
            'message' => 'Watcher added successfully',
        ]);
    }

    /**
     * Remove watcher from task
     * 
     * @group Tasks
     * @urlParam id integer required Task ID
     * @urlParam employeeId integer required Employee ID
     */
    public function removeWatcher(int $id, int $employeeId): JsonResponse
    {
        $this->taskService->removeWatcher($id, $employeeId);

        return response()->json([
            'success' => true,
            'message' => 'Watcher removed successfully',
        ]);
    }

    /**
     * Get project task statistics
     * 
     * @group Tasks
     * @urlParam projectId integer required Project ID
     */
    public function statistics(int $projectId): JsonResponse
    {
        $statistics = $this->taskService->getProjectTaskStatistics($projectId);

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * Get employee tasks
     * 
     * @group Tasks
     * @urlParam employeeId integer required Employee ID
     * @queryParam status string Filter by status
     * @queryParam priority string Filter by priority
     * @queryParam project_id integer Filter by project
     * @queryParam overdue boolean Filter overdue tasks
     * @queryParam due_today boolean Filter tasks due today
     * @queryParam due_this_week boolean Filter tasks due this week
     * @queryParam sort_by string Sort by field (default: due_date)
     * @queryParam sort_order string Sort order (default: asc)
     * @queryParam per_page integer Items per page (default: 20)
     */
    public function employeeTasks(Request $request, int $employeeId): JsonResponse
    {
        $filters = $request->only([
            'status', 'priority', 'project_id', 'overdue', 'due_today',
            'due_this_week', 'sort_by', 'sort_order', 'per_page'
        ]);

        $tasks = $this->taskService->getEmployeeTasks(
            $employeeId,
            $request->user()->company_id,
            $filters
        );

        return response()->json([
            'success' => true,
            'data' => $tasks,
        ]);
    }

    /**
     * Bulk update task positions
     * 
     * @group Tasks
     * @bodyParam tasks array required Array of task position updates
     * @bodyParam tasks.*.task_id integer required Task ID
     * @bodyParam tasks.*.board_column_id integer required Board column ID
     * @bodyParam tasks.*.position integer required Position in column
     */
    public function bulkUpdatePositions(Request $request): JsonResponse
    {
        $request->validate([
            'tasks' => 'required|array',
            'tasks.*.task_id' => 'required|exists:tasks,id',
            'tasks.*.board_column_id' => 'required|exists:board_columns,id',
            'tasks.*.position' => 'required|integer|min:0',
        ]);

        $this->taskService->bulkUpdateTaskPositions($request->tasks);

        return response()->json([
            'success' => true,
            'message' => 'Task positions updated successfully',
        ]);
    }

    /**
     * Create subtask
     * 
     * @group Tasks
     * @urlParam parentId integer required Parent task ID
     * @bodyParam title string required Subtask title
     * @bodyParam description string Subtask description
     * @bodyParam assignee_id integer Assignee employee ID
     * @bodyParam due_date string Due date
     * @bodyParam estimated_hours number Estimated hours
     */
    public function createSubtask(Request $request, int $parentId): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assignee_id' => 'nullable|exists:employees,id',
            'due_date' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0',
        ]);

        $data = CreateTaskData::fromArray($request->all());
        $subtask = $this->taskService->createSubtask($parentId, $data);

        return response()->json([
            'success' => true,
            'message' => 'Subtask created successfully',
            'data' => $subtask,
        ], Response::HTTP_CREATED);
    }

    /**
     * Get task hierarchy
     * 
     * @group Tasks
     * @urlParam id integer required Task ID
     */
    public function hierarchy(int $id): JsonResponse
    {
        $task = $this->taskService->getTaskHierarchy($id);

        return response()->json([
            'success' => true,
            'data' => $task,
        ]);
    }
}