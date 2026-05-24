<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('coupon_id')->nullable()->after('note')->constrained()->nullOnDelete();
            $table->string('coupon_code')->nullable()->after('coupon_id');
            $table->unsignedInteger('discount_cents')->default(0)->after('coupon_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn(['coupon_code', 'discount_cents']);
        });
    }
};
