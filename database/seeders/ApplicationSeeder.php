<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $alice = User::where('email', 'alice@jobboard.test')->first();
        $bob = User::where('email', 'bob@jobboard.test')->first();

        $activeJobs = Job::where('status', 'active')->pluck('id');

        $aliceJobs = $activeJobs->shuffle()->take(3);
        foreach ($aliceJobs as $jobId) {
            Application::factory()->create([
                'user_id' => $alice->id,
                'job_id' => $jobId,
            ]);
        }

        $bobJobs = $activeJobs->diff($aliceJobs)->shuffle()->take(2);
        foreach ($bobJobs as $jobId) {
            Application::factory()->create([
                'user_id' => $bob->id,
                'job_id' => $jobId,
            ]);
        }

        Application::factory()->reviewed()->create([
            'user_id' => $alice->id,
            'job_id' => $activeJobs->diff($aliceJobs->merge($bobJobs))->first(),
        ]);
    }
}
