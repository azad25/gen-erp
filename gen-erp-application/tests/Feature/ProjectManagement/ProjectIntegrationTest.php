<?php

namespace Tests\Feature\ProjectManagement;

use Tests\TestCase;
use App\Domain\Project\Models\Project;
use App\Domain\HR\Models\Employee;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
        $this->employee = Employee::factory()->create(['company_id' => $this->company->id]);
        
        // Associate user with company
        $this->user->companies()->attach($this->company->id, [
            'role' => 'admin',
            'is_owner' => true,
            'is_active' => true
        ]);
        
        $this->actingAs($this->user);
        session(['active_company_id' => $this->company->id]);
    }

    public function test_project_dashboard_api_endpoint_works()
    {
        // Arrange
        Project::factory()->count(3)->create(['company_id' => $this->company->id]);

        // Act
        $response = $this->getJson('/api/v1/projects/dashboard');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_projects',
                    'active_projects',
                    'completed_projects',
                    'overdue_projects',
                    'recent_projects',
                    'projects_by_status',
                    'projects_by_priority'
                ]
            ]);
    }

    public function test_project_list_api_endpoint_works()
    {
        // Arrange
        Project::factory()->count(5)->create(['company_id' => $this->company->id]);

        // Act
        $response = $this->getJson('/api/v1/projects');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'status',
                            'priority',
                            'progress_percentage'
                        ]
                    ],
                    'current_page',
                    'total'
                ]
            ]);
    }

    public function test_project_creation_api_endpoint_works()
    {
        // Arrange
        $projectData = [
            'name' => 'Test Project',
            'description' => 'Test Description',
            'status' => Project::STATUS_PLANNING,
            'priority' => Project::PRIORITY_MEDIUM,
            'project_manager_id' => $this->employee->id
        ];

        // Act
        $response = $this->postJson('/api/v1/projects', $projectData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'description',
                    'status',
                    'priority',
                    'project_manager'
                ]
            ]);

        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'company_id' => $this->company->id
        ]);
    }

    public function test_project_detail_api_endpoint_works()
    {
        // Arrange
        $project = Project::factory()->create(['company_id' => $this->company->id]);

        // Act
        $response = $this->getJson("/api/v1/projects/{$project->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'description',
                    'status',
                    'priority',
                    'progress_percentage',
                    'members',
                    'boards'
                ]
            ]);
    }

    public function test_project_statistics_api_endpoint_works()
    {
        // Arrange
        $project = Project::factory()->create(['company_id' => $this->company->id]);

        // Act
        $response = $this->getJson("/api/v1/projects/{$project->id}/statistics");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_tasks',
                    'completed_tasks',
                    'pending_tasks',
                    'total_time_logged',
                    'budget_utilization'
                ]
            ]);
    }

    public function test_project_web_routes_are_accessible()
    {
        // Test project dashboard route
        $response = $this->get('/projects/dashboard');
        $response->assertStatus(200);

        // Test project index route
        $response = $this->get('/projects');
        $response->assertStatus(200);

        // Test project create route
        $response = $this->get('/projects/create');
        $response->assertStatus(200);
    }

    public function test_project_member_management_works()
    {
        // Arrange
        $project = Project::factory()->create(['company_id' => $this->company->id]);
        $member = Employee::factory()->create(['company_id' => $this->company->id]);

        // Act - Add member
        $response = $this->postJson("/api/v1/projects/{$project->id}/members", [
            'employee_id' => $member->id,
            'role' => 'developer',
            'hourly_rate' => 50.0
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'employee_id' => $member->id,
            'role' => 'developer'
        ]);

        // Act - Remove member
        $response = $this->deleteJson("/api/v1/projects/{$project->id}/members/{$member->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseMissing('project_members', [
            'project_id' => $project->id,
            'employee_id' => $member->id
        ]);
    }

    public function test_project_filtering_works()
    {
        // Arrange
        Project::factory()->create([
            'company_id' => $this->company->id,
            'status' => Project::STATUS_ACTIVE,
            'name' => 'Active Project'
        ]);
        Project::factory()->create([
            'company_id' => $this->company->id,
            'status' => Project::STATUS_COMPLETED,
            'name' => 'Completed Project'
        ]);

        // Act - Filter by status
        $response = $this->getJson('/api/v1/projects?status=active');

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals(Project::STATUS_ACTIVE, $data[0]['status']);

        // Act - Search by name
        $response = $this->getJson('/api/v1/projects?search=Active');

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('Active', $data[0]['name']);
    }
}