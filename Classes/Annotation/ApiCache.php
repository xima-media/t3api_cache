<?php

namespace Xima\T3ApiCache\Annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class ApiCache
{
    protected CacheStrategy $strategy = CacheStrategy::MULTIPLE;

    /**
     * @var string[]
     */
    protected array $queryParamsToIgnore = [];

    protected ?int $lifetime = null;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        if (isset($options['strategy'])) {
            $this->strategy = CacheStrategy::from($options['strategy']);
        }
        if (isset($options['queryParamsToIgnore'])) {
            $this->queryParamsToIgnore = $options['queryParamsToIgnore'];
        }
        if (isset($options['lifetime'])) {
            $this->lifetime = $options['lifetime'];
        }
    }

    public function getStrategy(): CacheStrategy
    {
        return $this->strategy;
    }

    public function getLifetime(): ?int
    {
        return $this->lifetime;
    }

    /**
     * @return string[]
     */
    public function getQueryParamsToIgnore(): array
    {
        return $this->queryParamsToIgnore;
    }
}
