<?php

namespace NeedBrainz\TypesenseAggregator\Tests\TestSupport\TestModels;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class BaseModel extends Model
{
    use Searchable;

    protected $fillable = [
        'name',
    ];

    public function toSearchableArray()
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
        ];
    }
}
