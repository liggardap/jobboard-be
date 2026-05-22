<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'job_id' => Job::factory()->active(),
            'user_id' => User::factory(),
            'cover_letter' => fake()->paragraphs(2, true),
            'status' => ApplicationStatus::Pending,
        ];
    }

    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ApplicationStatus::Reviewed,
        ]);
    }

    public function shortlisted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ApplicationStatus::Shortlisted,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ApplicationStatus::Rejected,
        ]);
    }
}
