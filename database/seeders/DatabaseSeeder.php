<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test Korisnik',
            'email' => 'test@example.com',
        ]);

        $reactCategories = [
            'Pants', 'Dresses', 'Sweaters', 'Shirts', 
            'Jeans', 'Jackets', 'T-shirts', 'Shorts', 
            'Skirts', 'Tops'
        ];

        foreach ($reactCategories as $catName) {
            $category = Category::create([
                'name' => $catName,
                'description' => "Kolekcija artikala iz kategorije {$catName}.",
            ]);

            Product::factory()->count(5)->create([
                'category_id' => $category->id
            ]);
        }
    }
}
