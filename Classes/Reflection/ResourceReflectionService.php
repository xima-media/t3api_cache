<?php

namespace Xima\T3ApiCache\Reflection;

use Doctrine\Common\Annotations\AnnotationReader;
use Psr\Http\Message\ServerRequestInterface;
use SourceBroker\T3api\Domain\Model\ApiResource;
use SourceBroker\T3api\Domain\Repository\ApiResourceRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use Xima\T3ApiCache\Annotation\ApiCache;
use Xima\T3ApiCache\Annotation\CacheStrategy;

class ResourceReflectionService
{
    protected ?ApiCache $apiCacheAnnotation = null;

    protected string $tableName = '';

    private ?ApiResource $apiResource = null;

    private string $cacheKey = '';

    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly ApiResourceRepository $apiResourceRepository
    ) {
        $this->setApiResource();
        $this->setApiCacheAnnotation();
        $this->setCacheKey();
        $this->setTableName();
    }

    protected function setApiResource(): void
    {
        foreach ($this->apiResourceRepository->getAll() as $resource) {
            foreach ($resource->getCollectionOperations() as $operation) {
                $route = $operation->getRoute();
                if ($route->getPath() === $this->request->getUri()->getPath()) {
                    $this->apiResource = $resource;
                }
            }
        }
    }

    private function setApiCacheAnnotation(): void
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

    private function setTableName(): void
    {
        $entity = $this->apiResource->getEntity();
        $dataMapper = GeneralUtility::makeInstance(DataMapper::class);
        $this->tableName = $dataMapper->getDataMap($entity)->getTableName();
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function isCacheable(): bool
    {
        return $this->cacheKey !== '' && $this->tableName !== '';
    }

    public function getCacheKey(): string
    {
        return $this->cacheKey;
    }

    protected function setCacheKey(): void
    {
        $queryParams = $this->request->getQueryParams();
        $paramsToIgnore = $this->apiCacheAnnotation->getQueryParamsToIgnore();
        if (in_array('*', $paramsToIgnore, true) && count($queryParams)) {
            return;
        }

        foreach ($queryParams as $key => $value) {
            if (!empty($value) && in_array($key, $paramsToIgnore, true)) {
                return;
            }
        }

        $this->cacheKey = md5($this->request->getUri()->getPath() . '?' . http_build_query($queryParams));
    }

    public function getCacheStrategy(): ?CacheStrategy
    {
        return $this->apiCacheAnnotation?->getStrategy();
    }

    public function getLifetime(): int
    {
        return $this->apiCacheAnnotation->getLifetime() ?? 86400;
    }
}
