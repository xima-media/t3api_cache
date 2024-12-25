<?php

namespace Xima\T3ApiCache\Annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class ApiCache
{
    /**
     * @var string[]
     */
    protected array $parametersToIgnore = [];

    protected ?int $lifetime = null;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        if (isset($options['parametersToIgnore'])) {
            $this->parametersToIgnore = $options['parametersToIgnore'];
        }
        if (isset($options['lifetime'])) {
            $this->lifetime = $options['lifetime'];
        }
    }

    public function getLifetime(): ?int
    {
        return $this->lifetime;
    }

    /**
     * @return string[]
     */
    public function getParametersToIgnore(): array
    {
        return $this->parametersToIgnore;
    }
}
