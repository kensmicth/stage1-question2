<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('sku', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedInteger('reorder_level')->default(5);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['category_id', 'price']);
            $table->index(['stock_quantity', 'reorder_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
