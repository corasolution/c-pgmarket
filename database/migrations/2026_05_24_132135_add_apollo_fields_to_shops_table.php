<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table): void {
            $table->unsignedInteger('apollo_province_id')->nullable()->after('telegram_chat_id');
            $table->unsignedInteger('apollo_district_id')->nullable()->after('apollo_province_id');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table): void {
            $table->dropColumn(['apollo_province_id', 'apollo_district_id']);
        });
    }
};
