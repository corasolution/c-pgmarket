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
        Schema::create('shipments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sub_order_id')->constrained()->cascadeOnDelete();
            $table->string('provider'); // grab|jnt|kerry|vet|cambodia_post|stub
            $table->string('tracking_number')->nullable();
            $table->string('status')->default('pending');
            $table->bigInteger('shipping_fee_cents')->unsigned()->default(0);
            $table->string('shipping_fee_currency', 3)->default('USD');
            $table->json('provider_response')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->index('sub_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
