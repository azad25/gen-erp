<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A saved report configuration for the report builder.
 */
class SavedReport extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'created_by',
        'name',
        'entity_type',
        'selected_fields',
        'filters',
        'group_by',
        'aggregate',
        'sort_field',
        'sort_direction',
        'visualisation',
        'is_scheduled',
        'schedule_frequency',
        'schedule_recipients',
        'last_run_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'selected_fields' => 'array',
            'filters' => 'array',
            'aggregate' => 'array',
            'schedule_recipients' => 'array',
            'is_scheduled' => 'boolean',
            'last_run_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
