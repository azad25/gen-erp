<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/**
 * Payment method available for a company (Cash, bKash, Bank Transfer, etc).
 */
class PaymentMethod extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'account_reference',
        'is_active',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<static>  $query
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
