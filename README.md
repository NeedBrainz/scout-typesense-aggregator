# Laravel Scout Aggregator for Typesense

[![Latest Version on Packagist](https://img.shields.io/packagist/v/needbrainz/scout-typesense-aggregator.svg?style=flat-square)](https://packagist.org/packages/needbrainz/scout-typesense-aggregator)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/needbrainz/scout-typesense-aggregator/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/needbrainz/scout-typesense-aggregator/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/needbrainz/scout-typesense-aggregator/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/needbrainz/scout-typesense-aggregator/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/needbrainz/scout-typesense-aggregator.svg?style=flat-square)](https://packagist.org/packages/needbrainz/scout-typesense-aggregator)

Laravel Scout Aggregator for Typesense extends Laravel Scout with support for aggregated search across multiple models using Typesense. Based on Algolia's Scout Extended package, it adapts and extends the aggregator functionality for the open-source Typesense engine.

## Installation

You can install the package via composer:

```bash
composer require needbrainz/scout-typesense-aggregator
```

## Usage

To create a new aggregator, you can use the `make:aggregator` command:

```bash
php artisan make:typesense-aggregator MyAggregator
```


```php
<?php

namespace App\Search;

use NeedBrainz\TypesenseAggregator\TypesenseAggregator;

class MyAggregator extends TypesenseAggregator
{
    /**
     * The names of the models that should be aggregated.
     *
     * @var string[]
     */
    protected $models = [
        App\Models\MyModel::class,
        App\Models\MyOtherModel::class,
    ];
}
```
Then configure the aggregator settings in your `config/scout.php` file:

```php
'typesense' => [
    'model-settings' => [
        MyAggregator::class => [
           'collection-schema' => [
                'fields' => [
                    [
                        'name' => 'id',
                        'type' => 'string',
                    ],
                    // Your aggregated fields
                    [
                        'name' => 'name',
                        'type' => 'string',
                    ],
                    [
                        'name' => 'description',
                        'type' => 'string',
                    ],
                    [
                        'name' => 'created_at',
                        'type' => 'int64',
                    ],
                ],
                'default_sorting_field' => 'created_at',
           ],
           'search-parameters' => [
                'query_by' => 'name,description',
           ]
        ],
    ]
```

Register your Aggregator in an appropriate service provider, such as `App\Providers\AppServiceProvider`:

```php
use App\Search\MyAggregator;

public function boot(): void
{
    MyAggregator::bootSearchable();
}
```

By default the Aggregator will merge the models toSearchableArrray and set the custom id coming from the Aggregator `getScoutKey($model)` which will create an id in the format `ModelClassName::ModelScoutKey`. This format can then be reverted with the `extractScoutKey($key)` for models retrieve. So if you change the syntax, don't forget to override the `getScoutKey` and `extractScoutKey` methods in your Aggregator class.

If the structure of the models is different, you can override the `toSearchableArray` method in your Aggregator class to customize the merging of the models.

```php
public function toSearchableArray(): array
{
    $array = [
        'id' => (string )$this->getScoutKey(),
        'created_at' => $this->model->created_at->timestamp,
        // default empty values
        'name' => '',
        'description' => '',
    ];

    // Customize the merging of the models here
    if ($this->model instanceof MyModel) {
        $array['name'] = (string) $this->model->name;
        $array['description'] = (string) $this->model->description;
    } elseif ($this->model instanceof MyOtherModel) {
        $array['name'] = (string) $this->model->title;
        $array['description'] = (string) $this->model->summary;
    }

    return $array;
}
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Yann Haefliger](https://github.com/yhaefliger)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
