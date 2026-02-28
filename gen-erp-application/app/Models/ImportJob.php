<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bulk data import job tracker.
 */
class ImportJob extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'entity_type',
        'file_path',
        'original_filename',
        'status',
        'total_rows',
        'processed_rows',
        'created_rows',
        'failed_rows',
        'errors',
        'created_by',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'created_rows' => 'integer',
            'failed_rows' => 'integer',
            'errors' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isComplete(): bool
    {
        return in_array($this->status, ['completed', 'failed'], true);
    }
}
