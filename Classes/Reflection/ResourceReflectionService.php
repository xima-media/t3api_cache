<?php

namespace Xima\T3ApiCache\Reflection;

use Doctrine\Common\Annotations\AnnotationReader;
use phpDocumentor\Reflection\Types\ClassString;
use Psr\Http\Message\ServerRequestInterface;
use SourceBroker\T3api\Domain\Model\ApiResource;
use SourceBroker\T3api\Domain\Repository\ApiResourceRepository;
use SourceBroker\T3api\Routing\Enhancer\ResourceEnhancer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use Xima\T3ApiCache\Annotation\ApiCache;
use Xima\T3ApiCache\Annotation\ApiCacheRoundDatetime;

class ResourceReflectionService
{
    protected ?ApiCache $apiCacheAnnotation = null;

    protected ?ApiCacheRoundDatetime $apiCacheRoundDatetimeAnnotation = null;

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
            }
            if ($annotation instanceof ApiCacheRoundDatetime) {
                $this->apiCacheRoundDatetimeAnnotation = $annotation;
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

    public function getCacheKey(): string
    {
        return $this->cacheKey;
    }

    protected function setCacheKey(): void
    {
        if (!$this->apiCacheAnnotation) {
            return;
        }

        $parametersToIgnore = $this->apiCacheAnnotation->getParametersToIgnore();
        if (in_array('*', $parametersToIgnore, true)) {
            return;
        }

        $validRequestParams = array_filter($this->request->getQueryParams());
        unset($validRequestParams[ResourceEnhancer::PARAMETER_NAME]);
        if (count(array_intersect(array_keys($validRequestParams), $parametersToIgnore)) > 0) {
            return;
        }

        $allowedParameters = [$this->apiResource->getPagination()->getPageParameterName()];
        foreach ($this->apiResource->getMainCollectionOperation()?->getFilters() ?? [] as $filter) {
            $allowedParameters[] = $filter->getParameterName();
        }
        $allowedParameters = array_unique($allowedParameters);

        if (count(array_diff(array_keys($validRequestParams), $allowedParameters)) > 0) {
            return;
        }

        $validRequestParams = $this->roundDatetimeParameters($validRequestParams);

        $this->cacheKey = md5($this->request->getUri()->getPath() . '?' . http_build_query($validRequestParams));
    }

    /**
     * Round datetime parameters according to the @ApiCacheRoundDatetime annotation.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    protected function roundDatetimeParameters(array $params): array
    {
        if (!$this->apiCacheRoundDatetimeAnnotation) {
            return $params;
        }

        $direction = $this->apiCacheRoundDatetimeAnnotation->getDirection();
        foreach ($this->apiCacheRoundDatetimeAnnotation->getParameters() as $parameterName => $precision) {
            if (isset($params[$parameterName]) && is_string($params[$parameterName])) {
                $params[$parameterName] = ApiCacheRoundDatetime::roundDatetime(
                    $params[$parameterName],
                    $precision,
                    $direction
                );
            }
        }

        return $params;
    }

    public function getTableName(): string
    {
        $dataMapper = GeneralUtility::makeInstance(DataMapper::class);
        return $dataMapper->getDataMap($this->apiResource->getEntity())->getTableName();
    }
}
