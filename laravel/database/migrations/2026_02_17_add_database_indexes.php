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
        Schema::table('sales', function (Blueprint $table) {
            $table->index('customer_id');
            $table->index('inventory_item_id');
            $table->index('created_at');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->index('vendor_id');
            $table->index('inventory_item_id');
            $table->index('created_at');
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->index('status');
            $table->index('created_at');
            $table->index('updated_at');
            // For JSON tag search (if using PostgreSQL or MySQL 5.7+)
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('CREATE INDEX inventory_items_tags_idx ON inventory_items USING GIN (tags);');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('updated_at');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->index('is_preferred');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['customer_id']);
            $table->dropIndex(['inventory_item_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['vendor_id']);
            $table->dropIndex(['inventory_item_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['updated_at']);
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('DROP INDEX inventory_items_tags_idx;');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['updated_at']);
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex(['is_preferred']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['updated_at']);
        });
    }
};
