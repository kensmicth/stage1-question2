<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'sku' => Str::upper(fake()->unique()->bothify('SKU-########')),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->paragraph(),
            'price' => fake()->randomFloat(2, 1, 5000),
            'stock_quantity' => fake()->numberBetween(0, 250),
            'reorder_level' => 5,
        ];
    }
}
