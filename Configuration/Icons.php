<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    // Icon identifier
    'module-crowdsourcing' => [
        // Icon provider class
        'provider' => SvgIconProvider::class,
        // The source SVG for the SvgIconProvider
        'source' => 'EXT:crowdsourcing/Resources/Public/Icons/users-rectangle-solid.svg',
    ],
    // Icon identifier
    'module-crowdsourcing-campaign' => [
        // Icon provider class
        'provider' => SvgIconProvider::class,
        // The source SVG for the SvgIconProvider
        'source' => 'EXT:crowdsourcing/Resources/Public/Icons/sitemap-solid.svg',
    ]

];
