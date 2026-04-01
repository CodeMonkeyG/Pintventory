<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Services\Ai\AiManager;
use App\Contracts\AiProvider;

/**
 * Central service provider for the application.
 * 
 * Handles the registration of core services like the AI manager and providers.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     * 
     * Configures the AI singleton and binds the AiProvider contract to the default driver.
     *
     * @return void
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
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}
