<?php

namespace Database\Factories\Domain\CMS;

use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Enums\SiteStatus;
use App\Domain\Auth\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\CMS\Models\Site>
 */
class SiteFactory extends Factory
{
    protected $model = Site::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company();
        
        return [
            'company_id' => Company::factory(),
            'name' => $name,
            'slug' => str($name)->slug() . '-' . $this->faker->unique()->numberBetween(1000, 9999),
            'domain' => null,
            'subdomain' => str($name)->slug() . '-' . $this->faker->unique()->numberBetween(1000, 9999),
            'logo' => null,
            'favicon' => null,
            'primary_color' => $this->faker->hexColor(),
            'accent_color' => $this->faker->hexColor(),
            'font_family' => $this->faker->randomElement(['Inter', 'Roboto', 'Open Sans', 'Lato']),
            'status' => $this->faker->randomElement(SiteStatus::cases()),
            'seo_title' => $name . ' - ' . $this->faker->catchPhrase(),
            'seo_description' => $this->faker->sentence(20),
            'seo_image' => null,
            'google_analytics_id' => null,
            'facebook_pixel_id' => null,
            'settings' => null,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SiteStatus::PUBLISHED,
            'published_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SiteStatus::DRAFT,
            'published_at' => null,
        ]);
    }

    public function withDomain(): static
    {
        return $this->state(fn (array $attributes) => [
            'domain' => $this->faker->unique()->domainName(),
        ]);
    }

    public function withAnalytics(): static
    {
        return $this->state(fn (array $attributes) => [
            'google_analytics_id' => 'G-' . strtoupper($this->faker->bothify('?????????')),
            'facebook_pixel_id' => $this->faker->numerify('###############'),
        ]);
    }
}