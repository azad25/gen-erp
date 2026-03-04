<?php

namespace Tests\Unit\Domain\CRM\Services;

use App\Domain\CRM\Contracts\LeadServiceInterface;
use App\Domain\CRM\DTOs\LeadData;
use App\Domain\CRM\Enums\LeadSource;
use App\Domain\CRM\Enums\LeadStatus;
use App\Domain\CRM\Models\Lead;
use App\Domain\CRM\Models\LeadNote;
use App\Domain\CRM\Services\LeadService;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\Customer\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeadServiceInterface $leadService;
    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->leadService = new LeadService();
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        
        // Associate user with company
        $this->user->companies()->attach($this->company->id, [
            'role' => 'admin',
            'is_owner' => true,
            'is_active' => true
        ]);
    }

    public function test_can_create_lead(): void
    {
        $leadData = new LeadData(
            firstName: 'John',
            lastName: 'Doe',
            email: 'john.doe@example.com',
            phone: '+8801234567890',
            companyName: 'Test Company',
            source: LeadSource::WEBSITE,
            estimatedValue: 50000.00
        );

        $lead = $this->leadService->create($leadData, $this->company->id, $this->user->id);

        $this->assertInstanceOf(Lead::class, $lead);
        $this->assertEquals('John', $lead->first_name);
        $this->assertEquals('Doe', $lead->last_name);
        $this->assertEquals('john.doe@example.com', $lead->email);
        $this->assertEquals($this->company->id, $lead->company_id);
        $this->assertEquals($this->user->id, $lead->created_by);
        $this->assertEquals(LeadStatus::NEW, $lead->status);
        $this->assertEquals(LeadSource::WEBSITE, $lead->source);
        $this->assertEquals(50000.00, $lead->estimated_value);
    }

    public function test_can_update_lead(): void
    {
        $lead = Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $leadData = new LeadData(
            firstName: 'Jane',
            lastName: 'Smith',
            email: 'jane.smith@example.com',
            status: LeadStatus::CONTACTED,
            score: 75
        );

        $updatedLead = $this->leadService->update($lead, $leadData);

        $this->assertEquals('Jane', $updatedLead->first_name);
        $this->assertEquals('Smith', $updatedLead->last_name);
        $this->assertEquals('jane.smith@example.com', $updatedLead->email);
        $this->assertEquals(LeadStatus::CONTACTED, $updatedLead->status);
        $this->assertEquals(75, $updatedLead->score);
    }

    public function test_can_find_lead_by_uuid(): void
    {
        $lead = Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $foundLead = $this->leadService->findByUuid($lead->uuid, $this->company->id);

        $this->assertNotNull($foundLead);
        $this->assertEquals($lead->id, $foundLead->id);
        $this->assertEquals($lead->uuid, $foundLead->uuid);
    }

    public function test_cannot_find_lead_from_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $lead = Lead::factory()->create([
            'company_id' => $otherCompany->id,
            'created_by' => $this->user->id,
        ]);

        $foundLead = $this->leadService->findByUuid($lead->uuid, $this->company->id);

        $this->assertNull($foundLead);
    }

    public function test_can_assign_lead_to_user(): void
    {
        $assignee = User::factory()->create();
        $assignee->companies()->attach($this->company->id, [
            'role' => 'employee',
            'is_owner' => false,
            'is_active' => true
        ]);
        
        $lead = Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'assigned_to' => null,
        ]);

        $assignedLead = $this->leadService->assignTo($lead, $assignee->id);

        $this->assertEquals($assignee->id, $assignedLead->assigned_to);
    }

    public function test_can_update_lead_score(): void
    {
        $lead = Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'score' => 30,
        ]);

        $updatedLead = $this->leadService->updateScore($lead, 85);

        $this->assertEquals(85, $updatedLead->score);
    }

    public function test_score_is_clamped_between_0_and_100(): void
    {
        $lead = Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'score' => 50,
        ]);

        // Test upper bound
        $updatedLead = $this->leadService->updateScore($lead, 150);
        $this->assertEquals(100, $updatedLead->score);

        // Test lower bound
        $updatedLead = $this->leadService->updateScore($lead, -10);
        $this->assertEquals(0, $updatedLead->score);
    }

    public function test_can_qualify_lead(): void
    {
        $lead = Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'status' => LeadStatus::CONTACTED,
            'qualified_at' => null,
        ]);

        $qualifiedLead = $this->leadService->qualify($lead);

        $this->assertEquals(LeadStatus::QUALIFIED, $qualifiedLead->status);
        $this->assertNotNull($qualifiedLead->qualified_at);
    }

    public function test_can_convert_lead_to_customer(): void
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);
        
        $lead = Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'status' => LeadStatus::QUALIFIED,
        ]);

        $convertedLead = $this->leadService->convertToCustomer($lead, $customer);

        $this->assertEquals(LeadStatus::CONVERTED, $convertedLead->status);
        $this->assertEquals($customer->id, $convertedLead->converted_to_customer_id);
        $this->assertNotNull($convertedLead->converted_at);
    }

    public function test_can_add_note_to_lead(): void
    {
        $lead = Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
        ]);

        $this->leadService->addNote($lead, 'This is a test note', $this->user->id, [
            'type' => 'call_log',
            'is_private' => true,
        ]);

        $this->assertDatabaseHas('lead_notes', [
            'lead_id' => $lead->id,
            'user_id' => $this->user->id,
            'content' => 'This is a test note',
            'type' => 'call_log',
            'is_private' => true,
        ]);
    }

    public function test_can_get_leads_for_company_with_filters(): void
    {
        // Create leads with different statuses
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

        Lead::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'status' => LeadStatus::CONVERTED,
            'source' => LeadSource::WEBSITE,
            'score' => 95,
        ]);

        // Test status filter
        $newLeads = $this->leadService->getForCompany($this->company->id, [
            'status' => LeadStatus::NEW->value,
        ]);
        $this->assertEquals(1, $newLeads->count());

        // Test source filter
        $websiteLeads = $this->leadService->getForCompany($this->company->id, [
            'source' => LeadSource::WEBSITE->value,
        ]);
        $this->assertEquals(2, $websiteLeads->count());

        // Test min_score filter
        $highScoreLeads = $this->leadService->getForCompany($this->company->id, [
            'min_score' => 85,
        ]);
        $this->assertEquals(2, $highScoreLeads->count());
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

        $statistics = $this->leadService->getLeadStatistics($this->company->id);

        $this->assertEquals(6, $statistics['total_leads']);
        $this->assertEquals(3, $statistics['by_status'][LeadStatus::NEW->value]);
        $this->assertEquals(2, $statistics['by_status'][LeadStatus::QUALIFIED->value]);
        $this->assertEquals(1, $statistics['by_status'][LeadStatus::CONVERTED->value]);
        $this->assertEquals(3, $statistics['high_score_leads']); // 2 with score 80 + 1 with score 95 = 3 leads >= 70
        $this->assertEquals(16.67, $statistics['conversion_rate']); // 1/6 * 100
    }

    public function test_can_bulk_assign_leads(): void
    {
        $assignee = User::factory()->create();
        $assignee->companies()->attach($this->company->id, [
            'role' => 'employee',
            'is_owner' => false,
            'is_active' => true
        ]);
        
        $leads = Lead::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'assigned_to' => null,
        ]);

        $leadIds = $leads->pluck('id')->toArray();
        $updated = $this->leadService->bulkAssign($leadIds, $assignee->id, $this->company->id);

        $this->assertEquals(3, $updated);
        
        foreach ($leads as $lead) {
            $lead->refresh();
            $this->assertEquals($assignee->id, $lead->assigned_to);
        }
    }

    public function test_can_bulk_update_status(): void
    {
        $leads = Lead::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->user->id,
            'status' => LeadStatus::NEW,
        ]);

        $leadIds = $leads->pluck('id')->toArray();
        $updated = $this->leadService->bulkUpdateStatus($leadIds, LeadStatus::CONTACTED->value, $this->company->id);

        $this->assertEquals(3, $updated);
        
        foreach ($leads as $lead) {
            $lead->refresh();
            $this->assertEquals(LeadStatus::CONTACTED, $lead->status);
        }
    }
}