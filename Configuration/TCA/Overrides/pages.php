<?php

// SPDX-FileCopyrightText: 2026 Württembergische Landesbibliothek
//
// SPDX-License-Identifier: GPL-3.0-or-later

defined('TYPO3') || die('Access denied.');

use \TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

// TODO: Can be added in the page resources but the config does not take any effect,
// the plugin is not listed in the plugin list in the add content/plugin dialog.
ExtensionManagementUtility::registerPageTSConfigFile(
    'crowdsourcing',
    "Configuration/TsConfig/all.tsconfig",
    'Crowdsourcing TSConfig'
);

// $GLOBALS['TCA']['pages']['columns']['tsconfig_includes']['config']['items'][] = [
//    'LLL:EXT:my_sitepackage/Resources/Private/Language/locallang_db.xlf:pages.pageTSconfig.my_ext_be_layouts',
//    'EXT:my_sitepackage/Configuration/TsConfig/Page/myPageTSconfigFile.tsconfig',
// ];
