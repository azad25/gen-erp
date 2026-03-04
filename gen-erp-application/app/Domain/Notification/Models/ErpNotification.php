<?php

namespace App\Domain\Notification\Models;

use App\Domain\Auth\Models\User;
use App\Models\Company;
use Database\Factories\Domain\Notification\ErpNotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class ErpNotification extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'title_key',
        'body_key',
        'translation_params',
        'icon',
        'color',
        'action_url',
        'action_label_key',
        'domain',
        'meta',
        'read_at',
    ];

    protected $casts = [
        'translation_params' => 'array',
        'meta' => 'array',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'tenant_id');
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ErpNotificationFactory
    {
        return ErpNotificationFactory::new();
    }
}