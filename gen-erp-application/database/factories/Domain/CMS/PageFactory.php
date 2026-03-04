<?php

namespace Database\Factories\Domain\CMS;

use App\Domain\CMS\Models\Page;
use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Enums\PageStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\CMS\Models\Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(3);
        
        return [
            'site_id' => Site::factory(),
            'title' => $title,
            'slug' => str($title)->slug(),
            'seo_title' => $title . ' - ' . $this->faker->words(2, true),
            'seo_description' => $this->faker->sentence(15),
            'seo_image' => null,
            'status' => $this->faker->randomElement(PageStatus::cases()),
            'is_homepage' => false,
            'sort_order' => $this->faker->numberBetween(0, 10),
            'published_at' => null,
            'scheduled_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PageStatus::PUBLISHED,
            'published_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PageStatus::DRAFT,
            'published_at' => null,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PageStatus::SCHEDULED,
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 month'),
        ]);
    }

    public function homepage(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_homepage' => true,
            'slug' => 'home',
            'title' => 'Home',
            'sort_order' => 0,
        ]);
    }

    public function aboutUs(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'About Us',
            'slug' => 'about-us',
            'seo_title' => 'About Us - Learn More About Our Company',
            'seo_description' => 'Discover our company story, mission, and values. Learn about our team and what drives us to deliver excellence.',
        ]);
    }

    public function contact(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Contact Us',
            'slug' => 'contact',
            'seo_title' => 'Contact Us - Get in Touch',
            'seo_description' => 'Get in touch with us. Find our contact information, office locations, and send us a message.',
        ]);
    }
}