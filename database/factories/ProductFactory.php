<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
{
    return [
        'category_id' => \App\Models\Category::inRandomOrder()->first()->id ?? \App\Models\Category::factory(),
        'name' => 'Modni artikal ' . $this->faker->numberBetween(1, 100),
        'description' => 'Ovo je kvalitetan komad odeće iz naše najnovije kolekcije.',
        'image' => $this->faker->numberBetween(1000000000, 9999999999) . '_2_6_0.jpg',
        'price' => $this->faker->randomElement([20, 25, 30, 35, 40]),
        'stock' => $this->faker->numberBetween(10, 100),
        'brand' => $this->faker->randomElement(['Zara', 'Mango', 'H&M', 'Nike', 'Puma']),
    ];
}
}
