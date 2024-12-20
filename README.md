# TYPO3 extension `t3api_cache`

This extension provides a cache for the TYPO3 extension [t3api](https://github.com/sourcebroker/t3api).

## Installation

1. Install the extension via composer `composer require sourcebroker/t3api_cache`.

## Usage

The extension provides a new Annotation `@ApiCache` which can be used in your `ApiResource` class:

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
* @ApiFilter(SearchFilter::class, properties={"title": "partial", "teaser": "partial"}, arguments={"parameterName": "search"})
* @ApiCache(queryParamsToIgnore={"search"})
*/
class Division extends AbstractEntity
{
}
```

## Configuration

There a several configuration options available:

### `queryParamsToIgnore`

This option allows to prevent caching of the response for specific query parameters. This can be useful if you have a search parameter which should not be cached.

### `lifetime`

The lifetime of the cache entry in seconds.

### `strategy`

@TODO
