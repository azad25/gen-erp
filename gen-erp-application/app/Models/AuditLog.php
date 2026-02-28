<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * Immutable audit log entry tracking changes to auditable models.
 */
class AuditLog extends Model
{
    use BelongsToCompany;

    /**
     * Indicates that this model only uses `created_at`, not `updated_at`.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'company_id',
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'auditable_id' => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    // ── Immutability ─────────────────────────────────────────

    /**
     * Override save to enforce immutability — existing audit logs cannot be updated.
     *
     * @param  array<string, mixed>  $options
     *
     * @throws LogicException
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new LogicException('Audit log entries are immutable and cannot be updated.');
        }

        return parent::save($options);
    }

    /**
     * Prevent deletion of audit logs.
     *
     * @throws LogicException
     */
    public function delete(): ?bool
    {
        throw new LogicException('Audit log entries cannot be deleted.');
    }
}
