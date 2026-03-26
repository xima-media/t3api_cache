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

There are multiple configuration options available:

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

### `@ApiCacheRoundDatetime`

When using datetime filters, clients often request the API with the current timestamp. Since the timestamp is always different,
no cache hits occur. The `@ApiCacheRoundDatetime` annotation allows you to round datetime filter parameter values to a
configurable precision before the cache key is generated. This ensures that requests within the same time window produce the
same cache key, significantly improving cache hit rates.

The annotation accepts the following options:

- `parameters`: A map of query parameter names to rounding precision. Supported precision values are `minute`, `hour`, `day`, and `year`.
- `direction` (optional): The rounding direction. Use `floor` (default) to round down or `ceil` to round up.

**Example: Round a datetime filter to the nearest hour (floor)**

```php
<?php

use SourceBroker\T3api\Annotation\ApiFilter;
use SourceBroker\T3api\Filter\OrderFilter;
use Xima\T3ApiCache\Annotation\ApiCache;
use Xima\T3ApiCache\Annotation\ApiCacheRoundDatetime;

/**
* @ApiResource(
*     collectionOperations={
*         "get": {
*             "path": "/event"
*         }
*     }
* )
* @ApiFilter(OrderFilter::class, properties={"date"}, arguments={"parameterName": "date"})
* @ApiCache
* @ApiCacheRoundDatetime(parameters={"date": "hour"})
*/
class Event extends AbstractEntity
{
}
```

In this example, a request with `?date=2025-03-26T09:47:12+00:00` and another with `?date=2025-03-26T09:12:45+00:00`
will both be rounded to `2025-03-26T09:00:00+00:00`, resulting in the same cache key.

**Example: Round multiple parameters with different precisions**

```php
<?php

use Xima\T3ApiCache\Annotation\ApiCache;
use Xima\T3ApiCache\Annotation\ApiCacheRoundDatetime;

/**
* ...
* @ApiCache
* @ApiCacheRoundDatetime(parameters={"startDate": "day", "endDate": "day"})
*/
class Event extends AbstractEntity
{
}
```

**Example: Round up (ceil) instead of down**

```php
<?php

use Xima\T3ApiCache\Annotation\ApiCache;
use Xima\T3ApiCache\Annotation\ApiCacheRoundDatetime;

/**
* ...
* @ApiCache
* @ApiCacheRoundDatetime(parameters={"date": "hour"}, direction="ceil")
*/
class Event extends AbstractEntity
{
}
```

With `direction="ceil"`, a request with `?date=2025-03-26T09:12:45+00:00` will be rounded up to `2025-03-26T10:00:00+00:00`.

The annotation supports Unix timestamps, ISO 8601 dates, and date-only strings (e.g. `2025-03-26`).
The rounded value is returned in the same format as the input.
