<?php

namespace NeedBrainz\TypesenseAggregator\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \NeedBrainz\TypesenseAggregator\TypesenseAggregator
 */
class TypesenseAggregator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \NeedBrainz\TypesenseAggregator\TypesenseAggregator::class;
    }
}
