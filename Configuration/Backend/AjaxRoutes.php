<?php

// SPDX-FileCopyrightText: 2026 Württembergische Landesbibliothek
//
// SPDX-License-Identifier: GPL-3.0-or-later

use Wlb\Crowdsourcing\Controller\Backend\StatisticsController;

return [
    'crowdsourcing_statistics_get_active_users' => [
        'path' => '/crowdsourcing/statistics/get-active-users',
        'target' => StatisticsController::class . '::getActiveUsersDataAction',
    ],
    'crowdsourcing_statistics_get_page_views' => [
        'path' => '/crowdsourcing/statistics/get-page-views',
        'target' => StatisticsController::class . '::getPageViewsDataAction',
    ]
];
