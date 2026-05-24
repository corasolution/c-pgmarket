<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('back_in_stock_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'product_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('back_in_stock_subscriptions');
    }
};
