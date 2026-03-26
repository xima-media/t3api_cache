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
no cache hits occur. The `@ApiCacheRoundDatetime` annotation can be placed on **properties or methods** to round the corresponding
datetime filter parameter value to a configurable precision before the cache key is generated. This ensures that requests within
the same time window produce the same cache key, significantly improving cache hit rates.

Multiple `@ApiCacheRoundDatetime` annotations can be used — one per property or method.

The annotation accepts the following options:

- `precision`: The rounding precision. Supported values are `minute`, `hour`, `day`, and `year`. Default is `hour`.
- `direction` (optional): The rounding direction. Use `floor` (default) to round down or `ceil` to round up.
- `parameterName` (optional): Override the query parameter name. If not set, the property or method name is used.

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
*/
class Event extends AbstractEntity
{
    /**
     * @ApiCacheRoundDatetime(precision="hour")
     */
    protected \DateTime $date;
}
```

In this example, a request with `?date=2025-03-26T09:47:12+00:00` and another with `?date=2025-03-26T09:12:45+00:00`
will both be rounded to `2025-03-26T09:00:00+00:00`, resulting in the same cache key.

**Example: Multiple datetime properties with different precisions**

```php
<?php

use Xima\T3ApiCache\Annotation\ApiCache;
use Xima\T3ApiCache\Annotation\ApiCacheRoundDatetime;

/**
* ...
* @ApiCache
*/
class Event extends AbstractEntity
{
    /**
     * @ApiCacheRoundDatetime(precision="day")
     */
    protected \DateTime $startDate;

    /**
     * @ApiCacheRoundDatetime(precision="hour", direction="ceil")
     */
    protected \DateTime $endDate;
}
```

**Example: Custom query parameter name**

If the query parameter name differs from the property name, use the `parameterName` option:

```php
<?php

use Xima\T3ApiCache\Annotation\ApiCache;
use Xima\T3ApiCache\Annotation\ApiCacheRoundDatetime;

/**
* ...
* @ApiCache
*/
class Event extends AbstractEntity
{
    /**
     * @ApiCacheRoundDatetime(precision="hour", parameterName="filter[date]")
     */
    protected \DateTime $date;
}
```

The annotation supports Unix timestamps, ISO 8601 dates, and date-only strings (e.g. `2025-03-26`).
The rounded value is returned in the same format as the input.
