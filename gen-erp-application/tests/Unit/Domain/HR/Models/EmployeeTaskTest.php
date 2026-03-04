<?php

namespace Tests\Unit\Domain\HR\Models;

use Tests\TestCase;
use App\Domain\HR\Models\EmployeeTask;
use App\Domain\HR\Models\Employee;
use App\Domain\Project\Models\Task;
use App\Domain\Project\Models\Project;

class EmployeeTaskTest extends TestCase
{
    public function test_employee_task_has_correct_fillable_attributes()
    {
        $fillable = [
            'employee_id',
            'task_id',
            'project_id',
            'assigned_by',
            'assigned_at',
            'started_at',
            'completed_at',
            'estimated_hours',
            'actual_hours',
            'status',
            'notes'
        ];

        $employeeTask = new EmployeeTask();
        
        $this->assertEquals($fillable, $employeeTask->getFillable());
    }

    public function test_employee_task_has_correct_casts()
    {
        $expectedCasts = [
            'id' => 'int',
            'assigned_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'estimated_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2'
        ];

        $employeeTask = new EmployeeTask();
        
        foreach ($expectedCasts as $attribute => $cast) {
            $this->assertEquals($cast, $employeeTask->getCasts()[$attribute]);
        }
    }

    public function test_employee_task_belongs_to_employee()
    {
        $employeeTask = new EmployeeTask();
        
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $employeeTask->employee()
        );
    }

    public function test_employee_task_belongs_to_task()
    {
        $employeeTask = new EmployeeTask();
        
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $employeeTask->task()
        );
    }

    public function test_employee_task_belongs_to_project()
    {
        $employeeTask = new EmployeeTask();
        
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $employeeTask->project()
        );
    }

    public function test_employee_task_has_progress_attribute()
    {
        $employeeTask = new EmployeeTask([
            'estimated_hours' => 10,
            'actual_hours' => 5
        ]);

        $this->assertEquals(50, $employeeTask->getProgressPercentage());
    }

    public function test_employee_task_progress_is_zero_when_no_estimated_hours()
    {
        $employeeTask = new EmployeeTask([
            'estimated_hours' => 0,
            'actual_hours' => 5
        ]);

        $this->assertEquals(0, $employeeTask->getProgressPercentage());
    }

    public function test_employee_task_progress_is_capped_at_100()
    {
        $employeeTask = new EmployeeTask([
            'estimated_hours' => 5,
            'actual_hours' => 8
        ]);

        $this->assertEquals(100, $employeeTask->getProgressPercentage());
    }

    public function test_employee_task_can_check_if_overdue()
    {
        $employeeTask = new EmployeeTask();
        
        // Mock the task relationship
        $task = new Task(['due_date' => now()->subDays(2)]);
        $employeeTask->setRelation('task', $task);
        
        $this->assertTrue($employeeTask->isOverdue());
    }

    public function test_employee_task_is_not_overdue_when_no_due_date()
    {
        $employeeTask = new EmployeeTask();
        
        // Mock the task relationship
        $task = new Task(['due_date' => null]);
        $employeeTask->setRelation('task', $task);
        
        $this->assertFalse($employeeTask->isOverdue());
    }

    public function test_employee_task_is_not_overdue_when_completed()
    {
        $employeeTask = new EmployeeTask([
            'status' => 'completed',
            'completed_at' => now()
        ]);
        
        // Mock the task relationship
        $task = new Task(['due_date' => now()->subDays(2)]);
        $employeeTask->setRelation('task', $task);
        
        $this->assertFalse($employeeTask->isOverdue());
    }
}