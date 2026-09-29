<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_routes_require_authentication(): void
    {
        $this->getJson('/api/products')->assertUnauthorized();
        $this->get('/api/products')->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_a_product_with_suppliers(): void
    {
        $token = $this->token();
        $category = Category::factory()->create();
        $supplier = Supplier::factory()->create();

        $this->withToken($token)
            ->postJson('/api/products', [
                'category_id' => $category->id,
                'sku' => ' desk-001 ',
                'name' => 'Standing Desk',
                'price' => 349.99,
                'stock_quantity' => 3,
                'reorder_level' => 5,
                'supplier_ids' => [$supplier->id],
            ])
            ->assertCreated()
            ->assertJsonPath('data.sku', 'DESK-001')
            ->assertJsonPath('data.is_low_stock', true)
            ->assertJsonPath('data.suppliers.0.id', $supplier->id);
    }

    public function test_products_can_be_filtered_and_paginated(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 100,
            'stock_quantity' => 2,
            'reorder_level' => 5,
        ]);
        Product::factory()->create([
            'category_id' => $category->id,
            'price' => 300,
            'stock_quantity' => 20,
            'reorder_level' => 5,
        ]);
        Product::factory()->create(['price' => 100, 'stock_quantity' => 1, 'reorder_level' => 5]);

        $this->withToken($this->token())
            ->getJson('/api/products?category_id='.$category->id.'&min_price=50&max_price=150&stock_status=low_stock&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.price', '100.00')
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_product_writes_invalidate_cached_lists(): void
    {
        $token = $this->token();
        $this->withToken($token)->getJson('/api/products')->assertJsonPath('meta.total', 0);

        $this->withToken($token)->postJson('/api/products', [
            'category_id' => Category::factory()->create()->id,
            'sku' => 'CACHE-001',
            'name' => 'Cached Product',
            'price' => 10,
            'stock_quantity' => 4,
        ])->assertCreated();

        $this->withToken($token)->getJson('/api/products')->assertJsonPath('meta.total', 1);
    }

    public function test_sku_uniqueness_uses_normalized_input(): void
    {
        $product = Product::factory()->create(['sku' => 'DESK-001']);

        $this->withToken($this->token())->postJson('/api/products', [
            'category_id' => $product->category_id,
            'sku' => ' desk-001 ',
            'name' => 'Duplicate Desk',
            'price' => 20,
            'stock_quantity' => 2,
        ])->assertUnprocessable()->assertJsonValidationErrors(['sku']);
    }

    public function test_authenticated_user_can_show_and_update_a_product(): void
    {
        $product = Product::factory()->create(['name' => 'Old name']);

        $this->withToken($this->token())
            ->getJson('/api/products/'.$product->id)
            ->assertOk()
            ->assertJsonPath('data.name', 'Old name');

        $this->withToken($this->token())
            ->patchJson('/api/products/'.$product->id, ['name' => 'New name', 'stock_quantity' => 0])
            ->assertOk()
            ->assertJsonPath('data.name', 'New name')
            ->assertJsonPath('data.is_low_stock', false);
    }

    public function test_product_delete_is_soft_and_removes_it_from_api(): void
    {
        $product = Product::factory()->create();

        $this->withToken($this->token())
            ->deleteJson('/api/products/'.$product->id)
            ->assertNoContent();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->withToken($this->token())->getJson('/api/products/'.$product->id)->assertNotFound();
    }

    public function test_invalid_product_input_returns_validation_errors(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/products', ['name' => 'Incomplete'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'sku', 'price', 'stock_quantity']);
    }

    public function test_registration_returns_a_usable_sanctum_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
        ])->assertCreated()->assertJsonPath('token_type', 'Bearer');

        $this->withToken($response->json('token'))
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'new@example.com');
    }

    public function test_authentication_endpoints_are_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 11; $attempt++) {
            $response = $this->postJson('/api/auth/login', [
                'email' => 'limited@example.com',
                'password' => 'incorrect-password',
            ]);
        }

        $response->assertTooManyRequests();
    }

    private function token(): string
    {
        return User::factory()->create()->createToken('feature-test')->plainTextToken;
    }
}
