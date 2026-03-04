<?php

namespace Tests\Unit\Domain\HR\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domain\HR\Services\CapacityPlanningService;
use App\Domain\HR\Models\Employee;
use App\Domain\HR\Models\EmployeeCapacity;
use App\Domain\Auth\Models\User;
use App\Domain\Auth\Models\Company;

class CapacityPlanningServiceTest extends TestCase
{
    use RefreshDatabase;

    private CapacityPlanningService $service;
    private Company $company;
    private User $user;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(CapacityPlanningService::class);
        
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
            'weekly_capacity_hours' => 40
        ]);
        
        // Set active company context
        session(['active_company_id' => $this->company->id]);
    }

    public function test_can_allocate_hours_to_employee()
    {
        $result = $this->service->allocateHours($this->employee, 20);

        $this->assertTrue($result);
        
        $capacity = EmployeeCapacity::where('employee_id', $this->employee->id)
            ->where('week_start_date', now()->startOfWeek())
            ->first();
            
        $this->assertNotNull($capacity);
        $this->assertEquals(20, $capacity->allocated_hours);
    }

    public function test_can_deallocate_hours_from_employee()
    {
        // First allocate some hours
        $this->service->allocateHours($this->employee, 30);
        
        // Then deallocate some
        $result = $this->service->deallocateHours($this->employee, 10);

        $this->assertTrue($result);
        
        $capacity = EmployeeCapacity::where('employee_id', $this->employee->id)
            ->where('week_start_date', now()->startOfWeek())
            ->first();
            
        $this->assertEquals(20, $capacity->allocated_hours);
    }

    public function test_can_get_employee_capacity()
    {
        $this->service->allocateHours($this->employee, 25);

        $capacity = $this->service->getEmployeeCapacity($this->employee);

        $this->assertNotNull($capacity);
        $this->assertEquals($this->employee->id, $capacity->employee_id);
        $this->assertEquals(25, $capacity->allocated_hours);
        $this->assertEquals(15, $capacity->available_hours); // This should be remaining capacity (40-25)
        $this->assertEquals(15, $capacity->remaining_hours);
    }

    public function test_can_calculate_utilization_percentage()
    {
        $this->service->allocateHours($this->employee, 30);

        $utilization = $this->service->getUtilizationPercentage($this->employee);

        $this->assertEquals(75.0, $utilization); // 30/40 * 100
    }

    public function test_can_check_if_employee_is_over_capacity()
    {
        // Allocate more hours than available
        $this->service->allocateHours($this->employee, 45);

        $isOverCapacity = $this->service->isOverCapacity($this->employee);

        $this->assertTrue($isOverCapacity);
    }

    public function test_can_get_available_employees()
    {
        // Create another employee
        $employee2 = Employee::factory()->create([
            'company_id' => $this->company->id,
            'weekly_capacity_hours' => 40,
            'is_available_for_projects' => true
        ]);

        // Allocate hours to first employee (over capacity)
        $this->service->allocateHours($this->employee, 45);
        
        // Allocate hours to second employee (under capacity)
        $this->service->allocateHours($employee2, 20);

        $availableEmployees = $this->service->getAvailableEmployees();

        $this->assertCount(1, $availableEmployees);
        $this->assertEquals($employee2->id, $availableEmployees->first()->id);
    }

    public function test_can_get_team_capacity_overview()
    {
        // Create multiple employees
        $employee2 = Employee::factory()->create([
            'company_id' => $this->company->id,
            'weekly_capacity_hours' => 40
        ]);

        $employee3 = Employee::factory()->create([
            'company_id' => $this->company->id,
            'weekly_capacity_hours' => 30
        ]);

        // Allocate different hours to each
        $this->service->allocateHours($this->employee, 35);
        $this->service->allocateHours($employee2, 20);
        $this->service->allocateHours($employee3, 25);

        $overview = $this->service->getTeamCapacityOverview();

        $this->assertEquals(110, $overview['total_available_hours']); // 40 + 40 + 30
        $this->assertEquals(80, $overview['total_allocated_hours']); // 35 + 20 + 25
        $this->assertEquals(30, $overview['total_remaining_hours']); // 110 - 80
        $this->assertEquals(72.73, round($overview['average_utilization'], 2)); // 80/110 * 100
        $this->assertEquals(0, $overview['over_capacity_count']); // No employees over capacity
    }

    public function test_can_get_capacity_forecast()
    {
        $this->service->allocateHours($this->employee, 30);

        $forecast = $this->service->getCapacityForecast($this->employee, 4); // 4 weeks

        $this->assertCount(4, $forecast);
        
        // Check first week (current)
        $this->assertEquals(30, $forecast[0]['allocated_hours']);
        $this->assertEquals(10, $forecast[0]['remaining_hours']);
        
        // Check future weeks (should have full capacity available)
        for ($i = 1; $i < 4; $i++) {
            $this->assertEquals(0, $forecast[$i]['allocated_hours']);
            $this->assertEquals(40, $forecast[$i]['remaining_hours']);
        }
    }

    public function test_cannot_allocate_negative_hours()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Hours must be greater than 0');

        $this->service->allocateHours($this->employee, -5);
    }

    public function test_can_set_employee_availability()
    {
        $result = $this->service->setEmployeeAvailability($this->employee, false);

        $this->assertTrue($result);
        $this->assertFalse($this->employee->fresh()->is_available_for_projects);
    }

    public function test_can_update_available_hours()
    {
        $result = $this->service->updateAvailableHours($this->employee, 35);

        $this->assertTrue($result);
        $this->assertEquals(35, $this->employee->fresh()->weekly_capacity_hours);
    }

    public function test_can_get_workload_distribution()
    {
        // Create projects and allocate hours
        $this->service->allocateHours($this->employee, 20, 'Project A');
        $this->service->allocateHours($this->employee, 15, 'Project B');

        $distribution = $this->service->getWorkloadDistribution($this->employee);

        $this->assertCount(2, $distribution);
        $this->assertEquals(20, $distribution['Project A']);
        $this->assertEquals(15, $distribution['Project B']);
    }
}