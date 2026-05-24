<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('type'); // percent, fixed, free_shipping
            $table->unsignedInteger('value_cents')->default(0);
            $table->unsignedTinyInteger('value_percent')->default(0);
            $table->unsignedInteger('min_order_cents')->default(0);
            $table->unsignedInteger('max_discount_cents')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('max_uses_per_user')->default(1);
            $table->unsignedInteger('times_used')->default(0);
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('coupon_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->timestamp('used_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_user');
        Schema::dropIfExists('coupons');
    }
};
