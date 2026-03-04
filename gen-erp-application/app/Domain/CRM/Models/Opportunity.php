<?php

namespace App\Domain\CRM\Models;

use App\Domain\Auth\Models\Company;
use App\Domain\Customer\Models\Customer;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Opportunity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'pipeline_id',
        'stage_id',
        'lead_id',
        'customer_id',
        'assigned_to',
        'created_by',
        'name',
        'description',
        'amount',
        'currency',
        'probability',
        'expected_close_date',
        'actual_close_date',
        'status',
        'close_reason',
        'won_reason',
        'lost_reason',
        'stage_order',
        'source',
        'campaign',
        'products',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'last_activity_at',
        'won_at',
        'lost_at',
        'days_in_stage',
        'custom_fields',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'probability' => 'integer',
        'stage_order' => 'integer',
        'days_in_stage' => 'integer',
        'expected_close_date' => 'date',
        'actual_close_date' => 'date',
        'last_activity_at' => 'datetime',
        'won_at' => 'datetime',
        'lost_at' => 'datetime',
        'products' => 'array',
        'custom_fields' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($opportunity) {
            if (empty($opportunity->uuid)) {
                $opportunity->uuid = Str::uuid();
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

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class, 'stage_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmActivity::class, 'subject_id')
            ->where('subject_type', self::class);
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeWon($query)
    {
        return $query->where('status', 'won');
    }

    public function scopeLost($query)
    {
        return $query->where('status', 'lost');
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeInPipeline($query, $pipelineId)
    {
        return $query->where('pipeline_id', $pipelineId);
    }

    public function scopeInStage($query, $stageId)
    {
        return $query->where('stage_id', $stageId);
    }

    public function scopeExpectedToClose($query, $startDate, $endDate)
    {
        return $query->whereBetween('expected_close_date', [$startDate, $endDate]);
    }

    // Accessors
    public function getIsOpenAttribute(): bool
    {
        return $this->status === 'open';
    }

    public function getIsWonAttribute(): bool
    {
        return $this->status === 'won';
    }

    public function getIsLostAttribute(): bool
    {
        return $this->status === 'lost';
    }

    public function getIsClosedAttribute(): bool
    {
        return in_array($this->status, ['won', 'lost']);
    }

    public function getWeightedValueAttribute(): float
    {
        return round($this->amount * ($this->probability / 100), 2);
    }

    // Methods
    public function moveToStage(PipelineStage $stage, ?string $reason = null): void
    {
        $oldStage = $this->stage;
        
        // Execute exit actions for old stage
        if ($oldStage) {
            $oldStage->executeExitActions($this);
        }
        
        // Update opportunity
        $this->update([
            'stage_id' => $stage->id,
            'probability' => $stage->probability,
            'stage_order' => $stage->sort_order,
            'days_in_stage' => 0,
            'last_activity_at' => now(),
        ]);
        
        // Execute entry actions for new stage
        $stage->executeEntryActions($this);
        
        // Update stage metrics
        if ($oldStage) {
            $oldStage->updateMetrics();
        }
        $stage->updateMetrics();
    }

    public function markAsWon(?string $reason = null): void
    {
        $this->update([
            'status' => 'won',
            'won_at' => now(),
            'actual_close_date' => now(),
            'won_reason' => $reason,
            'probability' => 100,
        ]);
        
        $this->pipeline->updateMetrics();
    }

    public function markAsLost(?string $reason = null): void
    {
        $this->update([
            'status' => 'lost',
            'lost_at' => now(),
            'actual_close_date' => now(),
            'lost_reason' => $reason,
            'probability' => 0,
        ]);
        
        $this->pipeline->updateMetrics();
    }

    public function updateDaysInStage(): void
    {
        if ($this->updated_at) {
            $days = now()->diffInDays($this->updated_at);
            $this->update(['days_in_stage' => $days]);
        }
    }

    public function calculateTotalAmount(): void
    {
        $total = $this->amount + $this->tax_amount - $this->discount_amount;
        $this->update(['total_amount' => $total]);
    }
}