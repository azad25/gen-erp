<?php

namespace Tests\Feature\Auth;

use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SessionLoginTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_sets_active_company_in_session_after_login(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $company = Company::factory()->create();
        $user->companies()->attach($company->id, [
            'role' => 'owner',
            'is_owner' => true,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        // Act
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertEquals($company->id, session('active_company_id'));
        
        // Verify user's last_active_company_id was updated
        $user->refresh();
        $this->assertEquals($company->id, $user->last_active_company_id);
    }

    /** @test */
    public function it_uses_last_active_company_when_user_has_multiple_companies(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $user->companies()->attach($company1->id, [
            'role' => 'owner',
            'is_owner' => true,
            'is_active' => true,
            'joined_at' => now(),
        ]);
        $user->companies()->attach($company2->id, [
            'role' => 'admin',
            'is_owner' => false,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $user->update(['last_active_company_id' => $company2->id]);

        // Act
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertEquals($company2->id, session('active_company_id'));
    }

    /** @test */
    public function it_can_access_dashboard_after_login_with_company(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $company = Company::factory()->create();
        $user->companies()->attach($company->id, [
            'role' => 'owner',
            'is_owner' => true,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        // Act - Login
        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Act - Access dashboard
        $response = $this->get('/dashboard');

        // Assert
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Dashboard/Index'));
    }
}
