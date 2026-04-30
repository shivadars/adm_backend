<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the latest values from your .env file
        $email = env('ADMIN_EMAIL', 'admin@adoremom.com');
        $password = env('ADMIN_PASSWORD', 'password123');

        // Logic: Search for a user with the 'superadmin' ROLE.
        // If found, update their email and password.
        // If not found, create a new superadmin.
        User::updateOrCreate(
            ['role' => 'superadmin'], // Search criteria
            [
                'name' => 'Super Admin',
                'email' => $email,
                'password' => Hash::make($password),
            ]
        );

        $this->command->info("Superadmin credentials synchronized from .env for: {$email}");
    }
}
