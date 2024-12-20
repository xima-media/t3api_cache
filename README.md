# TYPO3 extension `t3api_cache`

This extension provides a cache for the TYPO3 extension [t3api](https://github.com/sourcebroker/t3api).

## Installation

1. Install the extension via composer `composer require sourcebroker/t3api_cache`.

## Usage

The extension provides a new Annotation `@ApiCache` which can be used in your `ApiResource` class:

```php
<?php

/**
 * @ApiResource(
 *     collectionOperations={
 *         "get": {
 *             "path": "/news"
 *         }
 *     }
 * )
 * @ApiCache()
 */
class Division extends AbstractEntity
{
}
```
