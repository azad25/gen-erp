<?php

namespace Tests\Feature\Api\Auth;

use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_register_new_user(): void
    {
        // Act
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '01712345678',
        ]);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Registration successful. Please setup your company.',
                'data' => [
                    'user' => [
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                    ],
                    'token_type' => 'Bearer',
                    'requires_company_setup' => true,
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'expires_at',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '01712345678',
        ]);
    }

    /** @test */
    public function it_validates_registration_input(): void
    {
        // Act
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'phone' => 'invalid-phone',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'phone']);
    }

    /** @test */
    public function it_prevents_duplicate_email_registration(): void
    {
        // Arrange
        User::factory()->create(['email' => 'existing@example.com']);

        // Act
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_can_login_with_valid_credentials(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // Act
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful.',
                'data' => [
                    'user' => [
                        'email' => 'test@example.com',
                    ],
                    'token_type' => 'Bearer',
                    'requires_company_selection' => false,
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'expires_at',
                ],
            ]);
    }

    /** @test */
    public function it_fails_login_with_invalid_credentials(): void
    {
        // Arrange
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Act
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        // Assert
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'These credentials do not match our records.',
            ]);
    }

    /** @test */
    public function it_requires_email_verification_for_login(): void
    {
        // Arrange
        User::factory()->create([
            'email' => 'unverified@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => null,
        ]);

        // Act
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'unverified@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Please verify your email address first.',
                'requires_verification' => true,
            ]);
    }

    /** @test */
    public function it_can_setup_company_after_registration(): void
    {
        // Arrange
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Act
        $response = $this->postJson('/api/v1/auth/setup-company', [
            'name' => 'Test Company Ltd',
            'business_type' => 'retail',
            'country' => 'BD',
            'currency' => 'BDT',
            'address_line1' => '123 Main Street',
            'city' => 'Dhaka',
            'district' => 'Dhaka',
            'phone' => '01712345678',
            'email' => 'company@example.com',
            'vat_bin' => '123456789012',
        ]);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Company setup completed successfully.',
                'data' => [
                    'company' => [
                        'name' => 'Test Company Ltd',
                        'business_type' => 'retail',
                        'country' => 'BD',
                        'currency' => 'BDT',
                        'address_line1' => '123 Main Street',
                        'city' => 'Dhaka',
                        'district' => 'Dhaka',
                        'phone' => '01712345678',
                        'email' => 'company@example.com',
                        'vat_bin' => '123456789012',
                    ],
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'permissions',
                ],
            ]);

        $this->assertDatabaseHas('companies', [
            'name' => 'Test Company Ltd',
            'business_type' => 'retail',
        ]);

        // Check user is attached as owner
        $this->assertTrue($user->companies()->exists());
        $user->refresh(); // Refresh to get updated relationships
        $pivot = $user->companies()->first()->pivot;
        $this->assertEquals('owner', $pivot->role);
        $this->assertTrue((bool) $pivot->is_owner);
    }

    /** @test */
    public function it_validates_company_setup_input(): void
    {
        // Arrange
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Act
        $response = $this->postJson('/api/v1/auth/setup-company', [
            'name' => '',
            'business_type' => 'invalid-type',
            'phone' => 'invalid-phone',
            'vat_bin' => '123', // Too short
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'business_type', 'phone', 'vat_bin']);
    }

    /** @test */
    public function it_can_get_authenticated_user_data(): void
    {
        // Arrange
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $user->companies()->attach($company->id, [
            'role' => 'owner',
            'is_owner' => true,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        Sanctum::actingAs($user);

        // Act
        $response = $this->getJson('/api/v1/auth/user');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'company',
                    'permissions',
                    'companies',
                ],
            ]);
    }

    /** @test */
    public function it_can_switch_company(): void
    {
        // Arrange
        $user = User::factory()->create();
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

        Sanctum::actingAs($user);

        // Act
        $response = $this->postJson("/api/v1/auth/switch-company/{$company2->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Company switched successfully.',
                'data' => [
                    'company' => [
                        'id' => $company2->id,
                    ],
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'permissions',
                ],
            ]);

        // Check user's last active company was updated
        $user->refresh();
        $this->assertEquals($company2->id, $user->last_active_company_id);
    }

    /** @test */
    public function it_prevents_switching_to_unauthorized_company(): void
    {
        // Arrange
        $user = User::factory()->create();
        $company = Company::factory()->create();
        Sanctum::actingAs($user);

        // Act
        $response = $this->postJson("/api/v1/auth/switch-company/{$company->id}");

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Company not found or access denied.',
            ]);
    }

    /** @test */
    public function it_can_logout(): void
    {
        // Arrange
        $user = User::factory()->create();
        $token = $user->createToken('test-token');
        
        // Act - Use the actual token for authentication
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
        ])->postJson('/api/v1/auth/logout');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully.',
            ]);

        // Token should be deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    /** @test */
    public function it_requires_authentication_for_protected_routes(): void
    {
        // Act & Assert
        $this->getJson('/api/v1/auth/user')->assertStatus(401);
        $this->postJson('/api/v1/auth/logout')->assertStatus(401);
        $this->postJson('/api/v1/auth/setup-company', [])->assertStatus(401);
        $this->postJson('/api/v1/auth/switch-company/1')->assertStatus(401);
    }
}
