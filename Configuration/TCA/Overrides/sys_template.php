<?php

// SPDX-FileCopyrightText: 2026 Württembergische Landesbibliothek
//
// SPDX-License-Identifier: GPL-3.0-or-later

defined('TYPO3') || die('Access denied.');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('crowdsourcing', 'Configuration/TypoScript/Frontend', 'Crowdsourcing Frontend');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('crowdsourcing', 'Configuration/TypoScript/Backend', 'Crowdsourcing Backend');
