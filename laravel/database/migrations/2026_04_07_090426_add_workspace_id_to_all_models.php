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
        $tables = [
            'inventory_items',
            'vendors',
            'customers',
            'purchases',
            'sales',
            'photos',
            'storage_locations'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->uuid('workspace_id')->nullable()->after('id');
                $table->index('workspace_id');
                
                // We'll add the foreign key after data migration or in a separate step 
                // to avoid issues with existing null records.
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'inventory_items',
            'vendors',
            'customers',
            'purchases',
            'sales',
            'photos',
            'storage_locations'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('workspace_id');
            });
        }
    }
};
