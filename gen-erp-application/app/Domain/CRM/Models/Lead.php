<?php

namespace App\Domain\CRM\Models;

use App\Domain\CRM\Enums\LeadStatus;
use App\Domain\CRM\Enums\LeadSource;
use App\Domain\Auth\Models\Company;
use App\Domain\Customer\Models\Customer;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'assigned_to',
        'created_by',
        'first_name',
        'last_name',
        'email',
        'phone',
        'company_name',
        'job_title',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'status',
        'source',
        'score',
        'estimated_value',
        'currency',
        'expected_close_date',
        'last_contacted_at',
        'qualified_at',
        'converted_at',
        'converted_to_customer_id',
        'custom_fields',
        'notes',
    ];

    protected $casts = [
        'status' => LeadStatus::class,
        'source' => LeadSource::class,
        'estimated_value' => 'decimal:2',
        'expected_close_date' => 'date',
        'last_contacted_at' => 'datetime',
        'qualified_at' => 'datetime',
        'converted_at' => 'datetime',
        'custom_fields' => 'array',
        'score' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($lead) {
            if (empty($lead->uuid)) {
                $lead->uuid = Str::uuid();
            }
        });
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function convertedToCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'converted_to_customer_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(LeadNote::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmActivity::class, 'subject_id')
            ->where('subject_type', self::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(LeadTag::class, 'lead_tag_pivot')
            ->withPivot(['tagged_by', 'tagged_at', 'notes', 'is_auto_tagged']);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getIsQualifiedAttribute(): bool
    {
        return $this->status === LeadStatus::QUALIFIED;
    }

    public function getIsConvertedAttribute(): bool
    {
        return $this->status === LeadStatus::CONVERTED;
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeByStatus($query, LeadStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeBySource($query, LeadSource $source)
    {
        return $query->where('source', $source);
    }

    public function scopeHighScore($query, int $minScore = 70)
    {
        return $query->where('score', '>=', $minScore);
    }

    public function scopeRecentlyContacted($query, int $days = 7)
    {
        return $query->where('last_contacted_at', '>=', now()->subDays($days));
    }

    // Methods
    public function updateScore(int $score): void
    {
        $this->update(['score' => max(0, min(100, $score))]);
    }

    public function markAsContacted(): void
    {
        $this->update(['last_contacted_at' => now()]);
    }

    public function qualify(): void
    {
        $this->update([
            'status' => LeadStatus::QUALIFIED,
            'qualified_at' => now(),
        ]);
    }

    public function convertToCustomer(Customer $customer): void
    {
        $this->update([
            'status' => LeadStatus::CONVERTED,
            'converted_at' => now(),
            'converted_to_customer_id' => $customer->id,
        ]);
    }
}