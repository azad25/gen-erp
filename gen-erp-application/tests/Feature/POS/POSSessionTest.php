<?php

namespace Tests\Feature\POS;

use App\Domain\Auth\Models\Branch;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\POS\Models\POSSession;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class POSSessionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'is_active' => true,
        ]);
        $this->user = User::factory()->create();
        
        $this->company->users()->attach($this->user->id, [
            'role' => 'admin',
            'is_owner' => true,
            'is_active' => true,
        ]);

        CompanyContext::setActive($this->company);
        $this->actingAs($this->user);
    }

    public function test_can_open_pos_session(): void
    {
        $response = $this->postJson('/api/v1/pos/sessions', [
            'branch_id' => $this->branch->id,
            'opening_cash' => 1000000, // 10,000 BDT in paisa
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'branch_id',
                    'opening_cash',
                    'status',
                    'opened_at',
                ],
            ]);

        $this->assertDatabaseHas('pos_sessions', [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'opened_by' => $this->user->id,
            'opening_cash' => 1000000,
            'status' => 'open',
        ]);
    }

    public function test_can_get_active_session(): void
    {
        $session = POSSession::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'opened_by' => $this->user->id,
            'opening_cash' => 1000000,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/pos/sessions/active');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $session->id)
            ->assertJsonPath('data.0.status', 'open');
    }

    public function test_can_close_pos_session(): void
    {
        $session = POSSession::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'opened_by' => $this->user->id,
            'opening_cash' => 1000000,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $response = $this->postJson("/api/v1/pos/sessions/{$session->id}/close", [
            'closing_cash' => 1500000, // 15,000 BDT
            'notes' => 'End of day closing',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('pos_sessions', [
            'id' => $session->id,
            'status' => 'closed',
            'closing_cash' => 1500000,
            'closed_by' => $this->user->id,
        ]);
    }

    public function test_cannot_open_multiple_sessions_for_same_branch(): void
    {
        POSSession::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'opened_by' => $this->user->id,
            'opening_cash' => 1000000,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/pos/sessions', [
            'branch_id' => $this->branch->id,
            'opening_cash' => 1000000,
        ]);

        $response->assertStatus(422);
    }

    public function test_can_get_session_summary(): void
    {
        $session = POSSession::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'opened_by' => $this->user->id,
            'opening_cash' => 1000000,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/pos/sessions/{$session->id}/summary");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'session',
                    'total_sales',
                    'total_amount',
                    'payment_breakdown',
                ],
            ]);
    }
}
