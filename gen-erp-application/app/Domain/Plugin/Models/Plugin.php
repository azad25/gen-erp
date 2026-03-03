<?php

namespace App\Domain\Plugin\Models;

use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plugin extends Model
{
    use HasFactory, BelongsToCompany;

    public const STATUS_ENABLED = 'enabled';
    public const STATUS_DISABLED = 'disabled';
    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'version',
        'author',
        'description',
        'manifest',
        'status',
        'source',
        'installed_at',
        'enabled_at',
        'error_message',
    ];

    protected $casts = [
        'manifest' => 'array',
        'installed_at' => 'datetime',
        'enabled_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isEnabled(): bool
    {
        return $this->status === self::STATUS_ENABLED;
    }

    public function isDisabled(): bool
    {
        return $this->status === self::STATUS_DISABLED;
    }

    public function hasError(): bool
    {
        return $this->status === self::STATUS_ERROR;
    }

    public function getHooks(): array
    {
        return $this->manifest['hooks'] ?? [];
    }

    public function getHookForEvent(string $event): ?array
    {
        return $this->getHooks()[$event] ?? null;
    }
}