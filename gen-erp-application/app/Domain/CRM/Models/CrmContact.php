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

class CrmContact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'lead_id',
        'customer_id',
        'created_by',
        'first_name',
        'last_name',
        'email',
        'phone',
        'mobile',
        'job_title',
        'department',
        'company_name',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'website',
        'linkedin_url',
        'twitter_handle',
        'is_primary',
        'is_decision_maker',
        'communication_preferences',
        'timezone',
        'language',
        'birthday',
        'notes',
        'custom_fields',
        'last_contacted_at',
        'is_active',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_decision_maker' => 'boolean',
        'is_active' => 'boolean',
        'communication_preferences' => 'array',
        'custom_fields' => 'array',
        'birthday' => 'date',
        'last_contacted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($contact) {
            if (empty($contact->uuid)) {
                $contact->uuid = Str::uuid();
            }
        });
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
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

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getPreferredContactMethodAttribute(): ?string
    {
        $preferences = $this->communication_preferences ?? [];
        return $preferences['preferred_method'] ?? null;
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

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeDecisionMakers($query)
    {
        return $query->where('is_decision_maker', true);
    }

    // Methods
    public function markAsContacted(): void
    {
        $this->update(['last_contacted_at' => now()]);
    }
}