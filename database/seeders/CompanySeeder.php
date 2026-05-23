<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $acmeUser = User::where('email', 'acme@jobboard.test')->first();
        $brightUser = User::where('email', 'bright@jobboard.test')->first();

        $acme = Company::create([
            'user_id' => $acmeUser->id,
            'name' => 'Acme Corp',
            'description' => 'A global leader in technology solutions for enterprise clients.',
            'industry' => 'Technology',
            'city' => 'Jakarta',
            'country' => 'ID',
            'website' => 'https://acme.example.com',
            'is_verified' => true,
        ]);

        $bright = Company::create([
            'user_id' => $brightUser->id,
            'name' => 'Bright Tech',
            'description' => 'A fast-growing startup building developer tools and cloud infrastructure.',
            'industry' => 'Technology',
            'city' => 'Bandung',
            'country' => 'ID',
            'website' => 'https://brighttech.example.com',
            'is_verified' => true,
        ]);

        Job::factory()->active()->count(5)->create(['company_id' => $acme->id]);
        Job::factory()->active()->count(4)->create(['company_id' => $bright->id]);
        Job::factory()->count(2)->create(['company_id' => $acme->id]);

        Company::factory()->verified()->count(20)->create()->each(function (Company $company) {
            $count = rand(5000, 10000);
            Job::factory()->active()->count($count)->create(['company_id' => $company->id]);
        });
    }
}
