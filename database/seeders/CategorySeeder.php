<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Male', 'slug' => 'male', 'description' => 'Clothing for male pets'],
            ['name' => 'Female', 'slug' => 'female', 'description' => 'Clothing for female pets'],
            ['name' => 'Unisex', 'slug' => 'unisex', 'description' => 'Clothing for all pets'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
