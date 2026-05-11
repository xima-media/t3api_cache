<?php

namespace Xima\T3ApiCache\Context;

use TYPO3\CMS\Core\Context\AspectInterface;
use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;

class ApiCacheAspect implements AspectInterface
{
    protected bool $apiCacheActive = false;

    protected bool $disableTableNameTag = false;

    public function __construct(bool $apiCacheActive, bool $disableTableNameTag = false)
    {
        $this->apiCacheActive = $apiCacheActive;
        $this->disableTableNameTag = $disableTableNameTag;
    }

    public function get(string $name): bool
    {
        if ($name === 'isActive') {
            return $this->apiCacheActive;
        }
        if ($name === 'disableTableNameTag') {
            return $this->disableTableNameTag;
        }
        throw new AspectPropertyNotFoundException('Property "' . $name . '" not found in Aspect "' . __CLASS__ . '".', 1735135381);
    }
}
