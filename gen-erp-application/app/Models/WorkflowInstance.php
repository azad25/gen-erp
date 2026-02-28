<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An active workflow instance tracking a specific document's progress through its workflow.
 */
class WorkflowInstance extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'workflow_definition_id',
        'document_type',
        'document_id',
        'current_status_key',
        'started_at',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_id' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────

    /**
     * @return BelongsTo<WorkflowDefinition, $this>
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }

    /**
     * @return HasMany<WorkflowHistory, $this>
     */
    public function history(): HasMany
    {
        return $this->hasMany(WorkflowHistory::class)->orderBy('created_at');
    }

    /**
     * @return HasMany<WorkflowApproval, $this>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(WorkflowApproval::class);
    }

    // ── Methods ──────────────────────────────────────────────

    /**
     * Get the current WorkflowStatus model for this instance.
     */
    public function currentStatus(): ?WorkflowStatus
    {
        return $this->definition
            ->statuses()
            ->where('key', $this->current_status_key)
            ->first();
    }

    /**
     * Check if the current status is a terminal status.
     */
    public function isCompleted(): bool
    {
        $status = $this->currentStatus();

        return $status?->is_terminal ?? false;
    }
}
