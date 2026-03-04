<?php

namespace Tests\Unit\Domain\HR\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domain\HR\Services\TimeTrackingService;
use App\Domain\HR\Models\Employee;
use App\Domain\HR\Models\EmployeeTimeEntry;
use App\Domain\HR\DTOs\LogTimeData;
use App\Domain\Project\Models\Project;
use App\Domain\Project\Models\Task;
use App\Domain\Auth\Models\User;
use App\Domain\Auth\Models\Company;

class TimeTrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    private TimeTrackingService $service;
    private Company $company;
    private User $user;
    private Employee $employee;
    private Project $project;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(TimeTrackingService::class);
        
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
            'user_id' => $this->user->id
        ]);
        
        $this->project = Project::factory()->create([
            'company_id' => $this->company->id
        ]);
        
        $this->task = Task::factory()->create([
            'project_id' => $this->project->id
        ]);
        
        // Set active company context
        session(['active_company_id' => $this->company->id]);
    }

    public function test_can_log_time_entry()
    {
        $timeData = new LogTimeData(
            employeeId: $this->employee->id,
            taskId: $this->task->id,
            projectId: $this->project->id,
            entryDate: now(),
            startTime: '09:00',
            endTime: '17:00',
            hours: 8.0,
            description: 'Working on feature development',
            entryType: 'task',
            isBillable: true
        );

        $result = $this->service->logTime($timeData);

        $this->assertInstanceOf(EmployeeTimeEntry::class, $result);
        $this->assertEquals($this->employee->id, $result->employee_id);
        $this->assertEquals($this->task->id, $result->task_id);
        $this->assertEquals(8.0, $result->hours);
        $this->assertEquals('task', $result->entry_type);
        $this->assertTrue($result->is_billable);
    }

    public function test_can_get_employee_time_entries()
    {
        // Create multiple time entries
        $timeData1 = new LogTimeData(
            employeeId: $this->employee->id,
            entryDate: now(),
            startTime: '09:00',
            endTime: '13:00',
            hours: 4.0,
            taskId: $this->task->id,
            projectId: $this->project->id,
            description: 'Morning work'
        );

        $timeData2 = new LogTimeData(
            employeeId: $this->employee->id,
            entryDate: now(),
            startTime: '14:00',
            endTime: '18:00',
            hours: 4.0,
            taskId: $this->task->id,
            projectId: $this->project->id,
            description: 'Afternoon work'
        );

        $this->service->logTime($timeData1);
        $this->service->logTime($timeData2);

        $entries = $this->service->getEmployeeTimeEntries($this->employee);

        $this->assertCount(2, $entries);
        $this->assertEquals(8.0, $entries->sum('hours'));
    }

    public function test_can_get_time_entries_for_date_range()
    {
        $startDate = now()->startOfWeek();
        $endDate = now()->endOfWeek();

        // Create entries for different dates
        $timeData1 = new LogTimeData(
            employeeId: $this->employee->id,
            entryDate: $startDate,
            startTime: '09:00',
            endTime: '17:00',
            hours: 8.0,
            taskId: $this->task->id,
            projectId: $this->project->id,
            description: 'Monday work'
        );

        $timeData2 = new LogTimeData(
            employeeId: $this->employee->id,
            entryDate: $startDate->copy()->addDays(2),
            startTime: '09:00',
            endTime: '15:00',
            hours: 6.0,
            taskId: $this->task->id,
            projectId: $this->project->id,
            description: 'Wednesday work'
        );

        $this->service->logTime($timeData1);
        $this->service->logTime($timeData2);

        $entries = $this->service->getTimeEntriesForDateRange(
            $this->employee,
            $startDate,
            $endDate
        );

        $this->assertCount(2, $entries);
        $this->assertEquals(14.0, $entries->sum('hours'));
    }

    public function test_can_get_weekly_timesheet()
    {
        $weekStart = now()->startOfWeek();

        // Create entries for the week
        for ($i = 0; $i < 5; $i++) {
            $timeData = new LogTimeData(
                employeeId: $this->employee->id,
                entryDate: $weekStart->copy()->addDays($i),
                startTime: '09:00',
                endTime: '17:00',
                hours: 8.0,
                taskId: $this->task->id,
                projectId: $this->project->id,
                description: "Day " . ($i + 1) . " work"
            );

            $this->service->logTime($timeData);
        }

        $timesheet = $this->service->getWeeklyTimesheet($this->employee, $weekStart);

        $this->assertCount(7, $timesheet);
        $this->assertEquals(40.0, collect($timesheet)->sum(fn($day) => $day['total_hours']));
    }

    public function test_can_calculate_billable_hours()
    {
        // Create billable and non-billable entries
        $billableData = new LogTimeData(
            employeeId: $this->employee->id,
            entryDate: now(),
            startTime: '09:00',
            endTime: '15:00',
            hours: 6.0,
            taskId: $this->task->id,
            projectId: $this->project->id,
            description: 'Billable work',
            isBillable: true
        );

        $nonBillableData = new LogTimeData(
            employeeId: $this->employee->id,
            entryDate: now(),
            startTime: '15:00',
            endTime: '17:00',
            hours: 2.0,
            taskId: $this->task->id,
            projectId: $this->project->id,
            description: 'Internal meeting',
            isBillable: false
        );

        $this->service->logTime($billableData);
        $this->service->logTime($nonBillableData);

        $billableHours = $this->service->getBillableHours(
            $this->employee,
            now()->startOfMonth(),
            now()->endOfMonth()
        );

        $this->assertEquals(6.0, $billableHours);
    }

    public function test_can_update_time_entry()
    {
        $timeData = new LogTimeData(
            employeeId: $this->employee->id,
            entryDate: now(),
            startTime: '09:00',
            endTime: '13:00',
            hours: 4.0,
            taskId: $this->task->id,
            projectId: $this->project->id,
            description: 'Initial work'
        );

        $entry = $this->service->logTime($timeData);

        $updatedData = new LogTimeData(
            employeeId: $this->employee->id,
            entryDate: now(),
            startTime: '09:00',
            endTime: '15:00',
            hours: 6.0,
            taskId: $this->task->id,
            projectId: $this->project->id,
            description: 'Updated work description'
        );

        $result = $this->service->updateTimeEntry($entry, $updatedData);

        $this->assertTrue($result);
        $this->assertEquals(6.0, $entry->fresh()->hours);
        $this->assertEquals('Updated work description', $entry->fresh()->description);
    }

    public function test_can_delete_time_entry()
    {
        $timeData = new LogTimeData(
            employeeId: $this->employee->id,
            entryDate: now(),
            startTime: '09:00',
            endTime: '13:00',
            hours: 4.0,
            taskId: $this->task->id,
            projectId: $this->project->id,
            description: 'Work to be deleted'
        );

        $entry = $this->service->logTime($timeData);
        $entryId = $entry->id;

        $result = $this->service->deleteTimeEntry($entry);

        $this->assertTrue($result);
        $this->assertNull(EmployeeTimeEntry::find($entryId));
    }

    public function test_validates_time_entry_hours()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Hours must be greater than 0 and less than or equal to 24');

        $timeData = new LogTimeData(
            employeeId: $this->employee->id,
            entryDate: now(),
            startTime: '09:00',
            endTime: '10:00', // This will be 25 hours which is invalid
            hours: 25.0, // Invalid hours
            taskId: $this->task->id,
            projectId: $this->project->id,
            description: 'Invalid hours'
        );

        $this->service->logTime($timeData);
    }
}