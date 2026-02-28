<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Models\CompanyUser;
use App\Models\User;
use App\Models\WorkflowApproval;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowHistory;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStatus;
use App\Models\WorkflowTransition;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Core workflow engine: initialise, transition, and approve document workflows.
 */
class WorkflowService
{
    /**
     * Initialise a workflow instance for a new document.
     */
    public function initialise(string $documentType, int $documentId): WorkflowInstance
    {
        $companyId = CompanyContext::activeId();

        $definition = WorkflowDefinition::query()
            ->active()
            ->forDocument($documentType)
            ->where('is_default', true)
            ->first();

        if (! $definition) {
            $definition = WorkflowDefinition::query()
                ->active()
                ->forDocument($documentType)
                ->first();
        }

        if (! $definition) {
            throw new \RuntimeException(
                "No active workflow definition found for document type '{$documentType}'."
            );
        }

        $initialStatus = $definition->initialStatus();

        if (! $initialStatus) {
            throw new \RuntimeException(
                "Workflow definition '{$definition->name}' has no initial status."
            );
        }

        $instance = WorkflowInstance::create([
            'company_id' => $companyId,
            'workflow_definition_id' => $definition->id,
            'document_type' => $documentType,
            'document_id' => $documentId,
            'current_status_key' => $initialStatus->key,
            'started_at' => now(),
        ]);

        // Record the initial history entry
        WorkflowHistory::create([
            'company_id' => $companyId,
            'workflow_instance_id' => $instance->id,
            'from_status_key' => null,
            'to_status_key' => $initialStatus->key,
            'transition_id' => null,
            'triggered_by' => auth()->id(),
            'comment' => 'Workflow initialised.',
        ]);

        return $instance;
    }

    /**
     * Get available transitions for a document given the current user's role.
     *
     * @return Collection<int, WorkflowTransition>
     */
    public function availableTransitions(WorkflowInstance $instance, User $user): Collection
    {
        if ($instance->isCompleted()) {
            return new Collection;
        }

        $userRole = $this->getUserRoleInCompany($user, $instance->company_id);

        return $instance->definition
            ->transitions()
            ->where('from_status_key', $instance->current_status_key)
            ->get()
            ->filter(function (WorkflowTransition $transition) use ($userRole): bool {
                $allowedRoles = $transition->allowed_roles ?? [];

                return in_array($userRole, $allowedRoles) || in_array('*', $allowedRoles);
            })
            ->values();
    }

    /**
     * Execute a transition on a workflow instance.
     *
     * @throws \RuntimeException
     */
    public function transition(
        WorkflowInstance $instance,
        WorkflowTransition $transition,
        User $user,
        ?string $comment = null,
    ): WorkflowHistory {
        // 1. Verify transition belongs to the instance's workflow
        if ($transition->workflow_definition_id !== $instance->workflow_definition_id) {
            throw new \RuntimeException('Transition does not belong to this workflow definition.');
        }

        // 2. Verify from_status_key matches current status
        if ($transition->from_status_key !== $instance->current_status_key) {
            throw new \RuntimeException(
                "Cannot transition: current status is '{$instance->current_status_key}', "
                ."but transition expects '{$transition->from_status_key}'."
            );
        }

        // 3. Verify user has allowed role
        $userRole = $this->getUserRoleInCompany($user, $instance->company_id);
        $allowedRoles = $transition->allowed_roles ?? [];

        if (! in_array($userRole, $allowedRoles) && ! in_array('*', $allowedRoles)) {
            throw new \RuntimeException(
                'User does not have permission to execute this transition. Required roles: '
                .implode(', ', $allowedRoles)
            );
        }

        // 4. If requires approval — create approval records and return pending history
        if ($transition->requires_approval) {
            return $this->handleApprovalTransition($instance, $transition, $user, $comment);
        }

        // 5. Direct transition — update status and record history
        return $this->executeDirectTransition($instance, $transition, $user, $comment);
    }

    /**
     * Get current status of a document.
     */
    public function currentStatus(string $documentType, int $documentId): ?WorkflowStatus
    {
        $instance = WorkflowInstance::query()
            ->where('document_type', $documentType)
            ->where('document_id', $documentId)
            ->first();

        return $instance?->currentStatus();
    }

    /**
     * Check if a document is in a specific status.
     */
    public function isInStatus(string $documentType, int $documentId, string $statusKey): bool
    {
        return WorkflowInstance::query()
            ->where('document_type', $documentType)
            ->where('document_id', $documentId)
            ->where('current_status_key', $statusKey)
            ->exists();
    }

    /**
     * Respond to an approval request.
     */
    public function respondToApproval(
        WorkflowApproval $approval,
        ApprovalStatus $status,
        User $user,
        ?string $comment = null,
    ): void {
        $approval->update([
            'status' => $status->value,
            'comment' => $comment,
            'responded_at' => now(),
        ]);

        $instance = $approval->instance;
        $transition = $approval->transition;

        if ($status === ApprovalStatus::REJECTED) {
            // Any rejection → reject the transition, revert to from_status
            WorkflowHistory::create([
                'company_id' => $instance->company_id,
                'workflow_instance_id' => $instance->id,
                'from_status_key' => $instance->current_status_key,
                'to_status_key' => $transition->from_status_key,
                'transition_id' => $transition->id,
                'triggered_by' => $user->id,
                'comment' => $comment ?? 'Approval rejected.',
            ]);

            $instance->update(['current_status_key' => $transition->from_status_key]);

            // Mark all other pending approvals for this transition as rejected
            WorkflowApproval::withoutGlobalScopes()
                ->where('workflow_instance_id', $instance->id)
                ->where('transition_id', $transition->id)
                ->where('status', ApprovalStatus::PENDING->value)
                ->update([
                    'status' => ApprovalStatus::REJECTED->value,
                    'responded_at' => now(),
                ]);

            return;
        }

        // Check if all approvals are now approved
        $pendingCount = WorkflowApproval::withoutGlobalScopes()
            ->where('workflow_instance_id', $instance->id)
            ->where('transition_id', $transition->id)
            ->where('status', ApprovalStatus::PENDING->value)
            ->count();

        if ($pendingCount === 0) {
            // All approved — execute the transition
            $this->executeDirectTransition($instance, $transition, $user, 'All approvals received.');
        }
    }

    /**
     * Handle a transition that requires approval.
     */
    private function handleApprovalTransition(
        WorkflowInstance $instance,
        WorkflowTransition $transition,
        User $user,
        ?string $comment,
    ): WorkflowHistory {
        $approverRoles = $transition->approver_roles ?? [];
        $companyId = $instance->company_id;

        // Find users with the approver roles in this company
        $approvers = CompanyUser::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereIn('role', $approverRoles)
            ->where('is_active', true)
            ->get();

        foreach ($approvers as $companyUser) {
            WorkflowApproval::create([
                'company_id' => $companyId,
                'workflow_instance_id' => $instance->id,
                'transition_id' => $transition->id,
                'approver_id' => $companyUser->user_id,
                'status' => ApprovalStatus::PENDING->value,
            ]);
        }

        // Move to a pending state — keep current status but record history
        $history = WorkflowHistory::create([
            'company_id' => $companyId,
            'workflow_instance_id' => $instance->id,
            'from_status_key' => $instance->current_status_key,
            'to_status_key' => $instance->current_status_key,
            'transition_id' => $transition->id,
            'triggered_by' => $user->id,
            'comment' => $comment ?? 'Submitted for approval.',
        ]);

        return $history;
    }

    /**
     * Execute a direct (non-approval) transition.
     */
    private function executeDirectTransition(
        WorkflowInstance $instance,
        WorkflowTransition $transition,
        User $user,
        ?string $comment,
    ): WorkflowHistory {
        $fromKey = $instance->current_status_key;
        $toKey = $transition->to_status_key;

        // Update the instance status
        $instance->update(['current_status_key' => $toKey]);

        // Check if the new status is terminal
        $toStatus = $instance->definition->statuses()->where('key', $toKey)->first();
        if ($toStatus?->is_terminal) {
            $instance->update(['completed_at' => now()]);
        }

        // Record history
        $history = WorkflowHistory::create([
            'company_id' => $instance->company_id,
            'workflow_instance_id' => $instance->id,
            'from_status_key' => $fromKey,
            'to_status_key' => $toKey,
            'transition_id' => $transition->id,
            'triggered_by' => $user->id,
            'comment' => $comment,
        ]);

        // Fire auto_actions (Phase 2 stubs)
        $this->fireAutoActions($transition, $instance);

        return $history;
    }

    /**
     * Fire auto_actions defined on a transition.
     */
    private function fireAutoActions(WorkflowTransition $transition, WorkflowInstance $instance): void
    {
        $actions = $transition->auto_actions ?? [];

        foreach ($actions as $action) {
            $type = $action['type'] ?? null;

            match ($type) {
                'notify_roles' => $this->handleNotifyRoles($action, $instance),
                'set_field' => $this->handleSetField($action, $instance),
                // TODO: Phase 3+ — 'update_stock', 'create_journal_entry'
                default => Log::warning("Unknown auto_action type: {$type}"),
            };
        }
    }

    /**
     * @param  array<string, mixed>  $action
     */
    private function handleNotifyRoles(array $action, WorkflowInstance $instance): void
    {
        // TODO: Phase 3 — dispatch notifications to role holders
        Log::info('Auto-action notify_roles triggered', [
            'instance_id' => $instance->id,
            'roles' => $action['roles'] ?? [],
        ]);
    }

    /**
     * @param  array<string, mixed>  $action
     */
    private function handleSetField(array $action, WorkflowInstance $instance): void
    {
        // TODO: Phase 3 — update a field on the document model
        Log::info('Auto-action set_field triggered', [
            'instance_id' => $instance->id,
            'field' => $action['field'] ?? null,
            'value' => $action['value'] ?? null,
        ]);
    }

    /**
     * Get user's role in a company.
     */
    private function getUserRoleInCompany(User $user, int $companyId): ?string
    {
        $companyUser = CompanyUser::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('user_id', $user->id)
            ->first();

        return $companyUser?->role?->value ?? $companyUser?->role;
    }
}
