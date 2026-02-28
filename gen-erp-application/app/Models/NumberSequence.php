<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/**
 * Number sequence configuration per document type.
 */
class NumberSequence extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'document_type',
        'prefix',
        'suffix',
        'separator',
        'padding',
        'next_number',
        'reset_frequency',
        'last_reset_at',
        'include_date',
        'date_format',
    ];

    protected function casts(): array
    {
        return [
            'padding' => 'integer',
            'next_number' => 'integer',
            'include_date' => 'boolean',
            'last_reset_at' => 'date',
        ];
    }
}
