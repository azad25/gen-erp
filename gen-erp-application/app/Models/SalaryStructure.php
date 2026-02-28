<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Salary structure — a named collection of salary components.
 */
class SalaryStructure extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'name', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    /** @return HasMany<SalaryComponent, $this> */
    public function components(): HasMany
    {
        return $this->hasMany(SalaryComponent::class)->orderBy('display_order');
    }
}
