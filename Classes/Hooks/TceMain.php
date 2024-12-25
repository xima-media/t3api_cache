<?php

namespace Xima\T3ApiCache\Hooks;

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

readonly class TceMain
{
    public function __construct(private FrontendInterface $cache)
    {
    }

    /**
     * @param array{tags: array<string, array<int, int>>} $params
     */
    public function clearCachePostProc(
        array $params
    ): void {
        $this->cache->flushByTags(array_keys($params['tags']));
    }
}
