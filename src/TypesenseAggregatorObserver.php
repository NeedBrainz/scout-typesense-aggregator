<?php

namespace NeedBrainz\TypesenseAggregator;

use Laravel\Scout\ModelObserver;

use function get_class;

class TypesenseAggregatorObserver extends ModelObserver
{
    /**
     * Array with models and their aggregators.
     *
     * @var array
     */
    private $aggregators = [];

    /**
     * Set the aggregator.
     *
     * @param  string[]  $models
     */
    public function setAggregator(string $aggregator, array $models): void
    {
        foreach ($models as $model) {
            if (! array_key_exists($model, $this->aggregators)) {
                $this->aggregators[$model] = [];
            }

            $this->aggregators[$model][] = $aggregator;
        }
    }

    /**
     * Set multiple aggregators.
     *
     * @param  string[]  $aggregators
     */
    public function setAggregators(array $aggregators, string $model): void
    {
        if (! array_key_exists($model, $this->aggregators)) {
            $this->aggregators[$model] = [];
        }

        $this->aggregators[$model] = $aggregators;
    }

    /**
     * {@inheritdoc}
     */
    public function saved($model): void
    {
        $class = get_class($model);
        if (! array_key_exists($class, $this->aggregators)) {
            return;
        }

        foreach ($this->aggregators[$class] as $aggregator) {
            parent::saved($aggregator::create($model));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleted($model): void
    {
        if (static::syncingDisabledFor($model)) {
            return;
        }

        if ($this->usesSoftDelete($model) && config('scout.soft_delete', false)) {
            $this->saved($model);
        } else {
            $class = get_class($model);

            if (! array_key_exists($class, $this->aggregators)) {
                return;
            }

            foreach ($this->aggregators[$class] as $aggregator) {
                $aggregator::create($model)->unsearchable();
            }
        }
    }

    /**
     * Handle the force deleted event for the model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     */
    public function forceDeleted($model): void
    {
        if (static::syncingDisabledFor($model)) {
            return;
        }

        $class = get_class($model);

        if (! array_key_exists($class, $this->aggregators)) {
            return;
        }

        foreach ($this->aggregators[$class] as $aggregator) {
            $aggregator::create($model)->unsearchable();
        }
    }
}
