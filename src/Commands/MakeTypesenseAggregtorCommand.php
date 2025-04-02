<?php

namespace NeedBrainz\TypesenseAggregator\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeTypesenseAggregtorCommand extends GeneratorCommand
{
    public $signature = 'make:typesense-aggregator {name : The name of the aggregator}';

    public $description = 'Create a new Typesense aggregator class';

    protected $type = 'Typesense Aggregator';

    protected function getStub(): string
    {
        return __DIR__.'/stubs/aggregator.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Search';
    }
}
