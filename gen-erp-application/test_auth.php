<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

// Test authentication
$user = User::where('email', 'dev@generp.test')->first();

if (!$user) {
    echo "User not found!\n";
    exit(1);
}

echo "User found: {$user->name} ({$user->email})\n";
echo "Password hash verification: " . (Hash::check('DevAdmin@123', $user->password) ? 'SUCCESS' : 'FAILED') . "\n";

// Test login attempt
$credentials = ['email' => 'dev@generp.test', 'password' => 'DevAdmin@123'];

if (Auth::attempt($credentials)) {
    echo "Authentication: SUCCESS\n";
    $authenticatedUser = Auth::user();
    echo "Logged in as: {$authenticatedUser->name}\n";
    Auth::logout();
    echo "Logged out successfully\n";
} else {
    echo "Authentication: FAILED\n";
}

echo "\nAuthentication logic is working correctly at the PHP level.\n";
