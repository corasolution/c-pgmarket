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
        Schema::create('payment_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_event_id')->unique(); // idempotency key
            $table->string('provider')->default('aba_payway');
            $table->string('event_type');
            $table->json('raw_payload'); // full ABA PayWay callback stored for audit
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index('payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
