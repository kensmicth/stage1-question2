<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function index(ProductIndexRequest $request)
    {
        $filters = $request->validated();
        $query = $request->query();
        ksort($query);
        $version = Cache::get('products.cache_version', 0);
        $cacheKey = 'products.index.'.$version.'.'.hash('sha256', json_encode($query));
        $perPage = $filters['per_page'] ?? 15;

        $products = Cache::remember($cacheKey, now()->addMinute(), fn () => Product::query()
            ->with(['category', 'suppliers'])
            ->filter($filters)
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString());

        return ProductResource::collection($products);
    }

    public function store(ProductStoreRequest $request)
    {
        $validated = $request->validated();
        $supplierIds = $validated['supplier_ids'] ?? [];
        $product = Product::create(Arr::except($validated, 'supplier_ids'));
        $product->suppliers()->sync($supplierIds);
        $this->invalidateProductCache();

        return (new ProductResource($product->load(['category', 'suppliers'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load(['category', 'suppliers']));
    }

    public function update(ProductUpdateRequest $request, Product $product): ProductResource
    {
        $validated = $request->validated();
        $supplierIds = $validated['supplier_ids'] ?? null;
        $product->update(Arr::except($validated, 'supplier_ids'));

        if ($supplierIds !== null) {
            $product->suppliers()->sync($supplierIds);
        }

        $this->invalidateProductCache();

        return new ProductResource($product->load(['category', 'suppliers']));
    }

    public function destroy(Product $product)
    {
        $product->delete();
        $this->invalidateProductCache();

        return response()->noContent();
    }

    private function invalidateProductCache(): void
    {
        Cache::forever('products.cache_version', Cache::get('products.cache_version', 0) + 1);
    }
}
