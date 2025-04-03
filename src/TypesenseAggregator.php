<?php

namespace NeedBrainz\TypesenseAggregator;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Builder;
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

    final public function __construct()
    {
    }

    public function typesenseCollectionSchema(): array
    {
        return config('scout.typesense.model-settings.' . static::class . '.collection-schema', []);
    }

    /**
     * Boot the aggregator.
     */
    public static function bootSearchable(): void
    {
        ($self = new static())->registerSearchableMacros();
        $observer = tap(app(TypesenseAggregatorObserver::class))->setAggregator(static::class, $models = (new static())->getModels());
        foreach ($models as $model) {
            $model::observe($observer);
        }
    }

    public static function create(Model $model): TypesenseAggregator
    {
        return (new static())->setModel($model);
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
            throw new ModelNotDefinedInAggregatorException();
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
     * @return mixed
     */
    public function getScoutKey(?Model $model = null)
    {
        if (!$model) {
            $model = $this->model;
        }
        if ($model === null) {
            throw new ModelNotDefinedInAggregatorException();
        }

        return get_class($model) . '::' . $model->getScoutKey();
    }

    /**
     * Get the index name for the searchable.
     */
    public function searchableAs(): string
    {
        return config('scout.prefix') . str_replace('\\', '', Str::snake(class_basename(static::class)));
    }

    /**
     * Get the searchable array of the searchable.
     */
    public function toSearchableArray(): array
    {
        if ($this->model === null) {
            throw new ModelNotDefinedInAggregatorException();
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
        foreach ((new static())->getModels() as $model) {
            $instance = new $model();

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
     * Get the requested models from an array of object IDs.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @param  array  $ids
     * @return \Illuminate\Support\Collection
     */
    public function getScoutModelsByIds(Builder $builder, array $ids)
    {
        // Group IDs by model class
        $groupedIds = $this->groupIdsByModel($ids);

        // Initialize an empty collection to store results
        $results = collect();

        // For each model class, fetch the corresponding models
        foreach ($groupedIds as $modelClass => $modelIds) {
            $instance = new $modelClass();
            $query = static::usesSoftDelete() && method_exists($instance, 'withTrashed')
            ? $instance->withTrashed()
            : $instance->newQuery();

            if ($builder->queryCallback) {
                call_user_func($builder->queryCallback, $query);
            }

            $whereIn = in_array($instance->getScoutKeyType(), ['int', 'integer'])
            ? 'whereIntegerInRaw'
            : 'whereIn';

            $models = $query->{$whereIn}(
                $instance->qualifyColumn($instance->getScoutKeyName()),
                $modelIds
            )->get();
            $results = $results->merge($models);
        }

        // Reorder results to match the original order of IDs
        return $results;
    }

    /**
     * Group IDs by model class.
     *
     * @param  array  $ids
     * @return array
     */
    protected function groupIdsByModel(array $ids)
    {
        $groupedIds = [];
        foreach ($ids as $id) {
            try {
                list($modelClass, $modelId) = explode('::', $id, 2);
            } catch (\Exception $e) {
                continue;
            }
            if (!isset($groupedIds[$modelClass])) {
                $groupedIds[$modelClass] = [];
            }
            $groupedIds[$modelClass][] = $modelId;
        }

        return $groupedIds;
    }



    /**
     * Get a query builder for retrieving the requested models from an array of object IDs.
     *
     * Note: This method is not used directly because we need to query multiple models.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @param  array  $ids
     * @return mixed
     */
    public function queryScoutModelsByIds(Builder $builder, array $ids)
    {
        // This method is kept for backwards compatibility
        // but will not be used directly by getScoutModelsByIds anymore

        throw new \RuntimeException('This method is not used directly anymore.');
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
        $model = $this->model ?? new class extends Model {
        };

        return $model->$method(...$parameters);
    }
}
