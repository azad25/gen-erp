<?php

namespace App\Domain\HR\Models;

use App\Support\Enums\CalculationType;
use App\Support\Enums\ComponentType;
use App\Domain\Auth\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Individual salary component within a structure (e.g. Basic, House Rent, PF Deduction).
 */
class SalaryComponent extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'salary_structure_id',
        'company_id',
        'name',
        'component_type',
        'calculation_type',
        'value',
        'is_taxable',
        'is_mandatory',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'component_type' => ComponentType::class,
            'calculation_type' => CalculationType::class,
            'value' => 'float',
            'is_taxable' => 'boolean',
            'is_mandatory' => 'boolean',
        ];
    }

    /** @return BelongsTo<SalaryStructure, $this> */
    public function structure(): BelongsTo
    {
        return $this->belongsTo(SalaryStructure::class, 'salary_structure_id');
    }
}
