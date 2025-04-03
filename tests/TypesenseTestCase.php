<?php

namespace NeedBrainz\TypesenseAggregator\Tests;

use Orchestra\Testbench\Attributes\RequiresEnv;
use Orchestra\Testbench\Attributes\WithConfig;

use function Orchestra\Testbench\artisan;
use function Orchestra\Testbench\remote;

#[RequiresEnv('TYPESENSE_API_KEY')]
#[WithConfig('scout.driver', 'typesense')]
abstract class TypesenseTestCase extends TestCase
{
    protected function importScoutIndexFrom($model = null)
    {

        artisan($this, 'scout:flush', ['model' => $model]);
        artisan($this, 'scout:import', ['model' => $model]);

        sleep(1);
    }

    /**
     * Clean up the testing environment before the next test case.
     */
    public static function tearDownAfterClass(): void
    {
        remote('scout:delete-all-indexes')->mustRun();

        parent::tearDownAfterClass();
    }
}
