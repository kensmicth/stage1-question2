<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'inventory@example.com'],
            [
                'name' => 'Inventory Admin',
                'password' => Hash::make('password'),
            ],
        );

        $categories = Category::query()->get();
        $suppliers = Supplier::query()->get();

        if ($categories->isEmpty()) {
            $categories = Category::factory(4)->create();
        }

        if ($suppliers->isEmpty()) {
            $suppliers = Supplier::factory(4)->create();
        }

        if (Product::query()->exists()) {
            return;
        }

        Product::factory(20)->make()->each(function (Product $product) use ($categories, $suppliers) {
            $product->category()->associate($categories->random());
            $product->save();
            $product->suppliers()->sync($suppliers->random(rand(1, 2))->modelKeys());
        });
    }
}
