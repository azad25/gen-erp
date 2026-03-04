<?php

namespace App\Domain\CRM\Models;

use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Pipeline extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'created_by',
        'name',
        'description',
        'color',
        'is_default',
        'is_active',
        'sort_order',
        'settings',
        'auto_move_stages',
        'default_probability',
        'opportunities_count',
        'total_value',
        'won_value',
        'lost_value',
        'conversion_rate',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'auto_move_stages' => 'boolean',
        'settings' => 'array',
        'default_probability' => 'integer',
        'opportunities_count' => 'integer',
        'total_value' => 'decimal:2',
        'won_value' => 'decimal:2',
        'lost_value' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($pipeline) {
            if (empty($pipeline->uuid)) {
                $pipeline->uuid = Str::uuid();
            }
        });
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(PipelineStage::class)->orderBy('sort_order');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
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

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // Methods
    public function updateMetrics(): void
    {
        $opportunities = $this->opportunities();
        
        $this->update([
            'opportunities_count' => $opportunities->count(),
            'total_value' => $opportunities->sum('amount'),
            'won_value' => $opportunities->where('status', 'won')->sum('amount'),
            'lost_value' => $opportunities->where('status', 'lost')->sum('amount'),
            'conversion_rate' => $this->calculateConversionRate(),
        ]);
    }

    private function calculateConversionRate(): float
    {
        $total = $this->opportunities()->count();
        if ($total === 0) return 0;
        
        $won = $this->opportunities()->where('status', 'won')->count();
        return round(($won / $total) * 100, 2);
    }

    public function createDefaultStages(): void
    {
        $defaultStages = [
            ['name' => 'Prospecting', 'probability' => 10, 'sort_order' => 1, 'color' => '#6B7280'],
            ['name' => 'Qualification', 'probability' => 25, 'sort_order' => 2, 'color' => '#3B82F6'],
            ['name' => 'Needs Analysis', 'probability' => 50, 'sort_order' => 3, 'color' => '#F59E0B'],
            ['name' => 'Proposal', 'probability' => 75, 'sort_order' => 4, 'color' => '#8B5CF6'],
            ['name' => 'Negotiation', 'probability' => 90, 'sort_order' => 5, 'color' => '#EF4444'],
            ['name' => 'Closed Won', 'probability' => 100, 'sort_order' => 6, 'color' => '#10B981', 'is_closed_won' => true],
            ['name' => 'Closed Lost', 'probability' => 0, 'sort_order' => 7, 'color' => '#6B7280', 'is_closed_lost' => true],
        ];

        foreach ($defaultStages as $stageData) {
            $this->stages()->create(array_merge($stageData, [
                'company_id' => $this->company_id,
                'created_by' => $this->created_by,
            ]));
        }
    }
}