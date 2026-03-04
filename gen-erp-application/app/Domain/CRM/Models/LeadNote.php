<?php

namespace App\Domain\CRM\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LeadNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'lead_id',
        'user_id',
        'title',
        'content',
        'type',
        'is_private',
        'is_pinned',
        'attachments',
        'mentioned_users',
        'tags',
        'last_edited_at',
        'last_edited_by',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'is_pinned' => 'boolean',
        'attachments' => 'array',
        'mentioned_users' => 'array',
        'tags' => 'array',
        'last_edited_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($note) {
            if (empty($note->uuid)) {
                $note->uuid = Str::uuid();
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lastEditedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForLead($query, $leadId)
    {
        return $query->where('lead_id', $leadId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_private', true);
    }

    // Accessors
    public function getIsEditedAttribute(): bool
    {
        return $this->last_edited_at !== null;
    }

    public function getHasAttachmentsAttribute(): bool
    {
        return !empty($this->attachments);
    }

    // Methods
    public function pin(): void
    {
        $this->update(['is_pinned' => true]);
    }

    public function unpin(): void
    {
        $this->update(['is_pinned' => false]);
    }

    public function makePrivate(): void
    {
        $this->update(['is_private' => true]);
    }

    public function makePublic(): void
    {
        $this->update(['is_private' => false]);
    }

    public function markAsEdited(User $user): void
    {
        $this->update([
            'last_edited_at' => now(),
            'last_edited_by' => $user->id,
        ]);
    }
}