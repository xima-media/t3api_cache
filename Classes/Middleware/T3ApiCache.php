<?php

namespace Xima\T3ApiCache\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SourceBroker\T3api\Service\RouteService;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\T3ApiCache\Reflection\ResourceReflectionFactory;
use Xima\T3ApiCache\Utility\CacheTagUtility;

readonly class T3ApiCache implements MiddlewareInterface
{
    public function __construct(
        private FrontendInterface $cache,
        private ResponseFactoryInterface $responseFactory
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!RouteService::routeHasT3ApiResourceEnhancerQueryParam($request)) {
            return $handler->handle($request);
        }

        $reflectionService = GeneralUtility::makeInstance(ResourceReflectionFactory::class)->createForRequest($request);
        if (!$reflectionService->isCacheable()) {
            return $handler->handle($request);
        }

        $cacheKey = $reflectionService->getCacheKey();

        if ($data = $this->cache->get($cacheKey)) {
            $response = $this->responseFactory->createResponse()->withHeader('Content-Type', 'application/json; charset=utf-8');
            $response->getBody()->write($data);
            return $response;
        }

        $response = $handler->handle($request);
        $data = (string)$response->getBody();
        $array = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

        $cacheTagUtility = new CacheTagUtility($reflectionService, $array);
        $cacheTags = $cacheTagUtility->getCacheTags();

        $this->cache->set($cacheKey, $data, $cacheTags, $reflectionService->getLifetime());

        return $response;
    }
}
