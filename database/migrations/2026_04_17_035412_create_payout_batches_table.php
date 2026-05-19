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
        Schema::create('payout_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('reference')->unique();
            $table->string('status')->default('pending'); // pending|processing|completed|failed
            $table->bigInteger('total_cents')->unsigned()->default(0);
            $table->string('total_currency', 3)->default('USD');
            $table->unsignedInteger('payout_count')->default(0);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_batches');
    }
};
