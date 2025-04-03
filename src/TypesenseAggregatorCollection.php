<?php

namespace NeedBrainz\TypesenseAggregator;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Queue\SerializesAndRestoresModelIdentifiers;
use Illuminate\Support\Collection;

use function get_class;

/**
 * @method static void searchable()
 */
class TypesenseAggregatorCollection extends Collection
{
    use SerializesAndRestoresModelIdentifiers;

    /**
     * The class name of the aggregator.
     *
     * @var string|null
     */
    public $aggregator;

    /**
     * Make all the models in this collection unsearchable.
     */
    public function unsearchable(): void
    {
        $aggregator = get_class($this->first());

        (new $aggregator)->queueRemoveFromSearch($this);
    }

    /**
     * Prepare the instance for serialization.
     *
     * @return string[]
     */
    public function __sleep()
    {
        $this->aggregator = get_class($this->first());

        $this->items = $this->getSerializedPropertyValue(EloquentCollection::make($this->map(function ($aggregator) {
            return $aggregator->getModel();
        })));

        return ['aggregator', 'items'];
    }

    /**
     * Restore the model after serialization.
     *
     * @return void
     */
    public function __wakeup()
    {
        $this->items = $this->getRestoredPropertyValue($this->items)->map(function ($model) {
            return $this->aggregator::create($model);
        })->toArray();
    }
}
