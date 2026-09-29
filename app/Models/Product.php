<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'description',
        'price',
        'stock_quantity',
        'reorder_level',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'reorder_level' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class)->withTimestamps();
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['category_id'] ?? null, fn (Builder $query, int $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['min_price'] ?? null, fn (Builder $query, string $price) => $query->where('price', '>=', $price))
            ->when($filters['max_price'] ?? null, fn (Builder $query, string $price) => $query->where('price', '<=', $price))
            ->when($filters['stock_status'] ?? null, function (Builder $query, string $status) {
                return match ($status) {
                    'in_stock' => $query->where('stock_quantity', '>', 0),
                    'out_of_stock' => $query->where('stock_quantity', 0),
                    'low_stock' => $query->where('stock_quantity', '>', 0)
                        ->whereColumn('stock_quantity', '<=', 'reorder_level'),
                };
            });
    }

    protected function isLowStock(): Attribute
    {
        return Attribute::get(fn (): bool => $this->stock_quantity > 0 && $this->stock_quantity <= $this->reorder_level);
    }

    protected function sku(): Attribute
    {
        return Attribute::set(fn (string $value): string => strtoupper(trim($value)));
    }
}
