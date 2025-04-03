<?php

namespace NeedBrainz\TypesenseAggregator\Tests\TestSupport\TestModels;

use NeedBrainz\TypesenseAggregator\TypesenseAggregator;

class Aggregator extends TypesenseAggregator
{
    protected $models = [
        TestModel1::class,
        TestModel2::class,
    ];
}
