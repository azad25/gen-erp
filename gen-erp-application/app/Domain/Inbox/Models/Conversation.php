<?php

namespace App\Domain\Inbox\Models;

use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\Auth\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Conversation extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'title',
        'is_group',
        'created_by',
        'last_message_at',
        'metadata',
    ];

    protected $casts = [
        'is_group' => 'boolean',
        'last_message_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($conversation) {
            if (empty($conversation->uuid)) {
                $conversation->uuid = Str::uuid();
            }
        });
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['is_starred', 'is_muted', 'last_read_at', 'joined_at'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'desc');
    }

    // Scopes
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->whereHas('participants', function ($q) use ($userId) {
            $q->where('users.id', $userId);
        });
    }

    public function scopeStarredByUser($query, int $userId)
    {
        return $query->whereHas('participants', function ($q) use ($userId) {
            $q->where('users.id', $userId)
              ->where('conversation_participants.is_starred', true);
        });
    }

    // Methods
    public function getUnreadCount(int $userId): int
    {
        $participant = $this->participants()->where('users.id', $userId)->first();
        
        if (!$participant) {
            return 0;
        }

        $lastReadAt = $participant->pivot->last_read_at;

        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->when($lastReadAt, fn($q) => $q->where('created_at', '>', $lastReadAt))
            ->count();
    }

    public function markAsRead(int $userId): void
    {
        $this->participants()->updateExistingPivot($userId, [
            'last_read_at' => now(),
        ]);
    }

    public function toggleStar(int $userId): bool
    {
        $participant = $this->participants()->where('users.id', $userId)->first();
        
        if (!$participant) {
            return false;
        }

        $isStarred = !$participant->pivot->is_starred;
        
        $this->participants()->updateExistingPivot($userId, [
            'is_starred' => $isStarred,
        ]);

        return $isStarred;
    }

    public function toggleMute(int $userId): bool
    {
        $participant = $this->participants()->where('users.id', $userId)->first();
        
        if (!$participant) {
            return false;
        }

        $isMuted = !$participant->pivot->is_muted;
        
        $this->participants()->updateExistingPivot($userId, [
            'is_muted' => $isMuted,
        ]);

        return $isMuted;
    }

    public function getDisplayTitle(int $userId): string
    {
        if ($this->is_group) {
            return $this->title ?? 'Group Chat';
        }

        // For direct messages, show the other participant's name
        $otherParticipant = $this->participants()
            ->where('users.id', '!=', $userId)
            ->first();

        return $otherParticipant?->name ?? 'Unknown User';
    }
}
