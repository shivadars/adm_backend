<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call our specific seeders instead of using factories
        $this->call([
            AdminUserSeeder::class,
            CategorySeeder::class,
        ]);
    }
}
