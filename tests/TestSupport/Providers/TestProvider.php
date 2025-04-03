<?php

namespace NeedBrainz\TypesenseAggregator\Tests\TestSupport\Providers;

use Illuminate\Support\ServiceProvider;
use NeedBrainz\TypesenseAggregator\Tests\TestSupport\TestModels\Aggregator;

class TestProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void {}

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Aggregator::bootSearchable();
    }
}
