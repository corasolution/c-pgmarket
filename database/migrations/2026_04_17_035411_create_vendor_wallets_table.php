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
        Schema::create('vendor_wallets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shop_id')->unique()->constrained()->cascadeOnDelete();
            $table->bigInteger('pending_balance_cents')->default(0);
            $table->string('pending_balance_currency', 3)->default('USD');
            $table->bigInteger('available_balance_cents')->default(0);
            $table->string('available_balance_currency', 3)->default('USD');
            $table->bigInteger('lifetime_earned_cents')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_wallets');
    }
};
