<?php

namespace Tests\Feature\Job;

use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_job_returns_job_with_nested_company(): void
    {
        $job = Job::factory()->active()->create();

        $response = $this->getJson("/api/v1/jobs/{$job->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id', 'title', 'description', 'category', 'employment_type',
                    'location_city', 'location_country', 'is_remote',
                    'salary_min', 'salary_max', 'currency', 'status',
                    'published_at', 'expires_at', 'created_at',
                    'company' => ['id', 'name', 'industry'],
                ],
            ])
            ->assertJsonPath('data.id', $job->id);
    }

    public function test_get_job_returns_404_for_missing_job(): void
    {
        $this->getJson('/api/v1/jobs/999')
            ->assertStatus(404)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'not_found');
    }

    public function test_get_job_does_not_require_authentication(): void
    {
        $job = Job::factory()->create();

        $this->getJson("/api/v1/jobs/{$job->id}")
            ->assertStatus(200);
    }
}
