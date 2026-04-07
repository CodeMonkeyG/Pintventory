<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Ai\AiManager;
use App\Contracts\AiProvider;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('ai', function ($app) {
            return new AiManager($app);
        });

        $this->app->bind(AiProvider::class, function ($app) {
            return $app->make('ai')->driver();
        });
    }

    public function boot(): void
    {
        $models = [
            \App\Models\InventoryItem::class,
            \App\Models\Vendor::class,
            \App\Models\Customer::class,
            \App\Models\StorageLocation::class,
            \App\Models\Purchase::class,
            \App\Models\Sale::class,
            \App\Models\Photo::class,
        ];

        foreach ($models as $model) {
            $model::creating(function ($item) {
                if (Auth::check()) {
                    if (!$item->workspace_id && Auth::user()->current_workspace_id) {
                        $item->workspace_id = Auth::user()->current_workspace_id;
                    }
                    
                    // Also auto-fill created_by_user_id if the column exists
                    if (SchemaHasColumn($item->getTable(), 'created_by_user_id') && !$item->created_by_user_id) {
                        $item->created_by_user_id = Auth::id();
                    }
                    if (SchemaHasColumn($item->getTable(), 'user_id') && !$item->user_id) {
                        $item->user_id = Auth::id();
                    }
                }
            });
        }
    }
}

function SchemaHasColumn($table, $column) {
    return \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
}
