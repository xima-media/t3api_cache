<?php

namespace Xima\T3ApiCache\Annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class ApiCache
{
    protected string $strategy = '';

    protected array $queryParamsToIgnore = [];

    protected ?int $lifetime = null;

    public function __construct(array $options = [])
    {
        if (isset($options['strategy'])) {
            $this->strategy = $options['strategy'];
        }
        if (isset($options['queryParamsToIgnore'])) {
            $this->queryParamsToIgnore = $options['queryParamsToIgnore'];
        }
        if (isset($options['lifetime'])) {
            $this->lifetime = $options['lifetime'];
        }
    }

    public function getStrategy(): string
    {
        return $this->strategy;
    }

    public function getLifetime(): ?int
    {
        return $this->lifetime;
    }

    public function getQueryParamsToIgnore(): array
    {
        return $this->queryParamsToIgnore;
    }
}
