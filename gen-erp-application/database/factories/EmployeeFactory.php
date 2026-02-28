<?php

namespace Database\Factories;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Employee> */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '01'.$this->faker->randomElement(['3', '4', '5', '6', '7', '8', '9']).$this->faker->numerify('########'),
            'joining_date' => $this->faker->date(),
            'employment_type' => EmploymentType::PERMANENT,
            'status' => EmployeeStatus::ACTIVE,
            'basic_salary' => $this->faker->numberBetween(1500000, 5000000),
            'gross_salary' => $this->faker->numberBetween(2000000, 7000000),
        ];
    }
}
