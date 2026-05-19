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
        Schema::create('aba_beneficiaries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('payee'); // ABA account number or Merchant ID
            $table->string('payee_name')->nullable(); // returned by ABA on success
            $table->string('status')->default('pending'); // pending|active|inactive|failed
            $table->json('raw_response')->nullable();
            $table->timestamps();
            $table->unique(['shop_id', 'payee']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aba_beneficiaries');
    }
};
