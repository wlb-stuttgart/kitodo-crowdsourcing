<?php

// SPDX-FileCopyrightText: 2026 Württembergische Landesbibliothek
//
// SPDX-License-Identifier: GPL-3.0-or-later

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
        'wlb/crowdsourcing/lobid-search' => [
            'target' => \Wlb\Crowdsourcing\Middleware\LobidSearchMiddleware::class,
            'after' => [
                'typo3/cms-frontend/site',
                'typo3/cms-frontend/authentication',
            ],
            'before' => [
                'typo3/cms-frontend/base-redirect-resolver',
            ],
        ],
        'ensure-session-cookies' => [
            'target' => \Wlb\Crowdsourcing\Middleware\SessionInitMiddleware::class,
            'before' => [
                'typo3/cms-frontend/base',
            ],
        ],
    ],
];
