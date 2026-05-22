<?php

namespace Tests\Unit\Models;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_is_cast_to_application_status_enum(): void
    {
        $application = Application::factory()->create(['status' => ApplicationStatus::Pending]);

        $this->assertInstanceOf(ApplicationStatus::class, $application->fresh()->status);
        $this->assertSame(ApplicationStatus::Pending, $application->fresh()->status);
    }

    public function test_belongs_to_job_relationship(): void
    {
        $application = Application::factory()->create();

        $this->assertInstanceOf(BelongsTo::class, $application->job());
    }

    public function test_belongs_to_user_relationship(): void
    {
        $application = Application::factory()->create();

        $this->assertInstanceOf(BelongsTo::class, $application->user());
    }

    public function test_unique_constraint_prevents_duplicate_application(): void
    {
        $application = Application::factory()->create();

        $this->expectException(UniqueConstraintViolationException::class);

        Application::factory()->create([
            'job_id' => $application->job_id,
            'user_id' => $application->user_id,
        ]);
    }
}
