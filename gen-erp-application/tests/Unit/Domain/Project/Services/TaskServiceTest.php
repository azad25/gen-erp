<?php

namespace Tests\Unit\Domain\Project\Services;

use Tests\TestCase;
use App\Domain\Project\Services\TaskService;
use App\Domain\Project\DTOs\CreateTaskData;
use App\Domain\Project\DTOs\UpdateTaskData;
use App\Domain\Project\Models\Task;
use App\Domain\Project\Models\Project;
use App\Domain\Project\Models\Board;
use App\Domain\Project\Models\BoardColumn;
use App\Domain\HR\Models\Employee;
use App\Domain\Auth\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaskService $taskService;
    private Company $company;
    private Employee $employee;
    private Project $project;
    private Board $board;
    private BoardColumn $column;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->taskService = new TaskService();
        $this->company = Company::factory()->create();
        $this->employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $this->project = Project::factory()->create(['company_id' => $this->company->id]);
        $this->board = Board::factory()->create(['project_id' => $this->project->id]);
        $this->column = BoardColumn::factory()->create(['board_id' => $this->board->id]);
    }

    public function test_get_project_tasks_returns_paginated_results()
    {
        // Arrange
        Task::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id
        ]);

        // Act
        $result = $this->taskService->getProjectTasks($this->project->id);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(3, $result->total());
    }

    public function test_get_project_tasks_filters_by_status()
    {
        // Arrange
        Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id,
            'status' => Task::STATUS_TODO
        ]);
        Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id,
            'status' => Task::STATUS_COMPLETED
        ]);

        // Act
        $result = $this->taskService->getProjectTasks($this->project->id, ['status' => Task::STATUS_TODO]);

        // Assert
        $this->assertEquals(1, $result->total());
        $this->assertEquals(Task::STATUS_TODO, $result->items()[0]->status);
    }

    public function test_get_project_tasks_filters_by_assignee()
    {
        // Arrange
        $assignee = Employee::factory()->create(['company_id' => $this->company->id]);
        Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id,
            'assignee_id' => $assignee->id
        ]);
        Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id,
            'assignee_id' => null
        ]);

        // Act
        $result = $this->taskService->getProjectTasks($this->project->id, ['assignee_id' => $assignee->id]);

        // Assert
        $this->assertEquals(1, $result->total());
        $this->assertEquals($assignee->id, $result->items()[0]->assignee_id);
    }

    public function test_get_project_tasks_filters_by_search()
    {
        // Arrange
        Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id,
            'title' => 'Test Task'
        ]);
        Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id,
            'title' => 'Another Task'
        ]);

        // Act
        $result = $this->taskService->getProjectTasks($this->project->id, ['search' => 'Test']);

        // Assert
        $this->assertEquals(1, $result->total());
        $this->assertStringContainsString('Test', $result->items()[0]->title);
    }

    public function test_get_task_by_id_returns_task_with_relationships()
    {
        // Arrange
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id
        ]);

        // Act
        $result = $this->taskService->getTaskById($task->id);

        // Assert
        $this->assertInstanceOf(Task::class, $result);
        $this->assertEquals($task->id, $result->id);
        $this->assertTrue($result->relationLoaded('project'));
        $this->assertTrue($result->relationLoaded('assignee'));
        $this->assertTrue($result->relationLoaded('boardColumn'));
    }

    public function test_create_task_creates_task_with_default_column()
    {
        // Arrange
        $this->board->update(['is_default' => true]);
        $data = new CreateTaskData(
            projectId: $this->project->id,
            title: 'Test Task',
            description: 'Test Description'
        );

        // Act
        $result = $this->taskService->createTask($data);

        // Assert
        $this->assertInstanceOf(Task::class, $result);
        $this->assertEquals('Test Task', $result->title);
        $this->assertEquals('Test Description', $result->description);
        $this->assertEquals($this->column->id, $result->board_column_id);
        $this->assertEquals(Task::STATUS_TODO, $result->status);
    }

    public function test_create_task_sets_correct_position()
    {
        // Arrange
        Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id,
            'position' => 1
        ]);
        
        $data = new CreateTaskData(
            projectId: $this->project->id,
            title: 'Test Task',
            boardColumnId: $this->column->id
        );

        // Act
        $result = $this->taskService->createTask($data);

        // Assert
        $this->assertEquals(2, $result->position);
    }

    public function test_update_task_updates_fields()
    {
        // Arrange
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id
        ]);
        $data = new UpdateTaskData(
            title: 'Updated Title',
            description: 'Updated Description',
            status: Task::STATUS_IN_PROGRESS
        );

        // Act
        $result = $this->taskService->updateTask($task->id, $data);

        // Assert
        $this->assertEquals('Updated Title', $result->title);
        $this->assertEquals('Updated Description', $result->description);
        $this->assertEquals(Task::STATUS_IN_PROGRESS, $result->status);
    }

    public function test_update_task_changes_column_and_position()
    {
        // Arrange
        $newColumn = BoardColumn::factory()->create(['board_id' => $this->board->id]);
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id,
            'position' => 1
        ]);
        
        $data = new UpdateTaskData(boardColumnId: $newColumn->id);

        // Act
        $result = $this->taskService->updateTask($task->id, $data);

        // Assert
        $this->assertEquals($newColumn->id, $result->board_column_id);
        $this->assertEquals(1, $result->position); // First task in new column
    }

    public function test_delete_task_removes_task()
    {
        // Arrange
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id
        ]);

        // Act
        $result = $this->taskService->deleteTask($task->id);

        // Assert
        $this->assertTrue($result);
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_move_task_to_column_updates_column_and_position()
    {
        // Arrange
        $newColumn = BoardColumn::factory()->create(['board_id' => $this->board->id]);
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id
        ]);

        // Act
        $result = $this->taskService->moveTaskToColumn($task->id, $newColumn->id, 2);

        // Assert
        $this->assertEquals($newColumn->id, $result->board_column_id);
        $this->assertEquals(2, $result->position);
    }

    public function test_move_task_to_done_column_completes_task()
    {
        // Arrange
        $doneColumn = BoardColumn::factory()->create([
            'board_id' => $this->board->id,
            'is_done_column' => true
        ]);
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id,
            'status' => Task::STATUS_IN_PROGRESS
        ]);

        // Act
        $result = $this->taskService->moveTaskToColumn($task->id, $doneColumn->id);

        // Assert
        $this->assertEquals($doneColumn->id, $result->board_column_id);
        $this->assertEquals(Task::STATUS_COMPLETED, $result->fresh()->status);
    }

    public function test_move_task_to_column_throws_exception_for_different_project()
    {
        // Arrange
        $otherProject = Project::factory()->create(['company_id' => $this->company->id]);
        $otherBoard = Board::factory()->create(['project_id' => $otherProject->id]);
        $otherColumn = BoardColumn::factory()->create(['board_id' => $otherBoard->id]);
        
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id
        ]);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->taskService->moveTaskToColumn($task->id, $otherColumn->id);
    }

    public function test_assign_task_assigns_employee()
    {
        // Arrange
        $assignee = Employee::factory()->create(['company_id' => $this->company->id]);
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id
        ]);

        // Act
        $result = $this->taskService->assignTask($task->id, $assignee->id);

        // Assert
        $this->assertEquals($assignee->id, $result->assignee_id);
    }

    public function test_assign_task_throws_exception_for_different_company()
    {
        // Arrange
        $otherCompany = Company::factory()->create();
        $otherEmployee = Employee::factory()->create(['company_id' => $otherCompany->id]);
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id
        ]);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->taskService->assignTask($task->id, $otherEmployee->id);
    }

    public function test_unassign_task_removes_assignee()
    {
        // Arrange
        $assignee = Employee::factory()->create(['company_id' => $this->company->id]);
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id,
            'assignee_id' => $assignee->id
        ]);

        // Act
        $result = $this->taskService->unassignTask($task->id);

        // Assert
        $this->assertNull($result->assignee_id);
    }

    public function test_add_watcher_adds_employee_as_watcher()
    {
        // Arrange
        $watcher = Employee::factory()->create(['company_id' => $this->company->id]);
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id
        ]);

        // Act
        $this->taskService->addWatcher($task->id, $watcher->id);

        // Assert
        $task->refresh();
        $this->assertTrue($task->watchers->contains($watcher));
    }

    public function test_remove_watcher_removes_employee_from_watchers()
    {
        // Arrange
        $watcher = Employee::factory()->create(['company_id' => $this->company->id]);
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id
        ]);
        $task->watchers()->attach($watcher->id);

        // Act
        $this->taskService->removeWatcher($task->id, $watcher->id);

        // Assert
        $task->refresh();
        $this->assertFalse($task->watchers->contains($watcher));
    }

    public function test_get_project_task_statistics_returns_correct_data()
    {
        // Arrange
        Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id,
            'status' => Task::STATUS_TODO
        ]);
        Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id,
            'status' => Task::STATUS_COMPLETED
        ]);

        // Act
        $result = $this->taskService->getProjectTaskStatistics($this->project->id);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals(2, $result['total_tasks']);
        $this->assertEquals(1, $result['completed_tasks']);
        $this->assertEquals(1, $result['pending_tasks']);
        $this->assertEquals(50, $result['completion_percentage']);
        $this->assertArrayHasKey('tasks_by_status', $result);
        $this->assertArrayHasKey('tasks_by_priority', $result);
    }

    public function test_get_employee_tasks_returns_assigned_tasks()
    {
        // Arrange
        $assignee = Employee::factory()->create(['company_id' => $this->company->id]);
        Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id,
            'assignee_id' => $assignee->id
        ]);
        Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id,
            'assignee_id' => null
        ]);

        // Act
        $result = $this->taskService->getEmployeeTasks($assignee->id, $this->company->id);

        // Assert
        $this->assertEquals(1, $result->total());
        $this->assertEquals($assignee->id, $result->items()[0]->assignee_id);
    }

    public function test_bulk_update_task_positions_updates_multiple_tasks()
    {
        // Arrange
        $task1 = Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id,
            'position' => 1
        ]);
        $task2 = Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id,
            'position' => 2
        ]);

        $taskPositions = [
            ['task_id' => $task1->id, 'board_column_id' => $this->column->id, 'position' => 2],
            ['task_id' => $task2->id, 'board_column_id' => $this->column->id, 'position' => 1],
        ];

        // Act
        $this->taskService->bulkUpdateTaskPositions($taskPositions);

        // Assert
        $task1->refresh();
        $task2->refresh();
        $this->assertEquals(2, $task1->position);
        $this->assertEquals(1, $task2->position);
    }

    public function test_create_subtask_creates_task_under_parent()
    {
        // Arrange
        $parentTask = Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id
        ]);
        
        $data = new CreateTaskData(
            projectId: 0, // Will be overridden
            title: 'Subtask',
            description: 'Subtask Description'
        );

        // Act
        $result = $this->taskService->createSubtask($parentTask->id, $data);

        // Assert
        $this->assertEquals('Subtask', $result->title);
        $this->assertEquals($parentTask->id, $result->parent_task_id);
        $this->assertEquals($parentTask->project_id, $result->project_id);
        $this->assertEquals($parentTask->board_column_id, $result->board_column_id);
    }

    public function test_get_task_hierarchy_returns_task_with_subtasks()
    {
        // Arrange
        $parentTask = Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id
        ]);
        $subtask = Task::factory()->create([
            'project_id' => $this->project->id,
            'board_column_id' => $this->column->id,
            'parent_task_id' => $parentTask->id
        ]);

        // Act
        $result = $this->taskService->getTaskHierarchy($parentTask->id);

        // Assert
        $this->assertEquals($parentTask->id, $result->id);
        $this->assertTrue($result->relationLoaded('subtasks'));
        $this->assertEquals(1, $result->subtasks->count());
        $this->assertEquals($subtask->id, $result->subtasks->first()->id);
    }
}