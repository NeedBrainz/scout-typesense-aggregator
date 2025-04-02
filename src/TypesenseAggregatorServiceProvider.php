<?php

namespace NeedBrainz\TypesenseAggregator;

use NeedBrainz\TypesenseAggregator\Commands\MakeTypesenseAggregtorCommand;
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
    }
}
