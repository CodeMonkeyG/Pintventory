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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('inventory_item_id')->constrained('inventory_items');
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->dateTime('purchased_at')->useCurrent();
            $table->integer('quantity_purchased');
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('tax_cost', 10, 2)->default(0);
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
