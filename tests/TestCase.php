<?php

namespace NeedBrainz\TypesenseAggregator\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Laravel\Scout\ScoutServiceProvider;
use NeedBrainz\TypesenseAggregator\TypesenseAggregatorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'NeedBrainz\\TypesenseAggregator\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            TypesenseAggregatorServiceProvider::class,
            ScoutServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('scout.driver', 'typesense');
    }
}
