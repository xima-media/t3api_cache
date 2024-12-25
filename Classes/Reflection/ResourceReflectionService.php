<?php

namespace Xima\T3ApiCache\Reflection;

use Doctrine\Common\Annotations\AnnotationReader;
use phpDocumentor\Reflection\Types\ClassString;
use Psr\Http\Message\ServerRequestInterface;
use SourceBroker\T3api\Domain\Model\ApiResource;
use SourceBroker\T3api\Domain\Repository\ApiResourceRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\T3ApiCache\Annotation\ApiCache;

class ResourceReflectionService
{
    protected ?ApiCache $apiCacheAnnotation = null;

    private ?ApiResource $apiResource = null;

    private string $cacheKey = '';

    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly ApiResourceRepository $apiResourceRepository
    ) {
        $this->setApiResource();
        $this->setApiCacheAnnotation();
        $this->setCacheKey();
    }

    private function setApiCacheAnnotation(): void
    {
        if ($this->apiResource === null) {
            return;
        }

        $annotationReader = GeneralUtility::makeInstance(AnnotationReader::class);
        /** @var ClassString $entityClass */
        $entityClass = $this->apiResource->getEntity();
        $annotations = $annotationReader->getClassAnnotations(new \ReflectionClass($entityClass));
        foreach ($annotations as $annotation) {
            if ($annotation instanceof ApiCache) {
                $this->apiCacheAnnotation = $annotation;
                return;
            }
        }
    }

    public function getApiResource(): ?ApiResource
    {
        return $this->apiResource;
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

    public function getApiCacheAnnotation(): ?ApiCache
    {
        return $this->apiCacheAnnotation;
    }

    public function isCacheable(): bool
    {
        return $this->cacheKey !== '';
    }

    public function getCacheKey(): string
    {
        return $this->cacheKey;
    }

    protected function setCacheKey(): void
    {
        if (!$this->apiCacheAnnotation) {
            return;
        }

        $queryParams = $this->request->getQueryParams();
        $paramsToIgnore = $this->apiCacheAnnotation->getParametersToIgnore();
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
}
