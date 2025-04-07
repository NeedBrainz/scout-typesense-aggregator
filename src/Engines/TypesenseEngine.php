<?php

namespace NeedBrainz\TypesenseAggregator\Engines;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\TypesenseEngine as BaseTypesenseEngine;
use Laravel\Scout\Searchable;
use NeedBrainz\TypesenseAggregator\TypesenseAggregator;

class TypesenseEngine extends BaseTypesenseEngine
{
    /**
     * {@inheritdoc}
     */
    public function map(Builder $builder, $results, $model)
    {
        if ($model instanceof TypesenseAggregator) {
            return $this->fromAggregation($builder, $results, $model);
        } else {
            return parent::map($builder, $results, $model);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function lazyMap(Builder $builder, $results, $model)
    {
        if ($model instanceof TypesenseAggregator) {
            return LazyCollection::make($this->fromAggregation($builder, $results, $model));
        } else {
            return parent::lazyMap($builder, $results, $model);
        }
    }

    protected function fromAggregation(Builder $builder, array $results, $aggregator): Collection
    {
        if ($this->getTotalCount($results) === 0) {
            return $aggregator->newCollection();
        }

        $instances = collect();
        $hits = isset($results['grouped_hits']) && ! empty($results['grouped_hits'])
            ? $results['grouped_hits']
            : $results['hits'];

        $pluck = isset($results['grouped_hits']) && ! empty($results['grouped_hits'])
            ? 'hits.0.document.id'
            : 'document.id';

        $objectIds = collect($hits)
            ->pluck($pluck)
            ->values()
            ->all();

        $models = [];
        foreach ($objectIds as $id) {
            try {
                [$modelClass, $modelId] = explode('::', $id, 2);
            } catch (\Exception $e) {
                continue;
            }
            if (! isset($groupedIds[$modelClass])) {
                $groupedIds[$modelClass] = [];
            }
            $models[$modelClass][] = $modelId;
        }

        foreach ($models as $modelClass => $modelKeys) {
            $model = new $modelClass;

            if (in_array(Searchable::class, class_uses_recursive($model), true)) {
                if (! empty($models = $model->getScoutModelsByIds($builder, $modelKeys))) {
                    $instances = $instances->merge($models->load($aggregator->getRelations($modelClass)));
                }
            } else {
                $query = in_array(
                    SoftDeletes::class,
                    class_uses_recursive($model),
                    true
                ) ? $model->withTrashed() : $model->newQuery();

                if ($builder->queryCallback) {
                    call_user_func($builder->queryCallback, $query);
                }

                $scoutKey = method_exists(
                    $model,
                    'getScoutKeyName'
                ) ? $model->getScoutKeyName() : $model->getQualifiedKeyName();
                if ($models = $query->whereIn($scoutKey, $modelKeys)->get()) {
                    $instances = $instances->merge($models->load($aggregator->getRelations($modelClass)));
                }
            }
        }
        $result = $aggregator->newCollection();
        foreach ($objectIds as $id) {
            foreach ($instances as $instance) {
                if ($aggregator->getScoutKey($instance) === (string) $id) {
                    $result->push($instance);
                    break;
                }
            }
        }

        return $result;
    }
}
