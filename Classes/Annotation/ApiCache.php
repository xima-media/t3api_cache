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

    public function __construct(array $options = [])
    {
        if (isset($options['strategy'])) {
            $this->strategy = $options['strategy'];
        }
        if (isset($options['queryParamsToIgnore'])) {
            $this->queryParamsToIgnore = $options['queryParamsToIgnore'];
        }
    }

    public function getQueryParamsToIgnore(): array
    {
        return $this->queryParamsToIgnore;
    }
}
