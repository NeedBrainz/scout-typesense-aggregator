<?php

namespace NeedBrainz\TypesenseAggregator\Tests;

use Illuminate\Database\Schema\Blueprint;
use Laravel\Scout\ScoutServiceProvider;
use NeedBrainz\TypesenseAggregator\Tests\TestSupport\Providers\TestProvider;
use NeedBrainz\TypesenseAggregator\Tests\TestSupport\TestModels\Aggregator;
use NeedBrainz\TypesenseAggregator\Tests\TestSupport\TestModels\TestModel1;
use NeedBrainz\TypesenseAggregator\Tests\TestSupport\TestModels\TestModel2;
use NeedBrainz\TypesenseAggregator\TypesenseAggregatorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    public $models = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupDatabase($this->app);
    }

    protected function getPackageProviders($app)
    {
        return [
            TypesenseAggregatorServiceProvider::class,
            ScoutServiceProvider::class,
            TestProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
    }

    protected function setUpDatabase($app)
    {
        $base_schema = [
            'collection-schema' => [
                'fields' => [
                    [
                        'name' => 'id',
                        'type' => 'string',
                    ],
                    [
                        'name' => 'name',
                        'type' => 'string',
                    ],
                ],
            ],
            'search-parameters' => [
                'query_by' => 'name',
            ],
        ];
        config()->set('scout.typesense.model-settings', [
            TestModel1::class => $base_schema,
            TestModel2::class => $base_schema,
            Aggregator::class => $base_schema,
        ]);
        $app['db']->connection()->getSchemaBuilder()->create('test_model_1', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
        $app['db']->connection()->getSchemaBuilder()->create('test_model_2', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        $this->models[] = TestModel1::create([
            'name' => 'Model 1 - A',
        ]);
        $this->models[] = TestModel1::create([
            'name' => 'Model 1 - B',
        ]);

        $this->models[] = TestModel2::create([
            'name' => 'Model 2 - A',
        ]);
        $this->models[] = TestModel2::create([
            'name' => 'Model 2 - B',
        ]);
        $this->models[] = TestModel2::create([
            'name' => 'Model 2 - C',
        ]);
    }
}
