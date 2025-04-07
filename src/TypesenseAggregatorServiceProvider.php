<?php

namespace NeedBrainz\TypesenseAggregator;

use NeedBrainz\TypesenseAggregator\Commands\MakeTypesenseAggregtorCommand;
use NeedBrainz\TypesenseAggregator\Engines\TypesenseEngine;
use NeedBrainz\TypesenseAggregator\Managers\EngineManager;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TypesenseAggregatorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('scout-typesense-aggregator')
            ->hasCommand(MakeTypesenseAggregtorCommand::class);
    }

    public function registeringPackage()
    {
        $this->app->singleton(TypesenseAggregatorObserver::class, TypesenseAggregatorObserver::class);

        $this->app->bind(EngineManager::class, function ($app) {
            return new EngineManager($app);
        });

        $this->app->alias(EngineManager::class, \Laravel\Scout\EngineManager::class);

        $this->app->bind(TypesenseEngine::class, function ($app): TypesenseEngine {
            return $app->make(\Laravel\Scout\EngineManager::class)->createTypesenseDriver();
        });

        $this->app->alias(TypesenseEngine::class, 'typesense.engine');
    }
}
