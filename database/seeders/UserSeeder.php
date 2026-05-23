<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@jobboard.test',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Acme Corp',
            'email' => 'acme@jobboard.test',
            'password' => Hash::make('password'),
            'role' => UserRole::Company,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Bright Tech',
            'email' => 'bright@jobboard.test',
            'password' => Hash::make('password'),
            'role' => UserRole::Company,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Alice Candidate',
            'email' => 'alice@jobboard.test',
            'password' => Hash::make('password'),
            'role' => UserRole::Candidate,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Bob Candidate',
            'email' => 'bob@jobboard.test',
            'password' => Hash::make('password'),
            'role' => UserRole::Candidate,
            'email_verified_at' => now(),
        ]);
    }
}
