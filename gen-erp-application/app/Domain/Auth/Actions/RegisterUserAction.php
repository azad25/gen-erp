<?php

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DataTransferObjects\UserRegistrationData;
use App\Domain\Auth\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Domain action for user registration.
 */
class RegisterUserAction
{
    /**
     * Execute user registration.
     */
    public function execute(UserRegistrationData $data): User
    {
        return User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
            'phone' => $data->phone,
            'email_verified_at' => null, // Require email verification
        ]);
    }
}
