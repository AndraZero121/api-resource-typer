<?php

namespace AndraZero121\ApiResourceTyper\Providers;

use Illuminate\Support\ServiceProvider;
use AndraZero121\ApiResourceTyper\Commands\GenerateTypesCommand;
use AndraZero121\ApiResourceTyper\Middleware\ApiResourceTyperMiddleware;

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
        // Load user extension if exists
        define('API_RESOURCE_TYPER_EXTENSION', app_path('ApiResourceTyperExtension.php'));
        if (file_exists(API_RESOURCE_TYPER_EXTENSION)) {
            require_once API_RESOURCE_TYPER_EXTENSION;
        }

        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/api-resource-typer.php' => config_path('api-resource-typer.php'),
        ], 'api-resource-typer-config');

        // Publish extension file
        $this->publishes([
            __DIR__ . '/../../extensions/ApiResourceTyperExtension.php' => app_path('ApiResourceTyperExtension.php'),
        ], 'api-resource-typer-extension');

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