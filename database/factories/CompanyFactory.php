<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->company(),
            'name' => fake()->company(),
            'description' => fake()->paragraph(),
            'industry' => fake()->randomElement(['Technology', 'Finance', 'Healthcare', 'Education', 'Retail']),
            'city' => fake()->city(),
            'country' => 'ID',
            'website' => fake()->url(),
            'is_verified' => false,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }
}
