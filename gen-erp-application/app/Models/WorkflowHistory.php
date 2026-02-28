<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Immutable workflow history entry recording a status transition.
 */
class WorkflowHistory extends Model
{
    use BelongsToCompany;

    /**
     * Only uses created_at — immutable record.
     */
    public const UPDATED_AT = null;

    protected $table = 'workflow_history';

    protected $fillable = [
        'company_id',
        'workflow_instance_id',
        'from_status_key',
        'to_status_key',
        'transition_id',
        'triggered_by',
        'comment',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transition_id' => 'integer',
            'triggered_by' => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────

    /**
     * @return BelongsTo<WorkflowInstance, $this>
     */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    // ── Immutability ─────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $options
     *
     * @throws LogicException
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new LogicException('Workflow history entries are immutable and cannot be updated.');
        }

        return parent::save($options);
    }

    /**
     * @throws LogicException
     */
    public function delete(): ?bool
    {
        throw new LogicException('Workflow history entries cannot be deleted.');
    }
}
