<?php

namespace Tests\Feature\Domain\CRM;

use App\Domain\CRM\Enums\LeadSource;
use App\Domain\CRM\Enums\LeadStatus;
use App\Domain\CRM\Models\Lead;
use App\Domain\CRM\Models\LeadTag;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeadApiTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        
        // Associate user with company and set as current company
        $this->user->companies()->attach($this->company->id, ['role' => 'admin']);
        $this->user->update(['current_company_id' => $this->company->id]);
        
        Sanctum::actingAs($this->user);
    }

    public function test_can_list_leads(): void
    {
        Lead::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/crm/leads');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'full_name',
                        'first_name',
                        'last_name',
                        'email',
                        'phone',
                        'status',
                        'source',
                        'score',
                        'created_at',
                    ]
                ],
                'links',
                'meta',
            ]);

        $this->assertEquals(3, $response->json('meta.total'));
    }

    public function test_can_create_lead(): void
    {
        $leadData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+8801234567890',
            'company_name' => 'Test Company',
            'job_title' => 'CEO',
            'source' => LeadSource::WEBSITE->value,
            'estimated_value' => 50000.00,
            'currency' => 'BDT',
            'notes' => 'Interested in our premium package',
        ];

        $response = $this->postJson('/api/v1/crm/leads', $leadData);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'uuid',
                    'full_name',
                    'first_name',
                    'last_name',
                    'email',
                    'phone',
                    'company_name',
                    'job_title',
                    'status',
                    'source',
                    'estimated_value',
                    'currency',
                    'notes',
                ]
            ]);

        $this->assertDatabaseHas('leads', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_create_lead_validation(): void
    {
        $response = $this->postJson('/api/v1/crm/leads', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['first_name', 'last_name']);
    }

    public function test_can_show_lead(): void
    {
        $lead = Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/crm/leads/{$lead->uuid}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'uuid',
                    'full_name',
                    'first_name',
                    'last_name',
                    'email',
                    'phone',
                    'status',
                    'source',
                    'score',
                    'created_by',
                ]
            ]);

        $this->assertEquals($lead->uuid, $response->json('data.uuid'));
    }

    public function test_cannot_show_lead_from_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $lead = Lead::factory()->create([
            'company_id' => $otherCompany->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/crm/leads/{$lead->uuid}");

        $response->assertNotFound();
    }

    public function test_can_update_lead(): void
    {
        $lead = Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $updateData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'status' => LeadStatus::CONTACTED->value,
            'score' => 75,
        ];

        $response = $this->putJson("/api/v1/crm/leads/{$lead->uuid}", $updateData);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'uuid',
                    'full_name',
                    'first_name',
                    'last_name',
                    'email',
                    'status',
                    'score',
                ]
            ]);

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'score' => 75,
        ]);
    }

    public function test_can_delete_lead(): void
    {
        $lead = Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/v1/crm/leads/{$lead->uuid}");

        $response->assertOk()
            ->assertJson(['message' => __('crm.lead_deleted_successfully')]);

        $this->assertSoftDeleted('leads', ['id' => $lead->id]);
    }

    public function test_can_assign_lead(): void
    {
        $assignee = User::factory()->create();
        $assignee->companies()->attach($this->company->id, ['role' => 'user']);
        
        $lead = Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'assigned_to' => null,
        ]);

        $response = $this->postJson("/api/v1/crm/leads/{$lead->uuid}/assign", [
            'assigned_to' => $assignee->id,
        ]);

        $response->assertOk()
            ->assertJson(['message' => __('crm.lead_assigned_successfully')]);

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'assigned_to' => $assignee->id,
        ]);
    }

    public function test_can_update_lead_score(): void
    {
        $lead = Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'score' => 30,
        ]);

        $response = $this->postJson("/api/v1/crm/leads/{$lead->uuid}/update-score", [
            'score' => 85,
        ]);

        $response->assertOk()
            ->assertJson(['message' => __('crm.lead_score_updated_successfully')]);

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'score' => 85,
        ]);
    }

    public function test_score_validation(): void
    {
        $lead = Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        // Test invalid score (too high)
        $response = $this->postJson("/api/v1/crm/leads/{$lead->uuid}/update-score", [
            'score' => 150,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['score']);

        // Test invalid score (negative)
        $response = $this->postJson("/api/v1/crm/leads/{$lead->uuid}/update-score", [
            'score' => -10,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['score']);
    }

    public function test_can_qualify_lead(): void
    {
        $lead = Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'status' => LeadStatus::CONTACTED,
        ]);

        $response = $this->postJson("/api/v1/crm/leads/{$lead->uuid}/qualify");

        $response->assertOk()
            ->assertJson(['message' => __('crm.lead_qualified_successfully')]);

        $lead->refresh();
        $this->assertEquals(LeadStatus::QUALIFIED, $lead->status);
        $this->assertNotNull($lead->qualified_at);
    }

    public function test_can_add_note_to_lead(): void
    {
        $lead = Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $noteData = [
            'content' => 'This is a test note',
            'title' => 'Call Summary',
            'type' => 'call_log',
            'is_private' => true,
        ];

        $response = $this->postJson("/api/v1/crm/leads/{$lead->uuid}/notes", $noteData);

        $response->assertOk()
            ->assertJson(['message' => __('crm.note_added_successfully')]);

        $this->assertDatabaseHas('lead_notes', [
            'lead_id' => $lead->id,
            'user_id' => $this->user->id,
            'content' => 'This is a test note',
            'title' => 'Call Summary',
            'type' => 'call_log',
            'is_private' => true,
        ]);
    }

    public function test_can_add_tag_to_lead(): void
    {
        $lead = Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $tag = LeadTag::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'name' => 'Hot Lead',
        ]);

        $response = $this->postJson("/api/v1/crm/leads/{$lead->uuid}/tags", [
            'tag_id' => $tag->id,
        ]);

        $response->assertOk()
            ->assertJson(['message' => __('crm.tag_added_successfully')]);

        $this->assertDatabaseHas('lead_tag_pivot', [
            'lead_id' => $lead->id,
            'lead_tag_id' => $tag->id,
            'tagged_by' => $this->user->id,
        ]);
    }

    public function test_can_remove_tag_from_lead(): void
    {
        $lead = Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $tag = LeadTag::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        // First add the tag
        $lead->tags()->attach($tag->id, [
            'tagged_by' => $this->user->id,
            'tagged_at' => now(),
        ]);

        $response = $this->deleteJson("/api/v1/crm/leads/{$lead->uuid}/tags/{$tag->id}");

        $response->assertOk()
            ->assertJson(['message' => __('crm.tag_removed_successfully')]);

        $this->assertDatabaseMissing('lead_tag_pivot', [
            'lead_id' => $lead->id,
            'lead_tag_id' => $tag->id,
        ]);
    }

    public function test_can_bulk_assign_leads(): void
    {
        $assignee = User::factory()->create();
        $assignee->companies()->attach($this->company->id, ['role' => 'user']);
        
        $leads = Lead::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'assigned_to' => null,
        ]);

        $response = $this->postJson('/api/v1/crm/leads/bulk-assign', [
            'lead_ids' => $leads->pluck('id')->toArray(),
            'assigned_to' => $assignee->id,
        ]);

        $response->assertOk()
            ->assertJson(['message' => __('crm.leads_assigned_successfully', ['count' => 3])]);

        foreach ($leads as $lead) {
            $this->assertDatabaseHas('leads', [
                'id' => $lead->id,
                'assigned_to' => $assignee->id,
            ]);
        }
    }

    public function test_can_bulk_update_status(): void
    {
        $leads = Lead::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'status' => LeadStatus::NEW,
        ]);

        $response = $this->postJson('/api/v1/crm/leads/bulk-update-status', [
            'lead_ids' => $leads->pluck('id')->toArray(),
            'status' => LeadStatus::CONTACTED->value,
        ]);

        $response->assertOk()
            ->assertJson(['message' => __('crm.leads_status_updated_successfully', ['count' => 3])]);

        foreach ($leads as $lead) {
            $this->assertDatabaseHas('leads', [
                'id' => $lead->id,
                'status' => LeadStatus::CONTACTED->value,
            ]);
        }
    }

    public function test_can_get_lead_statistics(): void
    {
        // Create leads with different statuses
        Lead::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'status' => LeadStatus::NEW,
            'score' => 30,
        ]);

        Lead::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'status' => LeadStatus::QUALIFIED,
            'score' => 80,
        ]);

        Lead::factory()->count(1)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'status' => LeadStatus::CONVERTED,
            'score' => 95,
        ]);

        $response = $this->getJson('/api/v1/crm/leads/statistics');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_leads',
                    'by_status',
                    'by_source',
                    'average_score',
                    'high_score_leads',
                    'conversion_rate',
                ]
            ]);

        $data = $response->json('data');
        $this->assertEquals(6, $data['total_leads']);
        $this->assertEquals(3, $data['by_status'][LeadStatus::NEW->value]);
        $this->assertEquals(2, $data['by_status'][LeadStatus::QUALIFIED->value]);
        $this->assertEquals(1, $data['by_status'][LeadStatus::CONVERTED->value]);
        $this->assertEquals(3, $data['high_score_leads']); // score >= 70
    }

    public function test_can_get_my_leads(): void
    {
        // Create leads assigned to current user
        Lead::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'assigned_to' => $this->user->id,
        ]);

        // Create lead assigned to someone else
        $otherUser = User::factory()->create();
        $otherUser->companies()->attach($this->company->id, ['role' => 'user']);
        
        Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'assigned_to' => $otherUser->id,
        ]);

        $response = $this->getJson('/api/v1/crm/leads/my-leads');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'full_name',
                        'assigned_to',
                    ]
                ]
            ]);

        $this->assertEquals(2, count($response->json('data')));
    }

    public function test_can_filter_leads(): void
    {
        // Create leads with different attributes
        Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'status' => LeadStatus::NEW,
            'source' => LeadSource::WEBSITE,
            'score' => 80,
        ]);

        Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'status' => LeadStatus::QUALIFIED,
            'source' => LeadSource::REFERRAL,
            'score' => 90,
        ]);

        // Test status filter
        $response = $this->getJson('/api/v1/crm/leads?status=' . LeadStatus::NEW->value);
        $response->assertOk();
        $this->assertEquals(1, $response->json('meta.total'));

        // Test source filter
        $response = $this->getJson('/api/v1/crm/leads?source=' . LeadSource::WEBSITE->value);
        $response->assertOk();
        $this->assertEquals(1, $response->json('meta.total'));

        // Test min_score filter
        $response = $this->getJson('/api/v1/crm/leads?min_score=85');
        $response->assertOk();
        $this->assertEquals(1, $response->json('meta.total'));
    }
}