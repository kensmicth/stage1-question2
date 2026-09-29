<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'stock_quantity' => $this->stock_quantity,
            'reorder_level' => $this->reorder_level,
            'is_low_stock' => $this->is_low_stock,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'suppliers' => SupplierResource::collection($this->whenLoaded('suppliers')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
