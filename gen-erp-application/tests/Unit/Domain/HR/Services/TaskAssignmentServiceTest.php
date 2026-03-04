<?php

namespace Tests\Unit\Domain\HR\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domain\HR\Services\TaskAssignmentService;
use App\Domain\HR\Models\Employee;
use App\Domain\HR\Models\EmployeeTask;
use App\Domain\Project\Models\Project;
use App\Domain\Project\Models\Task;
use App\Domain\Auth\Models\User;
use App\Domain\Auth\Models\Company;

class TaskAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaskAssignmentService $service;
    private Company $company;
    private User $user;
    private Employee $employee;
    private Project $project;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(TaskAssignmentService::class);
        
        // Create test data
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, [
            'role' => 'admin',
            'is_owner' => true,
            'is_active' => true
        ]);
        
        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'is_available_for_projects' => true,
            'weekly_capacity_hours' => 40
        ]);
        
        $this->project = Project::factory()->create([
            'company_id' => $this->company->id
        ]);
        
        $this->task = Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => null, // Don't set assignee_id as it should reference employees table
            'estimated_hours' => 8
        ]);
        
        // Set active company context
        session(['active_company_id' => $this->company->id]);
    }

    public function test_can_assign_task_to_employee()
    {
        $result = $this->service->assignTask($this->employee, $this->task, $this->user);

        $this->assertInstanceOf(EmployeeTask::class, $result);
        $this->assertEquals($this->employee->id, $result->employee_id);
        $this->assertEquals($this->task->id, $result->task_id);
        $this->assertEquals($this->project->id, $result->project_id);
        $this->assertEquals('assigned', $result->status);
        $this->assertEquals(8, $result->estimated_hours);
    }

    public function test_can_get_employee_tasks()
    {
        // Create multiple tasks
        $task2 = Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $this->employee->id
        ]);

        $this->service->assignTask($this->employee, $this->task, $this->user);
        $this->service->assignTask($this->employee, $task2, $this->user);

        $tasks = $this->service->getEmployeeTasks($this->employee);

        $this->assertCount(2, $tasks);
        $this->assertEquals($this->task->id, $tasks->first()->task_id);
    }

    public function test_can_update_task_status()
    {
        $employeeTask = $this->service->assignTask($this->employee, $this->task, $this->user);

        $result = $this->service->updateTaskStatus($employeeTask, 'in_progress');

        $this->assertTrue($result);
        $this->assertEquals('in_progress', $employeeTask->fresh()->status);
        $this->assertNotNull($employeeTask->fresh()->started_at);
    }

    public function test_can_complete_task()
    {
        $employeeTask = $this->service->assignTask($this->employee, $this->task, $this->user);

        $result = $this->service->completeTask($employeeTask);

        $this->assertInstanceOf(EmployeeTask::class, $result);
        $this->assertEquals('completed', $employeeTask->fresh()->status);
        $this->assertNotNull($employeeTask->fresh()->completed_at);
    }

    public function test_can_get_task_statistics()
    {
        // Create tasks with different statuses
        $task2 = Task::factory()->create(['project_id' => $this->project->id, 'assignee_id' => $this->employee->id]);
        $task3 = Task::factory()->create(['project_id' => $this->project->id, 'assignee_id' => $this->employee->id]);

        $employeeTask1 = $this->service->assignTask($this->employee, $this->task, $this->user);
        $employeeTask2 = $this->service->assignTask($this->employee, $task2, $this->user);
        $employeeTask3 = $this->service->assignTask($this->employee, $task3, $this->user);

        $this->service->updateTaskStatus($employeeTask2, 'in_progress');
        $this->service->completeTask($employeeTask3);

        $stats = $this->service->getEmployeeTaskStatistics($this->employee);

        $this->assertEquals(3, $stats['total_tasks']);
        $this->assertEquals(1, $stats['assigned_tasks']);
        $this->assertEquals(1, $stats['in_progress_tasks']);
        $this->assertEquals(1, $stats['completed_tasks']);
    }

    public function test_cannot_assign_task_to_unavailable_employee()
    {
        $this->employee->update(['is_available_for_projects' => false]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Employee is not available for project assignments');

        $this->service->assignTask($this->employee, $this->task, $this->user);
    }

    public function test_can_get_overdue_tasks()
    {
        $overdueTask = Task::factory()->create([
            'project_id' => $this->project->id,
            'assignee_id' => $this->employee->id,
            'due_date' => now()->subDays(2)
        ]);

        $this->service->assignTask($this->employee, $overdueTask, $this->user);

        $overdueTasks = $this->service->getOverdueTasks($this->company);

        $this->assertCount(1, $overdueTasks);
        $this->assertEquals($overdueTask->id, $overdueTasks->first()->task_id);
    }
}