<?php

use App\Models\User;

test('user can register with valid data', function (): void {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '01712345678',
    ]);

    $response->assertRedirect(route('setup.company'));
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'phone' => '01712345678']);
});

test('registration fails with duplicate email', function (): void {
    User::factory()->create(['email' => 'duplicate@example.com']);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'duplicate@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
});

test('phone validation rejects non-BD format', function (): void {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '12345678',
    ]);

    $response->assertSessionHasErrors('phone');
});
