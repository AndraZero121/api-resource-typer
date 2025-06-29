<?php

namespace AndraZero121\ApiResourceTyper\Providers;

use Illuminate\Support\ServiceProvider;
use Andrazero121\ApiResourceTyper\Commands\GenerateTypesCommand;
use Andrazero121\ApiResourceTyper\Middleware\ApiResourceTyperMiddleware;

class ApiResourceTyperServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/api-resource-typer.php', 'api-resource-typer');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/api-resource-typer.php' => config_path('api-resource-typer.php'),
        ], 'config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateTypesCommand::class,
            ]);
        }

        // Register middleware
        $this->app['router']->aliasMiddleware('api-typer', ApiResourceTyperMiddleware::class);
    }
}