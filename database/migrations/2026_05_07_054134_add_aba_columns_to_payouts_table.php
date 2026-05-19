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
        Schema::table('payouts', function (Blueprint $table): void {
            $table->string('aba_transaction_id')->nullable()->after('rejection_reason');
            $table->string('aba_external_ref')->nullable()->after('aba_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table): void {
            $table->dropColumn(['aba_transaction_id', 'aba_external_ref']);
        });
    }
};
