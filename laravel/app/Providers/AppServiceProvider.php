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
        // Model logic handled via traits and global scopes
    }
}
