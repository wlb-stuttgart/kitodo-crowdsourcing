<?php

// SPDX-FileCopyrightText: 2026 Württembergische Landesbibliothek
//
// SPDX-License-Identifier: GPL-3.0-or-later

return [
    'dependencies' => ['backend'],
    'imports' => [
        'd3' => 'EXT:crowdsourcing/Resources/Public/JavaScript/d3/d3.min.js',
        'chart' => 'EXT:crowdsourcing/Resources/Public/JavaScript/chart/chart.js',
        '@wlb/crowdsourcing/chart.js' => 'EXT:crowdsourcing/Resources/Public/JavaScript/Backend/charts.js'
    ],
];