<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A transition rule between two statuses in a workflow definition.
 */
class WorkflowTransition extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'workflow_definition_id',
        'company_id',
        'from_status_key',
        'to_status_key',
        'label',
        'allowed_roles',
        'requires_approval',
        'approval_type',
        'approver_roles',
        'auto_actions',
        'confirmation_message',
        'display_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allowed_roles' => 'array',
            'approver_roles' => 'array',
            'auto_actions' => 'array',
            'requires_approval' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<WorkflowDefinition, $this>
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }
}
