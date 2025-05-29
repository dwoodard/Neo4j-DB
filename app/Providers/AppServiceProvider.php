<?php

namespace App\Providers;

use App\Console\Commands\CreateCompanies;
use App\Console\Commands\GraphModelDemo;
use App\Console\Commands\TestGraphModel;
use App\Services\Neo4jService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Neo4jService::class, function ($app) {
            return new Neo4jService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                TestGraphModel::class,
                GraphModelDemo::class,
                CreateCompanies::class,
            ]);
        }
    }
}
