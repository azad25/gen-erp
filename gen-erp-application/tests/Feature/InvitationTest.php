<?php

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('owner can invite a new email with a role', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $company = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);

    $invitation = Invitation::factory()->create([
        'company_id' => $company->id,
        'email' => 'newmember@example.com',
        'role' => 'employee',
        'invited_by' => $user->id,
    ]);

    expect($invitation)->toBeInstanceOf(Invitation::class);
    $this->assertDatabaseHas('invitations', [
        'email' => 'newmember@example.com',
        'company_id' => $company->id,
    ]);
});

test('invitation can be accepted by existing user', function (): void {
    $inviter = User::factory()->create();
    $company = Company::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $inviter->id,
    ]);

    $acceptor = User::factory()->create(['email' => 'acceptor@example.com']);

    $invitation = Invitation::factory()->create([
        'company_id' => $company->id,
        'email' => $acceptor->email,
        'role' => 'employee',
        'invited_by' => $inviter->id,
    ]);

    $this->actingAs($acceptor);

    $response = $this->get(route('invitation.accept', $invitation->token));

    $response->assertRedirect('/app');

    $this->assertDatabaseHas('company_user', [
        'company_id' => $company->id,
        'user_id' => $acceptor->id,
        'role' => 'employee',
    ]);

    expect($invitation->fresh()->accepted_at)->not->toBeNull();
});

test('expired invitation returns error', function (): void {
    $invitation = Invitation::factory()->expired()->create();

    $response = $this->get(route('invitation.accept', $invitation->token));

    $response->assertOk();
    $response->assertViewIs('invitations.expired');
});

test('already accepted invitation redirects to login', function (): void {
    $invitation = Invitation::factory()->accepted()->create();

    $response = $this->get(route('invitation.accept', $invitation->token));

    $response->assertRedirect(route('login'));
});
