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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('transport_cost');
            $table->integer('markup_cost');
            // $table->integer('platform_fee');
            $table->bigInteger('total_price');
            $table->dateTime('transaction_time');
            $table->string('invoice');
            $table->dateTime('expire_time');
            $table->enum('status', ['pending', 'complete', 'failed', 'refunded'])->default('pending');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
