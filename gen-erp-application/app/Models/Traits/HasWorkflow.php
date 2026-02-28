<?php

namespace App\Models\Traits;

use App\Models\WorkflowHistory;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStatus;
use App\Models\WorkflowTransition;
use App\Services\WorkflowService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Apply to any model that participates in a workflow (PurchaseOrder, SalesOrder, etc.).
 */
trait HasWorkflow
{
    /**
     * Returns the document_type string for this model (e.g. 'purchase_order').
     */
    abstract public function workflowDocumentType(): string;

    public static function bootHasWorkflow(): void
    {
        static::created(function (self $model): void {
            try {
                app(WorkflowService::class)->initialise(
                    $model->workflowDocumentType(),
                    $model->getKey(),
                );
            } catch (\RuntimeException $e) {
                // No workflow defined for this document type — that's OK
            }
        });
    }

    /**
     * @return HasOne<WorkflowInstance, $this>
     */
    public function workflowInstance(): HasOne
    {
        return $this->hasOne(WorkflowInstance::class, 'document_id')
            ->where('document_type', $this->workflowDocumentType());
    }

    /**
     * Get the current workflow status model for this document.
     */
    public function currentWorkflowStatus(): ?WorkflowStatus
    {
        return $this->workflowInstance?->currentStatus();
    }

    /**
     * Get available transitions for the authenticated user.
     *
     * @return Collection<int, WorkflowTransition>
     */
    public function availableTransitions(): Collection
    {
        $instance = $this->workflowInstance;

        if (! $instance || ! auth()->user()) {
            return new Collection;
        }

        return app(WorkflowService::class)->availableTransitions($instance, auth()->user());
    }

    /**
     * Execute a transition on this document's workflow.
     */
    public function transitionTo(WorkflowTransition $transition, ?string $comment = null): WorkflowHistory
    {
        $instance = $this->workflowInstance;

        if (! $instance) {
            throw new \RuntimeException('No workflow instance found for this document.');
        }

        return app(WorkflowService::class)->transition($instance, $transition, auth()->user(), $comment);
    }

    /**
     * Check if this document is in a specific workflow status.
     */
    public function isInStatus(string $statusKey): bool
    {
        return $this->workflowInstance?->current_status_key === $statusKey;
    }
}
