<?php

namespace NeedBrainz\TypesenseAggregator;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Events\ModelsImported;
use Laravel\Scout\Searchable;
use NeedBrainz\TypesenseAggregator\Exceptions\ModelNotDefinedInAggregatorException;

use function in_array;

class TypesenseAggregator
{
    use Searchable;

    /**
     * The related models that should be aggregated.
     *
     * @var string[]
     */
    protected $models = [];

    /**
     * The model being queried, if any.
     *
     * @var \Illuminate\Database\Eloquent\Model|null
     */
    protected $model;

    /**
     * The relationships per model that should be loaded.
     *
     * @var mixed[]
     */
    protected $relations = [];

    /**
     * Returns the index name.
     *
     * @var string
     */
    protected $indexName;

    final public function __construct() {}

    public function typesenseCollectionSchema(): array
    {
        return config('scout.typesense.model-settings.'.static::class.'.collection-schema', []);
    }

    /**
     * Boot the aggregator.
     */
    public static function bootSearchable(): void
    {
        ($self = new static)->registerSearchableMacros();
        $observer = tap(app(TypesenseAggregatorObserver::class))->setAggregator(static::class, $models = (new static)->getModels());
        foreach ($models as $model) {
            $model::observe($observer);
        }
    }

    public static function create(Model $model): TypesenseAggregator
    {
        return (new static)->setModel($model);
    }

    /**
     * Get the names of the models that should be aggregated.
     *
     * @return string[]
     */
    public function getModels(): array
    {
        return $this->models;
    }

    public function getModel(): Model
    {
        if ($this->model === null) {
            throw new ModelNotDefinedInAggregatorException;
        }

        return $this->model;
    }

    public function setModel(Model $model): TypesenseAggregator
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Get the relations to load.
     *
     * @param  string  $modelClass
     */
    public function getRelations($modelClass): array
    {
        return $this->relations[$modelClass] ?? [];
    }

    /**
     * Get the value used to index the model.
     *
     * @return string
     */
    public function getScoutKey(?Model $model = null)
    {
        if (! $model) {
            $model = $this->model;
        }
        if ($model === null) {
            throw new ModelNotDefinedInAggregatorException;
        }
        if (method_exists($model, 'getScoutKey')) {
            $key = $model->getScoutKey();
        } else {
            $key = $model->getKey();
        }

        return get_class($model).'::'.$key;
    }

    /**
     * Return the model class and ID from the scout key.
     *
     * @return array
     */
    public function extractScoutKey(string $key)
    {
        $parts = explode('::', $key, 2);
        if (count($parts) !== 2) {
            throw new \RuntimeException('Invalid scout key format');
        }

        return [
            'model' => $parts[0],
            'id' => $parts[1],
        ];
    }

    /**
     * Get the index name for the searchable.
     */
    public function searchableAs(): string
    {
        return config('scout.prefix').str_replace('\\', '', Str::snake(class_basename(static::class)));
    }

    /**
     * Get the searchable array of the searchable.
     */
    public function toSearchableArray(): array
    {
        if ($this->model === null) {
            throw new ModelNotDefinedInAggregatorException;
        }

        return array_merge(method_exists($this->model, 'toSearchableArray') ? $this->model->toSearchableArray() :
           $this->model->toArray(), [
               'id' => (string) $this->getScoutKey(),
           ]);
    }

    /**
     * Make all instances of the model searchable.
     *
     * @return void
     */
    public static function makeAllSearchable()
    {
        foreach ((new static)->getModels() as $model) {
            $instance = new $model;

            $softDeletes =
               in_array(SoftDeletes::class, class_uses_recursive($model)) && config('scout.soft_delete', false);

            $instance->newQuery()->when($softDeletes, function ($query) {
                $query->withTrashed();
            })->orderBy($instance->getKeyName())->chunk(config('scout.chunk.searchable', 500), function ($models) {
                $models = $models->map(function ($model) {
                    return static::create($model);
                })->filter->shouldBeSearchable();

                $models->searchable();

                event(new ModelsImported($models));
            });
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchableCount(): int
    {
        $count = 0;

        foreach ($this->getModels() as $model) {
            $softDeletes =
               in_array(SoftDeletes::class, class_uses_recursive($model), true) && config('scout.soft_delete', false);

            $count += $model::query()->when($softDeletes, function ($query) {
                $query->withTrashed();
            })->count();
        }

        return (int) $count;
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function searchable(): void
    {
        TypesenseAggregatorCollection::make([$this])->searchable();
    }

    /**
     * {@inheritdoc}
     *
     * @internal
     */
    public function unsearchable(): void
    {
        TypesenseAggregatorCollection::make([$this])->unsearchable();
    }

    /**
     * Create a new Eloquent Collection instance.
     */
    public function newCollection(array $searchables = []): Collection
    {
        return new Collection($searchables);
    }

    /**
     * Dispatch the job to make the given models unsearchable.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @return void
     */
    public function queueRemoveFromSearch($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        $models->first()->searchableUsing()->delete($models);
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $model = $this->model ?? new class extends Model {};

        return $model->$method(...$parameters);
    }
}
