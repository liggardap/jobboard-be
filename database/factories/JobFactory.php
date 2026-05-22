<?php

namespace Database\Factories;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Job>
 */
class JobFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'title' => fake()->jobTitle(),
            'description' => fake()->paragraphs(3, true),
            'category' => fake()->randomElement(['Engineering', 'Marketing', 'Design', 'Finance', 'Operations']),
            'employment_type' => fake()->randomElement(EmploymentType::cases()),
            'location_city' => fake()->city(),
            'location_country' => 'ID',
            'is_remote' => fake()->boolean(30),
            'salary_min' => fake()->numberBetween(3_000_000, 10_000_000),
            'salary_max' => fake()->numberBetween(10_000_000, 30_000_000),
            'currency' => 'IDR',
            'status' => JobStatus::Draft,
            'published_at' => null,
            'expires_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JobStatus::Active,
            'published_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JobStatus::Closed,
            'published_at' => now()->subDays(60),
            'expires_at' => now()->subDays(30),
        ]);
    }
}
