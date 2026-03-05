<?php

namespace Tests\Feature\Domain\HR;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domain\HR\Models\Employee;
use App\Domain\HR\Models\EmployeeTask;
use App\Domain\HR\Models\EmployeeTimeEntry;
use App\Domain\Project\Models\Project;
use App\Domain\Project\Models\Task;
use App\Domain\Auth\Models\User;
use App\Domain\Auth\Models\Company;

class HRApiTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;
    private Employee $employee;
    private Project $project;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Allow all authorization checks in tests
        \Illuminate\Support\Facades\Gate::before(function () {
            return true;
        });
        
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
            'assignee_id' => $this->employee->id,
            'estimated_hours' => 8
        ]);
        
        // Set active company context
        session(['active_company_id' => $this->company->id]);
    }

    public function test_can_get_employee_tasks()
    {
        // Create an employee task
        EmployeeTask::factory()->create([
            'employee_id' => $this->employee->id,
            'task_id' => $this->task->id,
            'project_id' => $this->project->id,
            'status' => 'assigned'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/hr/employees/{$this->employee->id}/tasks");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'employee_id',
                        'task_id',
                        'project_id',
                        'status',
                        'estimated_hours',
                        'actual_hours',
                        'assigned_at',
                        'task',
                        'project'
                    ]
                ]
            ]);
    }

    public function test_can_create_employee_task()
    {
        $taskData = [
            'task_id' => $this->task->id,
            'project_id' => $this->project->id,
            'estimated_hours' => 8,
            'status' => 'assigned'
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/hr/employees/{$this->employee->id}/tasks", $taskData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'employee_id',
                    'task_id',
                    'project_id',
                    'status',
                    'estimated_hours'
                ]
            ]);

        $this->assertDatabaseHas('employee_tasks', [
            'employee_id' => $this->employee->id,
            'task_id' => $this->task->id,
            'status' => 'assigned'
        ]);
    }

    public function test_can_update_employee_task()
    {
        $employeeTask = EmployeeTask::factory()->create([
            'employee_id' => $this->employee->id,
            'task_id' => $this->task->id,
            'project_id' => $this->project->id,
            'status' => 'assigned'
        ]);

        $updateData = [
            'status' => 'in_progress',
            'actual_hours' => 4
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/hr/employees/{$this->employee->id}/tasks/{$employeeTask->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('employee_tasks', [
            'id' => $employeeTask->id,
            'status' => 'in_progress',
            'actual_hours' => 4
        ]);
    }

    public function test_can_get_employee_time_entries()
    {
        // Create time entries
        EmployeeTimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'task_id' => $this->task->id,
            'project_id' => $this->project->id,
            'hours' => 8
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/hr/employees/{$this->employee->id}/time-entries");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'employee_id',
                        'task_id',
                        'project_id',
                        'entry_date',
                        'hours',
                        'description',
                        'entry_type',
                        'is_billable',
                        'task',
                        'project'
                    ]
                ]
            ]);
    }

    public function test_can_create_time_entry()
    {
        $timeData = [
            'task_id' => $this->task->id,
            'project_id' => $this->project->id,
            'entry_date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '17:00',
            'hours' => 8,
            'description' => 'Working on feature development',
            'entry_type' => 'task',
            'is_billable' => true
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/hr/employees/{$this->employee->id}/time-entries", $timeData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'employee_id',
                    'task_id',
                    'project_id',
                    'entry_date',
                    'hours',
                    'description',
                    'entry_type',
                    'is_billable'
                ]
            ]);

        $this->assertDatabaseHas('employee_time_entries', [
            'employee_id' => $this->employee->id,
            'task_id' => $this->task->id,
            'hours' => 8,
            'description' => 'Working on feature development'
        ]);
    }

    public function test_can_get_employee_capacity()
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/hr/employees/{$this->employee->id}/capacity");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'employee_id',
                    'available_hours',
                    'allocated_hours',
                    'remaining_hours',
                    'utilization_percentage',
                    'is_over_capacity'
                ]
            ]);
    }

    public function test_can_update_employee_capacity()
    {
        $capacityData = [
            'available_hours' => 35,
            'is_available_for_projects' => true,
            'overtime_allowed' => false
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/hr/employees/{$this->employee->id}/capacity", $capacityData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Employee capacity updated successfully'
            ]);
    }

    public function test_can_get_weekly_timesheet()
    {
        $weekStart = now()->startOfWeek()->toDateString();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/hr/employees/{$this->employee->id}/time-entries?week_start={$weekStart}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [],
                'meta' => [
                    'week_start',
                    'week_end',
                    'total_hours'
                ]
            ]);
    }

    public function test_requires_authentication()
    {
        $response = $this->getJson("/api/v1/hr/employees/{$this->employee->id}/tasks");

        $response->assertStatus(401);
    }

    public function test_validates_time_entry_data()
    {
        $invalidData = [
            'hours' => 25, // Invalid - more than 24 hours
            'entry_date' => 'invalid-date',
            'entry_type' => 'invalid-type'
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/hr/employees/{$this->employee->id}/time-entries", $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['hours', 'entry_date', 'entry_type']);
    }

    public function test_can_get_task_statistics()
    {
        // Create tasks with different statuses
        EmployeeTask::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => 'assigned'
        ]);

        EmployeeTask::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => 'in_progress'
        ]);

        EmployeeTask::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => 'completed'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/hr/employees/{$this->employee->id}/tasks/statistics");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_tasks',
                    'assigned_tasks',
                    'in_progress_tasks',
                    'completed_tasks',
                    'overdue_tasks',
                    'completion_rate'
                ]
            ]);
    }

    public function test_can_get_time_tracking_summary()
    {
        // Create time entries for different dates
        EmployeeTimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'entry_date' => now()->toDateString(),
            'hours' => 8,
            'is_billable' => true
        ]);

        EmployeeTimeEntry::factory()->create([
            'employee_id' => $this->employee->id,
            'entry_date' => now()->subDay()->toDateString(),
            'hours' => 6,
            'is_billable' => false
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/hr/employees/{$this->employee->id}/time-entries/summary");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_hours',
                    'billable_hours',
                    'non_billable_hours',
                    'entries_count',
                    'average_hours_per_day'
                ]
            ]);
    }
}