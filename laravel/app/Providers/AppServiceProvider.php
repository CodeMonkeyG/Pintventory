<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Services\Ai\AiManager;
use App\Contracts\AiProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('ai', function ($app) {
            return new AiManager($app);
        });

        $this->app->bind(AiProvider::class, function ($app) {
            return $app->make('ai')->driver();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
