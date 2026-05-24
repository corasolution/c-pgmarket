<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_queries', function (Blueprint $table): void {
            $table->id();
            $table->string('query_text');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category_slug')->nullable();
            $table->unsignedInteger('results_count')->default(0);
            $table->timestamps();
            $table->index('query_text');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_queries');
    }
};
