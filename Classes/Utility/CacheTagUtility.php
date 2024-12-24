<?php

namespace Xima\T3ApiCache\Utility;

use Xima\T3ApiCache\Annotation\CacheStrategy;
use Xima\T3ApiCache\Reflection\ResourceReflectionService;

readonly class CacheTagUtility
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(private ResourceReflectionService $reflectionService, private array $data)
    {
    }

    /**
     * @return string[]
     */
    public function getCacheTags(): array
    {
        return match ($this->reflectionService->getCacheStrategy()) {
            CacheStrategy::SINGLE => $this->getSingleTag(),
            CacheStrategy::MULTIPLE => $this->getMultipleTags(),
            CacheStrategy::DEEP => $this->getMultipleTagsDeep(),
            default => [],
        };
    }

    /**
     * @return string[]
     */
    private function getSingleTag(): array
    {
        return [$this->reflectionService->getTableName()];
    }

    /**
     * @return string[]
     */
    private function getMultipleTags(): array
    {
        $cacheTags = [];
        foreach ($this->data['hydra:member'] ?? [] as $value) {
            if (isset($value['uid'])) {
                $cacheTags[] = $this->reflectionService->getTableName() . '_' . $value['uid'];
            }
        }
        return $cacheTags;
    }

    /**
     * @return string[]
     */
    private function getMultipleTagsDeep(): array
    {
        return [];
    }
}
