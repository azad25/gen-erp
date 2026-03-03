<?php

namespace App\Models;

use App\Domain\Auth\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/**
 * Unit of measure for products.
 */
class Unit extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'abbreviation',
    ];
}
