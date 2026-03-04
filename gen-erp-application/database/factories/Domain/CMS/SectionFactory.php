<?php

namespace Database\Factories\Domain\CMS;

use App\Domain\CMS\Models\Section;
use App\Domain\CMS\Models\Page;
use App\Domain\CMS\Enums\SectionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\CMS\Models\Section>
 */
class SectionFactory extends Factory
{
    protected $model = Section::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(SectionType::cases());
        
        return [
            'page_id' => Page::factory(),
            'type' => $type->value,
            'sort_order' => $this->faker->numberBetween(0, 10),
            'content' => $type->getDefaultContent(),
            'is_visible' => true,
        ];
    }

    public function heroBanner(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SectionType::HERO_BANNER->value,
            'content' => [
                'title' => $this->faker->catchPhrase(),
                'subtitle' => $this->faker->sentence(),
                'button_text' => 'Learn More',
                'button_url' => '/about',
                'background_image' => null,
                'background_color' => $this->faker->hexColor(),
                'text_color' => '#FFFFFF',
                'alignment' => 'center',
            ],
        ]);
    }

    public function textBlock(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SectionType::TEXT_BLOCK->value,
            'content' => [
                'title' => $this->faker->sentence(4),
                'text' => $this->faker->paragraphs(3, true),
                'alignment' => 'left',
                'background_color' => '#FFFFFF',
                'text_color' => '#000000',
            ],
        ]);
    }

    public function imageText(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SectionType::IMAGE_TEXT->value,
            'content' => [
                'title' => $this->faker->sentence(4),
                'text' => $this->faker->paragraphs(2, true),
                'image' => null,
                'image_position' => $this->faker->randomElement(['left', 'right']),
                'button_text' => null,
                'button_url' => null,
            ],
        ]);
    }

    public function productGrid(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SectionType::PRODUCT_GRID->value,
            'content' => [
                'title' => 'Our Products',
                'subtitle' => 'Discover our amazing product range',
                'limit' => 8,
                'columns' => 4,
                'show_price' => true,
                'show_description' => true,
                'category_filter' => null,
            ],
        ]);
    }

    public function contactForm(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SectionType::CONTACT_FORM->value,
            'content' => [
                'title' => 'Get in Touch',
                'subtitle' => 'We\'d love to hear from you',
                'fields' => [
                    ['name' => 'name', 'label' => 'Full Name', 'type' => 'text', 'required' => true],
                    ['name' => 'email', 'label' => 'Email Address', 'type' => 'email', 'required' => true],
                    ['name' => 'subject', 'label' => 'Subject', 'type' => 'text', 'required' => true],
                    ['name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'required' => true],
                ],
                'submit_text' => 'Send Message',
                'success_message' => 'Thank you for your message. We\'ll get back to you soon!',
            ],
        ]);
    }

    public function testimonials(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SectionType::TESTIMONIALS->value,
            'content' => [
                'title' => 'What Our Clients Say',
                'subtitle' => 'Don\'t just take our word for it',
                'testimonials' => [
                    [
                        'name' => $this->faker->name(),
                        'position' => $this->faker->jobTitle(),
                        'company' => $this->faker->company(),
                        'text' => $this->faker->paragraph(),
                        'image' => null,
                        'rating' => 5,
                    ],
                    [
                        'name' => $this->faker->name(),
                        'position' => $this->faker->jobTitle(),
                        'company' => $this->faker->company(),
                        'text' => $this->faker->paragraph(),
                        'image' => null,
                        'rating' => 5,
                    ],
                ],
                'layout' => 'grid',
                'show_rating' => true,
            ],
        ]);
    }

    public function faq(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SectionType::FAQ->value,
            'content' => [
                'title' => 'Frequently Asked Questions',
                'subtitle' => 'Find answers to common questions',
                'faqs' => [
                    [
                        'question' => 'What services do you offer?',
                        'answer' => $this->faker->paragraph(),
                    ],
                    [
                        'question' => 'How can I contact you?',
                        'answer' => $this->faker->paragraph(),
                    ],
                    [
                        'question' => 'What are your business hours?',
                        'answer' => $this->faker->paragraph(),
                    ],
                ],
                'layout' => 'accordion',
            ],
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible' => false,
        ]);
    }

    public function visible(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible' => true,
        ]);
    }
}