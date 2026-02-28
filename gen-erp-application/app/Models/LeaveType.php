<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Leave type configuration for a company (Annual, Sick, Maternity, etc).
 */
class LeaveType extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'days_per_year',
        'is_paid',
        'carry_forward',
        'max_carry_forward_days',
        'requires_approval',
    ];

    protected function casts(): array
    {
        return [
            'days_per_year' => 'integer',
            'is_paid' => 'boolean',
            'carry_forward' => 'boolean',
            'max_carry_forward_days' => 'integer',
            'requires_approval' => 'boolean',
        ];
    }

    /** @return HasMany<LeaveBalance, $this> */
    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }
}
