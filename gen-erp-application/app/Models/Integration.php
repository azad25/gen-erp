<?php

namespace App\Models;

use App\Enums\IntegrationCategory;
use App\Enums\IntegrationTier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Integration registry — master catalogue of all available integrations. */
class Integration extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'category',
        'description',
        'logo_path',
        'tier',
        'min_plan',
        'config_schema',
        'capabilities',
        'is_active',
        'is_official',
        'version',
        'author',
        'author_url',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'category' => IntegrationCategory::class,
            'tier' => IntegrationTier::class,
            'config_schema' => 'array',
            'capabilities' => 'array',
            'is_active' => 'boolean',
            'is_official' => 'boolean',
        ];
    }

    public function companyIntegrations(): HasMany
    {
        return $this->hasMany(CompanyIntegration::class);
    }

    /** Check if a plan string meets the minimum plan requirement. */
    public function isPlanEligible(string $companyPlan): bool
    {
        $planHierarchy = ['free' => 0, 'pro' => 1, 'enterprise' => 2];

        return ($planHierarchy[$companyPlan] ?? 0) >= ($planHierarchy[$this->min_plan] ?? 0);
    }

    /** Whether this integration supports a given capability. */
    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities ?? [], true);
    }
}
