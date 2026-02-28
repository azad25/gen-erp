<?php

namespace App\Models;

use App\Enums\WorkflowDocumentType;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A company-specific workflow definition for a document type.
 */
class WorkflowDefinition extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'document_type',
        'name',
        'is_active',
        'is_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_type' => WorkflowDocumentType::class,
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────────────

    /**
     * @return HasMany<WorkflowStatus, $this>
     */
    public function statuses(): HasMany
    {
        return $this->hasMany(WorkflowStatus::class)->orderBy('display_order');
    }

    /**
     * @return HasMany<WorkflowTransition, $this>
     */
    public function transitions(): HasMany
    {
        return $this->hasMany(WorkflowTransition::class)->orderBy('display_order');
    }

    /**
     * @return HasMany<WorkflowInstance, $this>
     */
    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class);
    }

    // ── Scopes ───────────────────────────────────────────────

    /**
     * @param  Builder<WorkflowDefinition>  $query
     * @return Builder<WorkflowDefinition>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<WorkflowDefinition>  $query
     * @return Builder<WorkflowDefinition>
     */
    public function scopeForDocument(Builder $query, string $type): Builder
    {
        return $query->where('document_type', $type);
    }

    // ── Methods ──────────────────────────────────────────────

    /**
     * Get the initial status for this workflow.
     */
    public function initialStatus(): ?WorkflowStatus
    {
        return $this->statuses()->where('is_initial', true)->first();
    }
}
