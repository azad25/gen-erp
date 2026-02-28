<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single status in a workflow definition (e.g. Draft, Pending Approval, Approved).
 */
class WorkflowStatus extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'workflow_definition_id',
        'company_id',
        'key',
        'label',
        'color',
        'is_initial',
        'is_terminal',
        'display_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_initial' => 'boolean',
            'is_terminal' => 'boolean',
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
