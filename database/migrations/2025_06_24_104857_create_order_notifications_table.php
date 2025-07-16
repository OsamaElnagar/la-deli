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
        Schema::create('order_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->enum('recipient_type', ['branch', 'driver', 'customer']);
            $table->unsignedBigInteger('recipient_id');
            $table->string('title');
            $table->text('message');
            $table->enum('type', [
                'order_created',
                'order_prepared',
                'driver_assigned',
                'order_picked_up',
                'order_delivered',
                'order_confirmed'
            ]);
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_type', 'recipient_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_notifications');
    }
};
