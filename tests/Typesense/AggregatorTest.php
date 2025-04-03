<?php

use NeedBrainz\TypesenseAggregator\Tests\TestSupport\TestModels\Aggregator;

it('return correct raw results', function () {
    $this->importScoutIndexFrom(Aggregator::class);

    $search = Aggregator::search('');
    $raw = $search->raw();

    $this->assertIsArray($raw);
    $this->assertEquals(count($this->models), $raw['found']);
    $this->assertIsArray($raw['hits']);
});

it('return correct results', function () {
    $search = Aggregator::search('');
    dd($search->get());
});
