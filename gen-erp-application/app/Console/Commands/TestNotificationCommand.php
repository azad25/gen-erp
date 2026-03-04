<?php

namespace App\Console\Commands;

use App\Domain\Notification\Events\SystemAlertFired;
use App\Domain\Auth\Models\User;
use Illuminate\Console\Command;

class TestNotificationCommand extends Command
{
    protected $signature = 'test:notification {user_id?} {--message=Test notification from ERP system}';
    protected $description = 'Test the new ERP notification system';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $message = $this->option('message');

        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return 1;
            }
            
            // Get tenant ID from user's companies
            $company = $user->companies()->first();
            if (!$company) {
                $this->error("User has no associated companies.");
                return 1;
            }
            $tenantId = $company->id;
            $this->info("Sending notification to user: {$user->name} (ID: {$userId})");
        } else {
            // Get first user for testing
            $user = User::first();
            if (!$user) {
                $this->error("No users found in the system.");
                return 1;
            }
            
            // Get tenant ID from user's companies
            $company = $user->companies()->first();
            if (!$company) {
                $this->error("User has no associated companies.");
                return 1;
            }
            $tenantId = $company->id;
            $userId = null; // Send to all users in tenant
            $this->info("Sending notification to all users in tenant: {$tenantId}");
        }

        // Fire the event
        SystemAlertFired::dispatch($message, 'info', $tenantId, $userId);

        $this->info("✅ Notification dispatched successfully!");
        $this->info("Check the erp_notifications table and WebSocket connections.");

        return 0;
    }
}