<?php

namespace App\Domain\CRM\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PipelineStage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'pipeline_id',
        'created_by',
        'name',
        'description',
        'color',
        'sort_order',
        'is_active',
        'probability',
        'is_closed_won',
        'is_closed_lost',
        'requires_reason',
        'entry_actions',
        'exit_actions',
        'max_days_in_stage',
        'opportunities_count',
        'total_value',
        'average_days',
        'conversion_rate',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_closed_won' => 'boolean',
        'is_closed_lost' => 'boolean',
        'requires_reason' => 'boolean',
        'entry_actions' => 'array',
        'exit_actions' => 'array',
        'sort_order' => 'integer',
        'probability' => 'integer',
        'max_days_in_stage' => 'integer',
        'opportunities_count' => 'integer',
        'total_value' => 'decimal:2',
        'average_days' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($stage) {
            if (empty($stage->uuid)) {
                $stage->uuid = Str::uuid();
            }
        });
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'stage_id');
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPipeline($query, $pipelineId)
    {
        return $query->where('pipeline_id', $pipelineId);
    }

    public function scopeClosedWon($query)
    {
        return $query->where('is_closed_won', true);
    }

    public function scopeClosedLost($query)
    {
        return $query->where('is_closed_lost', true);
    }

    // Accessors
    public function getIsClosedAttribute(): bool
    {
        return $this->is_closed_won || $this->is_closed_lost;
    }

    public function getNextStageAttribute(): ?PipelineStage
    {
        return $this->pipeline->stages()
            ->where('sort_order', '>', $this->sort_order)
            ->orderBy('sort_order')
            ->first();
    }

    public function getPreviousStageAttribute(): ?PipelineStage
    {
        return $this->pipeline->stages()
            ->where('sort_order', '<', $this->sort_order)
            ->orderByDesc('sort_order')
            ->first();
    }

    // Methods
    public function updateMetrics(): void
    {
        $opportunities = $this->opportunities();
        
        $this->update([
            'opportunities_count' => $opportunities->count(),
            'total_value' => $opportunities->sum('amount'),
            'average_days' => $this->calculateAverageDays(),
            'conversion_rate' => $this->calculateConversionRate(),
        ]);
    }

    private function calculateAverageDays(): float
    {
        $opportunities = $this->opportunities()->whereNotNull('days_in_stage');
        
        if ($opportunities->count() === 0) return 0;
        
        return round($opportunities->avg('days_in_stage'), 2);
    }

    private function calculateConversionRate(): float
    {
        $nextStage = $this->next_stage;
        if (!$nextStage) return 0;
        
        $currentCount = $this->opportunities()->count();
        if ($currentCount === 0) return 0;
        
        $movedToNext = $nextStage->opportunities()->count();
        return round(($movedToNext / $currentCount) * 100, 2);
    }

    public function executeEntryActions(Opportunity $opportunity): void
    {
        $actions = $this->entry_actions ?? [];
        
        foreach ($actions as $action) {
            $this->executeAction($action, $opportunity);
        }
    }

    public function executeExitActions(Opportunity $opportunity): void
    {
        $actions = $this->exit_actions ?? [];
        
        foreach ($actions as $action) {
            $this->executeAction($action, $opportunity);
        }
    }

    private function executeAction(array $action, Opportunity $opportunity): void
    {
        // Implementation for stage actions (notifications, tasks, etc.)
        // This would be expanded based on specific business requirements
    }
}