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
        Schema::create('driver_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users');
            $table->enum('status', ['online', 'busy', 'offline'])->default('offline');
            $table->json('current_location')->nullable(); // lat, lng
            $table->foreignId('current_order_id')->nullable()->constrained('orders');
            $table->timestamp('last_location_update')->nullable();
            $table->timestamps();

            $table->unique('driver_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_statuses');
    }
};
