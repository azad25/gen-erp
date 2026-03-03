<?php

namespace App\Domain\Shared\Models;

use App\Domain\Auth\Models\Concerns\BelongsToCompany;
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
