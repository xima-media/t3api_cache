<?php

namespace Xima\T3ApiCache\Context;

use TYPO3\CMS\Core\Context\AspectInterface;
use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;

class ApiCacheAspect implements AspectInterface
{
    protected bool $apiCacheActive = false;

    public function __construct(bool $apiCacheActive)
    {
        $this->apiCacheActive = $apiCacheActive;
    }

    public function get(string $name): bool
    {
        if ($name === 'isActive') {
            return $this->apiCacheActive;
        }
        throw new AspectPropertyNotFoundException('Property "' . $name . '" not found in Aspect "' . __CLASS__ . '".', 1735135381);
    }
}
