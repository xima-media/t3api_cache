<?php

namespace Xima\T3ApiCache\Reflection;

use Psr\Http\Message\ServerRequestInterface;
use SourceBroker\T3api\Domain\Repository\ApiResourceRepository;

readonly class ResourceReflectionFactory
{
    public function __construct(private ApiResourceRepository $apiResourceRepository)
    {
    }

    public function createForRequest(ServerRequestInterface $request): ResourceReflectionService
    {
        return new ResourceReflectionService(
            $request,
            $this->apiResourceRepository
        );
    }
}
