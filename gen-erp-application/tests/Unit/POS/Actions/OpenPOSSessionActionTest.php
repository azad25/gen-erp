<?php

namespace Tests\Unit\POS\Actions;

use App\Domain\Auth\Models\Branch;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\POS\Actions\OpenPOSSessionAction;
use App\Domain\POS\DTOs\OpenSessionData;
use App\Domain\POS\Events\POSSessionOpened;
use App\Domain\POS\Exceptions\SessionAlreadyOpenException;
use App\Domain\POS\Models\POSSession;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OpenPOSSessionActionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected User $user;
    protected OpenPOSSessionAction $action;

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

        CompanyContext::setActive($this->company);
        $this->action = new OpenPOSSessionAction();
    }

    public function test_can_open_new_session(): void
    {
        Event::fake();

        $data = new OpenSessionData(
            companyId: $this->company->id,
            branchId: $this->branch->id,
            openedBy: $this->user->id,
            openingCash: 1000000,
        );

        $session = $this->action->execute($data);

        $this->assertInstanceOf(POSSession::class, $session);
        $this->assertEquals($this->company->id, $session->company_id);
        $this->assertEquals($this->branch->id, $session->branch_id);
        $this->assertEquals($this->user->id, $session->opened_by);
        $this->assertEquals(1000000, $session->opening_cash);
        $this->assertEquals('open', $session->status);
        $this->assertNotNull($session->opened_at);

        Event::assertDispatched(POSSessionOpened::class);
    }

    public function test_throws_exception_when_session_already_open(): void
    {
        POSSession::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'opened_by' => $this->user->id,
            'opening_cash' => 1000000,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $data = new OpenSessionData(
            companyId: $this->company->id,
            branchId: $this->branch->id,
            openedBy: $this->user->id,
            openingCash: 1000000,
        );

        $this->expectException(SessionAlreadyOpenException::class);

        $this->action->execute($data);
    }

    public function test_can_open_new_session_after_previous_closed(): void
    {
        POSSession::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'opened_by' => $this->user->id,
            'opening_cash' => 1000000,
            'status' => 'closed',
            'opened_at' => now()->subDay(),
            'closed_at' => now()->subHour(),
            'closed_by' => $this->user->id,
        ]);

        $data = new OpenSessionData(
            companyId: $this->company->id,
            branchId: $this->branch->id,
            openedBy: $this->user->id,
            openingCash: 1000000,
        );

        $session = $this->action->execute($data);

        $this->assertInstanceOf(POSSession::class, $session);
        $this->assertEquals('open', $session->status);
    }
}
