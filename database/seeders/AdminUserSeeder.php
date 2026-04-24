<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::updateOrCreate(
            ['email' => 'admin@adoremom.com'],
            [
                'name' => 'Super Admin',
                'password' => 'admin123',
                'role' => 'superadmin',
                'status' => 'active',
                'phone' => '1234567890',
            ]
        );
    }
}
