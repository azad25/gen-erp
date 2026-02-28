<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Contact group for categorising customers and suppliers.
 */
class ContactGroup extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'description',
    ];

    /**
     * @return HasMany<Customer, $this>
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'group_id');
    }

    /**
     * @return HasMany<Supplier, $this>
     */
    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class, 'group_id');
    }
}
