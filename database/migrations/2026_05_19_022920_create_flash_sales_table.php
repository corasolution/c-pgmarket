<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flash_sales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('sale_price_cents');
            $table->string('sale_price_currency', 3)->default('USD');
            $table->unsignedInteger('quantity_limit')->nullable(); // max units at sale price
            $table->unsignedInteger('quantity_sold')->default(0);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status')->default('scheduled'); // scheduled, active, completed, cancelled
            $table->timestamps();
            $table->index(['status', 'starts_at', 'ends_at']);
            $table->index('shop_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flash_sales');
    }
};
