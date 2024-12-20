<?php

namespace Xima\T3ApiCache\Middleware;

use Doctrine\Common\Annotations\AnnotationReader;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SourceBroker\T3api\Domain\Model\ApiResource;
use SourceBroker\T3api\Domain\Repository\ApiResourceRepository;
use SourceBroker\T3api\Service\RouteService;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use Xima\T3ApiCache\Annotation\ApiCache;

class T3ApiCache implements MiddlewareInterface
{
    protected ?ApiCache $apiCacheAnnotation = null;

    private ?ApiResource $apiResource = null;

    public function __construct(
        private readonly FrontendInterface $cache,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly ApiResourceRepository $apiResourceRepository
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!RouteService::routeHasT3ApiResourceEnhancerQueryParam($request)) {
            return $handler->handle($request);
        }

        $this->setApiResource($request);
        if (!$this->apiResource) {
            return $handler->handle($request);
        }

        $this->setApiCacheAnnotation($request);
        if (!$this->apiCacheAnnotation) {
            return $handler->handle($request);
        }

        $cacheKey = $this->getCacheKey($request);
        if (!$cacheKey) {
            return $handler->handle($request);
        }

        if ($data = $this->cache->get($cacheKey)) {
            $response = $this->responseFactory->createResponse()->withHeader('Content-Type', 'application/json; charset=utf-8');
            $response->getBody()->write($data);
            return $response;
        }

        $response = $handler->handle($request);

        $tableName = $this->getTableName();
        if (!$tableName) {
            return $response;
        }

        $cacheTags = [];

        // strategy 1: add cache tag for each uid
        $data = (string)$response->getBody();
        $array = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        foreach ($array['hydra:member'] as $value) {
            if (isset($value['uid'])) {
                $cacheTags[] = $tableName . '_' . $value['uid'];
            }
        }

        // strategy 2: add cache tag for the whole table
        // $cacheTags = [$tableName];

        $this->cache->set($cacheKey, $data, $cacheTags, $this->apiCacheAnnotation->getLifetime());

        return $response;
    }

    protected function setApiResource(ServerRequestInterface $request): void
    {
        foreach ($this->apiResourceRepository->getAll() as $resource) {
            foreach ($resource->getCollectionOperations() as $operation) {
                $route = $operation->getRoute();
                if ($route->getPath() === $request->getUri()->getPath()) {
                    $this->apiResource = $resource;
                }
            }
        }
    }

    private function setApiCacheAnnotation(ServerRequestInterface $request): void
    {
        $annotationReader = GeneralUtility::makeInstance(AnnotationReader::class);
        $annotations = $annotationReader->getClassAnnotations(new \ReflectionClass($this->apiResource->getEntity()));
        foreach ($annotations as $annotation) {
            if ($annotation instanceof ApiCache) {
                $this->apiCacheAnnotation = $annotation;
                return;
            }
        }
    }

    protected function getCacheKey(ServerRequestInterface $request): string
    {
        $queryParams = $request->getQueryParams();
        $paramsToIgnore = $this->apiCacheAnnotation->getQueryParamsToIgnore();
        if (in_array('*', $paramsToIgnore, true) && count($queryParams)) {
            return '';
        }

        foreach ($queryParams as $key => $value) {
            if (!empty($value) && in_array($key, $paramsToIgnore, true)) {
                return '';
            }
        }

        return md5($request->getUri()->getPath() . '?' . http_build_query($queryParams));
    }

    protected function getTableName(): ?string
    {
        $entity = $this->apiResource->getEntity();
        $dataMapper = GeneralUtility::makeInstance(DataMapper::class);
        return $dataMapper->getDataMap($entity)->getTableName();
    }
}
