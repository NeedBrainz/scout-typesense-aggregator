<?php

namespace NeedBrainz\TypesenseAggregator\Managers;

use Laravel\Scout\EngineManager as BaseEngineManager;
use NeedBrainz\TypesenseAggregator\Engines\TypesenseEngine;
use Typesense\Client as Typesense;

class EngineManager extends BaseEngineManager
{
    /**
     * Create a Typesense engine instance.
     *
     * @return \Laravel\Scout\Engines\TypesenseEngine
     *
     * @throws \Typesense\Exceptions\ConfigError
     */
    public function createTypesenseDriver()
    {
        $config = config('scout.typesense');
        $this->ensureTypesenseClientIsInstalled();

        return new TypesenseEngine(new Typesense($config['client-settings']), $config['max_total_results'] ?? 1000);
    }
}
