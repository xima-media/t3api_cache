# TYPO3 extension `t3api_cache`

This extension provides a simple cache for the API response of the TYPO3 extension [t3api](https://github.com/sourcebroker/t3api).

## Installation

Install the extension via composer:

```bash
composer require xima/t3api-cache
```

## Usage

The extension provides a new Annotation `@ApiCache` which can be used in your `ApiResource` class to active caching for the specific resource:

```php
<?php

use Xima\T3ApiCache\Annotation\ApiCache;

/**
 * @ApiResource(
 *     collectionOperations={
 *         "get": {
 *             "path": "/news"
 *         }
 *     }
 * )
 * @ApiCache
 */
class News extends AbstractEntity
{
}
```

## Configuration

There a two configuration options available:

### `parametersToIgnore`

This option allows you to prevent caching of the response for specific query parameters. This can be useful if you have e.g. a search parameter
which should not be cached, since it is too individual.

```php
<?php

use SourceBroker\T3api\Annotation\ApiFilter;
use SourceBroker\T3api\Filter\SearchFilter;
use Xima\T3ApiCache\Annotation\ApiCache;

/**
 * ...
 * @ApiFilter(SearchFilter::class, properties={"title": "partial", "teaser": "partial"}, arguments={"parameterName": "search"})
 * @ApiCache(parametersToIgnore={"search"})
 */
class ExampleResource extends AbstractEntity
{
}
```

### `lifetime`

The lifetime of the cache entry in seconds. Default is 86400 (1 day).

```php
<?php

use Xima\T3ApiCache\Annotation\ApiCache;

/**
 * ...
 * @ApiCache(lifetime=3600) // Cache lifetime set to 1 hour
 */
class ExampleResource extends AbstractEntity
{
}
```
