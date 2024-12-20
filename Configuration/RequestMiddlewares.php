<?php

return [
    'frontend' => [
        'xima/t3api-cache' => [
            'target' => \Xima\T3ApiCache\Middleware\T3ApiCache::class,
            'before' => [
                'sourcebroker/t3api/process-api-request',
            ],
            'after' => [
                'sourcebroker/t3api/prepare-api-request',
            ],
        ],
    ],
];
