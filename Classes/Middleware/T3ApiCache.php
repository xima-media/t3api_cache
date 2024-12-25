<?php

namespace Xima\T3ApiCache\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SourceBroker\T3api\Service\RouteService;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\JsonResponse;
use Xima\T3ApiCache\Context\ApiCacheAspect;
use Xima\T3ApiCache\Reflection\ResourceReflectionFactory;

readonly class T3ApiCache implements MiddlewareInterface
{
    public function __construct(
        private FrontendInterface $cache,
        private ResponseFactoryInterface $responseFactory,
        private Context $context,
        private ResourceReflectionFactory $resourceReflectionFactory
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!RouteService::routeHasT3ApiResourceEnhancerQueryParam($request)) {
            return $handler->handle($request);
        }

        $reflectionService = $this->resourceReflectionFactory->createForRequest($request);

        $cacheKey = $reflectionService->getCacheKey();
        if (!$reflectionService->getCacheKey()) {
            return $handler->handle($request);
        }

        if ($data = $this->cache->get($cacheKey)) {
            $response = $this->responseFactory->createResponse()->withHeader('Content-Type', 'application/json; charset=utf-8');
            $response->getBody()->write($data);
            return $response;
        }

        $this->context->setAspect('t3api_cache', new ApiCacheAspect(true));

        $response = $handler->handle($request);
        $data = (string)$response->getBody();
        $lifetime = $reflectionService->getApiCacheAnnotation()?->getLifetime() ?? 86400;

        $apiData = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        $cacheTags = [];
        self::collectAndRemoveCacheTags($apiData, $cacheTags);

        $this->cache->set($cacheKey, json_encode($apiData, JSON_THROW_ON_ERROR), $cacheTags, $lifetime);

        return new JsonResponse($apiData);
    }

    /**
     * @param array<string, mixed> $data
     * @param string[] $cacheTags
     * @return array<string, mixed>
     */
    private static function collectAndRemoveCacheTags(array &$data, array &$cacheTags = []): array
    {
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                $value = self::collectAndRemoveCacheTags($value, $cacheTags);
            } elseif ($key === '@cache_tag') {
                unset($data[$key]);
                if (!in_array($value, $cacheTags, true)) {
                    $cacheTags[] = $value;
                }
            }
        }
        return $data;
    }
}
