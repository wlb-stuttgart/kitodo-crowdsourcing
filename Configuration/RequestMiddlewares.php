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
        'ensure-session-cookies' => [
            'target' => \Wlb\Crowdsourcing\Middleware\SessionInitMiddleware::class,
            'before' => [
                'typo3/cms-frontend/base',
            ],
        ],
    ],
];
