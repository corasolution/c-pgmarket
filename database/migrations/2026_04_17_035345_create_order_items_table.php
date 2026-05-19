<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sub_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->string('product_name_snapshot');
            $table->string('variant_sku_snapshot');
            $table->string('image_snapshot')->nullable();
            $table->json('options_snapshot')->nullable();
            $table->unsignedInteger('quantity');
            $table->bigInteger('unit_price_cents')->unsigned();
            $table->string('unit_price_currency', 3)->default('USD');
            $table->timestamps();
            $table->index('sub_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
