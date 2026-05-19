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
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('aba_payway');
            $table->string('transaction_id')->unique()->nullable();
            $table->string('status')->default('pending'); // pending|paid|failed|refunded
            $table->bigInteger('amount_cents')->unsigned();
            $table->string('amount_currency', 3)->default('USD');
            $table->string('hash')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
