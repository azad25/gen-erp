<?php

namespace Database\Factories;

use App\Enums\BusinessType;
use App\Enums\Plan;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'uuid' => Str::uuid()->toString(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(6),
            'business_type' => BusinessType::RETAIL->value,
            'country' => 'BD',
            'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka',
            'locale' => 'en',
            'vat_registered' => false,
            'is_active' => true,
            'plan' => Plan::FREE->value,
            'settings' => [
                'simplified_mode' => false,
                'invoice_prefix' => 'INV',
                'po_prefix' => 'PO',
                'date_format' => 'd M Y',
                'time_format' => 'h:i A',
                'fiscal_year_start' => '07-01',
            ],
        ];
    }

    public function pharmacy(): static
    {
        return $this->state(fn (): array => [
            'business_type' => BusinessType::PHARMACY->value,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
