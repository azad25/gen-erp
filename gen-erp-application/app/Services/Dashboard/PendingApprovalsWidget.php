<?php

namespace App\Services\Dashboard;

use App\Enums\ApprovalStatus;
use App\Models\WorkflowApproval;

/**
 * Pending workflow approvals for the current user.
 */
class PendingApprovalsWidget extends BaseWidget
{
    public function getData(): array
    {
        $count = WorkflowApproval::withoutGlobalScopes()
            ->where('company_id', $this->company->id)
            ->where('status', ApprovalStatus::PENDING->value)
            ->count();

        return ['count' => $count];
    }

    public function getViewName(): string
    {
        return 'widgets.pending-approvals';
    }

    public function getTitle(): string
    {
        return __('Pending Approvals');
    }
}
