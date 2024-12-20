<?php

namespace Xima\T3ApiCache\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use SourceBroker\T3api\Domain\Repository\ApiResourceRepository;
use SourceBroker\T3api\Service\RouteService;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

class T3ApiCache implements MiddlewareInterface
{

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

        $cacheKey = md5($request->getUri()->getPath() . 'fwef' . http_build_query($request->getQueryParams()));
        if ($this->cache->has($cacheKey)) {
            $data = $this->cache->get($cacheKey);
            $response = $this->responseFactory->createResponse()->withHeader('Content-Type', 'application/json; charset=utf-8');
            $response->getBody()->write($data);
            return $response;
        }

        // @TODO: more checks

        $response = $handler->handle($request);
        $data = (string)$response->getBody();

        $array = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        $tableName = $this->getTableName($request);
        $cacheTags = [$tableName];
        //foreach ($array['hydra:member'] as $key => $value) {
        //    if (isset($value['uid'])) {
        //        $cacheTags[] = $tableName . '_' . $value['uid'];
        //    }
        //}
        $this->cache->set($cacheKey, $data, $cacheTags);


        return $response;
    }

    protected function getTableName(ServerRequestInterface $request): string
    {
        foreach ($this->apiResourceRepository->getAll() as $repo) {
            foreach($repo->getCollectionOperations() as $operation) {
                $route = $operation->getRoute();
                if ($route->getPath() === $request->getUri()->getPath()) {
                    $entity = $repo->getEntity();
                    $dataMapper = GeneralUtility::makeInstance(DataMapper::class);
                    $tableName = $dataMapper->getDataMap($entity)->getTableName();
                    break 2;
                }
            }
        }

        return $tableName ?? '';
    }
}
