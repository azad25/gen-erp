<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
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
