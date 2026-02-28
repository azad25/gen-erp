<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents an installed plugin for a company.
 */
class Plugin extends Model
{
    use BelongsToCompany;

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
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'manifest' => 'array',
            'installed_at' => 'datetime',
            'enabled_at' => 'datetime',
        ];
    }

    // ── Status Constants ─────────────────────────────────────

    public const STATUS_DISABLED = 'disabled';
    public const STATUS_ENABLED = 'enabled';
    public const STATUS_ERROR = 'error';

    // ── Scopes ───────────────────────────────────────────────

    /**
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', self::STATUS_ENABLED);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeDisabled($query)
    {
        return $query->where('status', self::STATUS_DISABLED);
    }

    /**
     * Check if plugin is currently enabled.
     */
    public function isEnabled(): bool
    {
        return $this->status === self::STATUS_ENABLED;
    }
}
