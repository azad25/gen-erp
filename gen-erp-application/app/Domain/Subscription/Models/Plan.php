<?php

namespace App\Domain\Subscription\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'monthly_price',
        'annual_price',
        'limits',
        'feature_flags',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'limits' => 'array',
        'feature_flags' => 'array',
        'is_active' => 'boolean',
        'monthly_price' => 'integer',
        'annual_price' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function getLimit(string $key): int
    {
        return $this->limits[$key] ?? 0;
    }

    public function hasFeature(string $flag): bool
    {
        return $this->feature_flags[$flag] ?? false;
    }

    public static function bySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }
}