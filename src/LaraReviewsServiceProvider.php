<?php

namespace LaraReviews;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use LaraReviews\Console\Commands\SyncReviewsCommand;
use LaraReviews\Console\Commands\LinkPlatformCommand;
use LaraReviews\View\Components\Summary;
use LaraReviews\View\Components\ListWidget;
use LaraReviews\View\Components\Grid;
use LaraReviews\View\Components\Badge;
use LaraReviews\View\Components\Schema;

class LaraReviewsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/larareviews.php',
            'larareviews'
        );

        // Bind LaraReviewsManager singleton
        $this->app->singleton('larareviews', function ($app) {
            return new LaraReviewsManager($app);
        });
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load Blade views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'larareviews');

        // Register Blade Components
        Blade::component('larareviews-summary', Summary::class);
        Blade::component('larareviews-list', ListWidget::class);
        Blade::component('larareviews-grid', Grid::class);
        Blade::component('larareviews-badge', Badge::class);
        Blade::component('larareviews-schema', Schema::class);

        // Register Artisan Console Commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncReviewsCommand::class,
                LinkPlatformCommand::class,
            ]);

            // Publish configuration
            $this->publishes([
                __DIR__ . '/../config/larareviews.php' => config_path('larareviews.php'),
            ], 'larareviews-config');

            // Publish migrations
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'larareviews-migrations');

            // Publish views
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/larareviews'),
            ], 'larareviews-views');
        }
    }
}
