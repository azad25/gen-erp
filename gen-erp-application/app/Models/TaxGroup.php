<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/**
 * Stub TaxGroup model — full implementation in Phase 5.
 */
class TaxGroup extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'rate',
        'is_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'float',
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
