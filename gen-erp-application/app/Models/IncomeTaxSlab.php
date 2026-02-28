<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * BD income tax slab for a fiscal year.
 */
class IncomeTaxSlab extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'fiscal_year',
        'min_income',
        'max_income',
        'tax_rate',
        'description',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'min_income' => 'integer',
            'max_income' => 'integer',
            'tax_rate' => 'float',
        ];
    }

    /** @param Builder<self> $query */
    public function scopeForFiscalYear(Builder $query, string $year): Builder
    {
        return $query->where('fiscal_year', $year)->orderBy('display_order');
    }
}
