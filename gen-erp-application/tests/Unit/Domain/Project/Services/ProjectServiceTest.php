<?php

namespace Tests\Unit\Domain\Project\Services;

use Tests\TestCase;
use App\Domain\Project\Services\ProjectService;
use App\Domain\Project\DTOs\CreateProjectData;
use App\Domain\Project\DTOs\UpdateProjectData;
use App\Domain\Project\Models\Project;
use App\Domain\Project\Models\Board;
use App\Domain\HR\Models\Employee;
use App\Domain\Auth\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProjectService $projectService;
    private Company $company;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->projectService = new ProjectService();
        $this->company = Company::factory()->create();
        $this->employee = Employee::factory()->create(['company_id' => $this->company->id]);
    }

    public function test_get_all_projects_returns_paginated_results()
    {
        // Arrange
        Project::factory()->count(3)->create(['company_id' => $this->company->id]);

        // Act
        $result = $this->projectService->getAllProjects($this->company->id);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(3, $result->total());
    }

    public function test_get_all_projects_filters_by_status()
    {
        // Arrange
        Project::factory()->create(['company_id' => $this->company->id, 'status' => Project::STATUS_ACTIVE]);
        Project::factory()->create(['company_id' => $this->company->id, 'status' => Project::STATUS_COMPLETED]);

        // Act
        $result = $this->projectService->getAllProjects($this->company->id, ['status' => Project::STATUS_ACTIVE]);

        // Assert
        $this->assertEquals(1, $result->total());
        $this->assertEquals(Project::STATUS_ACTIVE, $result->items()[0]->status);
    }

    public function test_get_all_projects_filters_by_search()
    {
        // Arrange
        Project::factory()->create(['company_id' => $this->company->id, 'name' => 'Test Project']);
        Project::factory()->create(['company_id' => $this->company->id, 'name' => 'Another Project']);

        // Act
        $result = $this->projectService->getAllProjects($this->company->id, ['search' => 'Test']);

        // Assert
        $this->assertEquals(1, $result->total());
        $this->assertStringContainsString('Test', $result->items()[0]->name);
    }

    public function test_get_project_by_id_returns_project_with_relationships()
    {
        // Arrange
        $project = Project::factory()->create(['company_id' => $this->company->id]);

        // Act
        $result = $this->projectService->getProjectById($project->id, $this->company->id);

        // Assert
        $this->assertInstanceOf(Project::class, $result);
        $this->assertEquals($project->id, $result->id);
        $this->assertTrue($result->relationLoaded('projectManager'));
        $this->assertTrue($result->relationLoaded('members'));
    }

    public function test_get_project_by_id_returns_null_for_different_company()
    {
        // Arrange
        $otherCompany = Company::factory()->create();
        $project = Project::factory()->create(['company_id' => $otherCompany->id]);

        // Act
        $result = $this->projectService->getProjectById($project->id, $this->company->id);

        // Assert
        $this->assertNull($result);
    }

    public function test_create_project_creates_project_with_default_board()
    {
        // Arrange
        $data = new CreateProjectData(
            companyId: $this->company->id,
            name: 'Test Project',
            description: 'Test Description',
            projectManagerId: $this->employee->id
        );

        // Act
        $result = $this->projectService->createProject($data);

        // Assert
        $this->assertInstanceOf(Project::class, $result);
        $this->assertEquals('Test Project', $result->name);
        $this->assertEquals('Test Description', $result->description);
        $this->assertEquals($this->employee->id, $result->project_manager_id);
        $this->assertEquals(1, $result->boards()->count());
        $this->assertTrue($result->boards()->first()->is_default);
    }

    public function test_create_project_adds_project_manager_as_member()
    {
        // Arrange
        $data = new CreateProjectData(
            companyId: $this->company->id,
            name: 'Test Project',
            projectManagerId: $this->employee->id
        );

        // Act
        $result = $this->projectService->createProject($data);

        // Assert
        $this->assertTrue($result->members->contains($this->employee));
        $this->assertEquals('lead', $result->members->first()->pivot->role);
    }

    public function test_create_project_adds_initial_members()
    {
        // Arrange
        $member1 = Employee::factory()->create(['company_id' => $this->company->id]);
        $member2 = Employee::factory()->create(['company_id' => $this->company->id]);
        
        $data = new CreateProjectData(
            companyId: $this->company->id,
            name: 'Test Project',
            memberIds: [$member1->id, $member2->id]
        );

        // Act
        $result = $this->projectService->createProject($data);

        // Assert
        $this->assertEquals(2, $result->members()->count());
        $this->assertTrue($result->members->contains($member1));
        $this->assertTrue($result->members->contains($member2));
    }

    public function test_update_project_updates_fields()
    {
        // Arrange
        $project = Project::factory()->create(['company_id' => $this->company->id]);
        $data = new UpdateProjectData(
            name: 'Updated Name',
            description: 'Updated Description',
            status: Project::STATUS_ACTIVE
        );

        // Act
        $result = $this->projectService->updateProject($project->id, $data);

        // Assert
        $this->assertEquals('Updated Name', $result->name);
        $this->assertEquals('Updated Description', $result->description);
        $this->assertEquals(Project::STATUS_ACTIVE, $result->status);
    }

    public function test_delete_project_removes_project()
    {
        // Arrange
        $project = Project::factory()->create(['company_id' => $this->company->id]);

        // Act
        $result = $this->projectService->deleteProject($project->id);

        // Assert
        $this->assertTrue($result);
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_add_project_member_adds_member_with_role()
    {
        // Arrange
        $project = Project::factory()->create(['company_id' => $this->company->id]);
        $member = Employee::factory()->create(['company_id' => $this->company->id]);

        // Act
        $this->projectService->addProjectMember($project->id, $member->id, 'developer', 50.0);

        // Assert
        $project->refresh();
        $this->assertTrue($project->members->contains($member));
        $this->assertEquals('developer', $project->members->first()->pivot->role);
        $this->assertEquals(50.0, $project->members->first()->pivot->hourly_rate);
    }

    public function test_remove_project_member_removes_member()
    {
        // Arrange
        $project = Project::factory()->create(['company_id' => $this->company->id]);
        $member = Employee::factory()->create(['company_id' => $this->company->id]);
        $project->members()->attach($member->id, ['role' => 'developer']);

        // Act
        $this->projectService->removeProjectMember($project->id, $member->id);

        // Assert
        $project->refresh();
        $this->assertFalse($project->members->contains($member));
    }

    public function test_update_project_member_role_updates_role()
    {
        // Arrange
        $project = Project::factory()->create(['company_id' => $this->company->id]);
        $member = Employee::factory()->create(['company_id' => $this->company->id]);
        $project->members()->attach($member->id, ['role' => 'developer']);

        // Act
        $this->projectService->updateProjectMemberRole($project->id, $member->id, 'lead', 75.0);

        // Assert
        $project->refresh();
        $this->assertEquals('lead', $project->members->first()->pivot->role);
        $this->assertEquals(75.0, $project->members->first()->pivot->hourly_rate);
    }

    public function test_get_project_statistics_returns_correct_data()
    {
        // Arrange
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
            'estimated_hours' => 100,
            'budget' => 10000
        ]);

        // Act
        $result = $this->projectService->getProjectStatistics($project->id);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_tasks', $result);
        $this->assertArrayHasKey('completed_tasks', $result);
        $this->assertArrayHasKey('total_time_logged', $result);
        $this->assertArrayHasKey('budget_utilization', $result);
        $this->assertEquals(100, $result['estimated_hours']);
        $this->assertEquals(10000, $result['budget']);
    }

    public function test_get_dashboard_data_returns_summary_statistics()
    {
        // Arrange
        Project::factory()->create(['company_id' => $this->company->id, 'status' => Project::STATUS_ACTIVE]);
        Project::factory()->create(['company_id' => $this->company->id, 'status' => Project::STATUS_COMPLETED]);

        // Act
        $result = $this->projectService->getDashboardData($this->company->id);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals(2, $result['total_projects']);
        $this->assertEquals(1, $result['active_projects']);
        $this->assertEquals(1, $result['completed_projects']);
        $this->assertArrayHasKey('recent_projects', $result);
        $this->assertArrayHasKey('projects_by_status', $result);
        $this->assertArrayHasKey('projects_by_priority', $result);
    }

    public function test_get_employee_projects_returns_assigned_projects()
    {
        // Arrange
        $project1 = Project::factory()->create([
            'company_id' => $this->company->id,
            'project_manager_id' => $this->employee->id
        ]);
        $project2 = Project::factory()->create(['company_id' => $this->company->id]);
        $project2->members()->attach($this->employee->id, ['role' => 'developer']);

        // Act
        $result = $this->projectService->getEmployeeProjects($this->employee->id, $this->company->id);

        // Assert
        $this->assertEquals(2, $result->count());
        $this->assertTrue($result->contains($project1));
        $this->assertTrue($result->contains($project2));
    }

    public function test_archive_project_sets_status_to_completed()
    {
        // Arrange
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
            'status' => Project::STATUS_ACTIVE
        ]);

        // Act
        $result = $this->projectService->archiveProject($project->id);

        // Assert
        $this->assertEquals(Project::STATUS_COMPLETED, $result->status);
    }

    public function test_duplicate_project_creates_copy_with_members_and_boards()
    {
        // Arrange
        $originalProject = Project::factory()->create(['company_id' => $this->company->id]);
        $member = Employee::factory()->create(['company_id' => $this->company->id]);
        $originalProject->members()->attach($member->id, ['role' => 'developer']);
        
        $board = $originalProject->boards()->create([
            'name' => 'Test Board',
            'type' => Board::TYPE_KANBAN,
            'is_default' => true
        ]);
        $board->columns()->create(['name' => 'To Do', 'position' => 1]);

        // Act
        $result = $this->projectService->duplicateProject($originalProject->id, 'Duplicated Project');

        // Assert
        $this->assertEquals('Duplicated Project', $result->name);
        $this->assertEquals(Project::STATUS_PLANNING, $result->status);
        $this->assertEquals(0, $result->progress_percentage);
        $this->assertEquals(1, $result->members()->count());
        $this->assertEquals(1, $result->boards()->count());
        $this->assertEquals(1, $result->boards()->first()->columns()->count());
    }
}