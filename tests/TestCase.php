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
    public $models = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupDatabase($this->app);
    }

    protected function getPackageProviders($app)
    {
        return [
            ScoutServiceProvider::class,
            TestProvider::class,
            TypesenseAggregatorServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
    }

    protected function setUpDatabase($app)
    {
        $app['db']->connection()->getSchemaBuilder()->create('test_model_1', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('order')->default(0);
            $table->timestamps();
        });
        $app['db']->connection()->getSchemaBuilder()->create('test_model_2', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

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
                    [
                        'name' => 'order',
                        'type' => 'int32',
                    ],
                ],
                'default_sorting_field' => 'order',
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

        $this->models = collect();
        // Create the test models with mixed order values
        $this->models->push(TestModel1::create([
            'name' => 'model1-a',
            'order' => 4,
        ]));
        $this->models->push(TestModel1::create([
            'name' => 'model1-b',
            'order' => 1,
        ]));

        $this->models->push(TestModel2::create([
            'name' => 'model2-a',
            'order' => 5,
        ]));
        $this->models->push(TestModel2::create([
            'name' => 'model2-b',
            'order' => 3,
        ]));
        $this->models->push(TestModel2::create([
            'name' => 'model2-c',
            'order' => 2,
        ]));

        // Order the models by their order value
        $this->models = $this->models->sortByDesc('order')->values();
    }
}
