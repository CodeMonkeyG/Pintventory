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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sku')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('in_stock'); // in_stock, low_stock, out_of_stock, archived
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('reorder_point')->default(0);
            $table->string('unit')->default('each');
            $table->json('tags')->nullable();
            $table->string('location')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
