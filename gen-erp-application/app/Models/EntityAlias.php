<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores company-specific aliases for entity names (e.g. "Customer" → "Client").
 */
class EntityAlias extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'entity_key',
        'alias',
    ];
}
