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
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->json('name_i18n');
            $table->json('description_i18n')->nullable();
            $table->string('slug')->unique();
            $table->json('images')->nullable();
            $table->string('status')->default('draft'); // draft|active|archived
            $table->boolean('is_featured')->default(false);
            $table->json('attributes')->nullable(); // {color: [...], size: [...]}
            $table->timestamps();
            $table->softDeletes();
            $table->index('shop_id');
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
