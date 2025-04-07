<?php

use NeedBrainz\TypesenseAggregator\Tests\TestSupport\TestModels\Aggregator;

it('return correct raw results', function () {
    $this->importScoutIndexFrom(Aggregator::class);

    $search = Aggregator::search('');
    $raw = $search->raw();

    $this->assertIsArray($raw);
    $this->assertEquals($this->models->count(), $raw['found']);
    $this->assertIsArray($raw['hits']);
});

it('return correct search results', function () {
    $search = Aggregator::search('');
    $results = $search->get();

    $this->assertIsArray($results->all());
    $this->assertEquals($this->models->count(), $results->count());

    // Ensur e the results are sorted by order
    $this->assertEquals($this->models->pluck('order')->sort()->values()->all(), $results->pluck('order')->sort()->values()->all());
});

it('return correct lazy search results', function () {
    $search = Aggregator::search('');
    $results = $search->cursor();

    $this->assertIsArray($results->all());
    $this->assertEquals($this->models->count(), $results->count());

    // Ensur e the results are sorted by order
    $this->assertEquals($this->models->pluck('order')->sort()->values()->all(), $results->pluck('order')->sort()->values()->all());
});
