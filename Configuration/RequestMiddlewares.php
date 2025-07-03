<?php

return [
    'frontend' => [
        'vendor-myextension/log-page-hit' => [
            'target' => \Wlb\Crowdsourcing\Middleware\LogPageHitMiddleware::class,
            'after' => [
                'typo3/cms-frontend/authentication',
            ],
            'before' => [
                'typo3/cms-frontend/output-compression',
            ],
        ],
    ],
];