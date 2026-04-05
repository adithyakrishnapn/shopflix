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
        Schema::create('razorpay_orders', function (Blueprint $table) {
            $table->id();
            $table->string('razorpay_order_id')->unique()->index();
            $table->longText('cart_data'); // Stores serialized cart/order data
            $table->string('user_id')->nullable()->index();
            $table->timestamps();

            // Auto-cleanup: delete after 24 hours (webhook should fire within this time)
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('razorpay_orders');
    }
};
