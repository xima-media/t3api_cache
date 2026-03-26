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
use SourceBroker\T3api\Annotation\Serializer\SerializedName;
use SourceBroker\T3api\Annotation\Serializer\VirtualProperty;
use Xima\T3ApiCache\Annotation\ApiCache;
use Xima\T3ApiCache\Annotation\ApiCacheRoundDatetime;

class ResourceReflectionService
{
    protected ?ApiCache $apiCacheAnnotation = null;

    /**
     * @var ApiCacheRoundDatetime[]
     */
    protected array $apiCacheRoundDatetimeAnnotations = [];

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
        $reflectionClass = new \ReflectionClass($entityClass);

        $annotations = $annotationReader->getClassAnnotations($reflectionClass);
        foreach ($annotations as $annotation) {
            if ($annotation instanceof ApiCache) {
                $this->apiCacheAnnotation = $annotation;
            }
        }

        // Collect @ApiCacheRoundDatetime annotations from properties and methods
        foreach ($reflectionClass->getProperties() as $property) {
            $annotation = $annotationReader->getPropertyAnnotation($property, ApiCacheRoundDatetime::class);
            if ($annotation instanceof ApiCacheRoundDatetime) {
                $parameterName = $annotation->getParameterName()
                    ?? $this->getSerializedName($annotationReader, property: $property)
                    ?? $property->getName();
                $this->apiCacheRoundDatetimeAnnotations[$parameterName] = $annotation;
            }
        }
        foreach ($reflectionClass->getMethods() as $method) {
            $annotation = $annotationReader->getMethodAnnotation($method, ApiCacheRoundDatetime::class);
            if ($annotation instanceof ApiCacheRoundDatetime) {
                $parameterName = $annotation->getParameterName()
                    ?? $this->getSerializedName($annotationReader, method: $method)
                    ?? $this->getVirtualPropertyName($annotationReader, $method)
                    ?? $method->getName();
                $this->apiCacheRoundDatetimeAnnotations[$parameterName] = $annotation;
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
     * Round datetime parameters according to @ApiCacheRoundDatetime annotations on properties/methods.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    protected function roundDatetimeParameters(array $params): array
    {
        if (empty($this->apiCacheRoundDatetimeAnnotations)) {
            return $params;
        }

        foreach ($this->apiCacheRoundDatetimeAnnotations as $parameterName => $annotation) {
            if (isset($params[$parameterName]) && is_string($params[$parameterName])) {
                $params[$parameterName] = ApiCacheRoundDatetime::roundDatetime(
                    $params[$parameterName],
                    $annotation->getPrecision(),
                    $annotation->getDirection()
                );
            }
        }

        return $params;
    }

    /**
     * Get the serialized name from a @SerializedName annotation on a property or method.
     */
    private function getSerializedName(
        AnnotationReader $annotationReader,
        ?\ReflectionProperty $property = null,
        ?\ReflectionMethod $method = null
    ): ?string {
        $annotation = null;
        if ($property !== null) {
            $annotation = $annotationReader->getPropertyAnnotation($property, SerializedName::class);
        } elseif ($method !== null) {
            $annotation = $annotationReader->getMethodAnnotation($method, SerializedName::class);
        }

        if ($annotation instanceof SerializedName && !empty($annotation->name)) {
            return $annotation->name;
        }

        return null;
    }

    /**
     * Get the virtual property name from a @VirtualProperty annotation on a method.
     */
    private function getVirtualPropertyName(AnnotationReader $annotationReader, \ReflectionMethod $method): ?string
    {
        $annotation = $annotationReader->getMethodAnnotation($method, VirtualProperty::class);
        if ($annotation instanceof VirtualProperty && !empty($annotation->name)) {
            return $annotation->name;
        }

        return null;
    }

    public function getTableName(): string
    {
        $dataMapper = GeneralUtility::makeInstance(DataMapper::class);
        return $dataMapper->getDataMap($this->apiResource->getEntity())->getTableName();
    }
}
