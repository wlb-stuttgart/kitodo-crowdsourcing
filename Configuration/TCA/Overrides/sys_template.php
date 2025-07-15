<?php

defined('TYPO3') || die('Access denied.');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('crowdsourcing', 'Configuration/TypoScript/Frontend', 'Crowdsourcing Frontend');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('crowdsourcing', 'Configuration/TypoScript/Backend', 'Crowdsourcing Backend');
