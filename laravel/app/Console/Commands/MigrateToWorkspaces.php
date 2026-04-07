<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MigrateToWorkspaces extends Command
{
    protected $signature = 'app:migrate-to-workspaces';
    protected $description = 'Migrate existing user-based data to workspaces';

    public function handle()
    {
        $users = User::all();

        foreach ($users as $user) {
            $this->info("Migrating data for user: {$user->email}");

            // 1. Create a personal workspace for the user
            $workspace = Workspace::create([
                'name' => 'Personal Workspace',
                'created_by_user_id' => $user->id,
            ]);

            // 2. Attach user to workspace as owner
            $workspace->users()->attach($user->id, ['role' => 'owner']);

            // 3. Set current workspace for user
            $user->update(['current_workspace_id' => $workspace->id]);

            // 4. Update all related models
            $tables = [
                'inventory_items' => 'created_by_user_id',
                'vendors' => 'created_by_user_id',
                'customers' => 'created_by_user_id',
                'storage_locations' => 'user_id',
                'purchases' => 'created_by_user_id',
                'sales' => 'created_by_user_id',
            ];

            foreach ($tables as $table => $userColumn) {
                $count = DB::table($table)
                    ->where($userColumn, $user->id)
                    ->update(['workspace_id' => $workspace->id]);
                
                $this->line("  - Updated {$count} records in {$table}");
            }

            // Special handling for photos (linked via inventory_items)
            $photoCount = DB::table('photos')
                ->join('inventory_items', 'photos.inventory_item_id', '=', 'inventory_items.id')
                ->where('inventory_items.workspace_id', $workspace->id)
                ->update(['photos.workspace_id' => $workspace->id]);
            
            $this->line("  - Updated {$photoCount} records in photos");
        }

        $this->info('Migration completed successfully!');
    }
}
