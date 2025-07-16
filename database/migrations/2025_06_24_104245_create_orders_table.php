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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();
            $table->string('invoice_number')->unique();

            // Source and Destination
            $table->foreignId('source_branch_id')->constrained('branches');
            $table->foreignId('destination_branch_id')->nullable()->constrained('branches');

            // Customer delivery info (for home deliveries)
            $table->string('customer_name')->nullable();
            $table->text('customer_address')->nullable();
            $table->string('customer_phone')->nullable();
            $table->json('customer_coordinates')->nullable();

            // Order details
            $table->enum('delivery_type', ['branch_to_branch', 'branch_to_customer', 'warehouse_to_branch']);
            $table->text('notes')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);

            // Status tracking
            $table->enum('status', [
                'pending',           // Just created
                'assigned_pharmacist', // Assigned to pharmacist
                'preparing',         // Pharmacist is preparing
                'ready_for_pickup',  // Ready for delivery agent
                'assigned_driver',   // Assigned to driver
                'picked_up',         // Driver picked up
                'in_transit',        // On the way
                'delivered',         // Successfully delivered
                'cancelled',         // Cancelled
                'returned'           // Returned to source
            ])->default('pending');

            // Staff assignments
            $table->foreignId('pharmacist_id')->nullable()->constrained('users');
            $table->foreignId('driver_id')->nullable()->constrained('users');
            $table->foreignId('created_by')->constrained('users'); // Feeder who created

            // Timestamps for tracking
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['status', 'source_branch_id']);
            $table->index(['driver_id', 'status']);
            $table->index(['pharmacist_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
