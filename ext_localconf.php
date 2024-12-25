<?php

use Xima\T3ApiCache\Hooks\TceMain;
use Xima\T3ApiCache\Serializer\Subscriber\CacheTagSubscriber;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['t3api_cache'] ??= [];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']['t3api_cache'] = TceMain::class . '->clearCachePostProc';

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3api']['serializerSubscribers'][] = CacheTagSubscriber::class;
