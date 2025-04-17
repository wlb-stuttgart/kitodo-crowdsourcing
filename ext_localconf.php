<?php

declare(strict_types=1);

defined('TYPO3') or die();

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

// TODO: In contrast to the integration via Overrides/pages.php,
// this automatic integration works, i.e., the configuration takes effect
// and the plugin is directly listed in the plugin list.
// However, it is not clear if this method is actually only intended for site packages
// and might cause problems otherwise.
/*
$versionInformation = GeneralUtility::makeInstance(Typo3Version::class);
if ($versionInformation->getMajorVersion() < 12) {
    ExtensionManagementUtility::addPageTSConfig('
      @import "EXT:crowdsourcing/Configuration/page.tsconfig"
   ');
}
*/

ExtensionUtility::configurePlugin(
    'Crowdsourcing',
    'Campaigns',
    [\Wlb\Crowdsourcing\Controller\WorkflowController::class => 'index,listCampaigns,showCampaignDetails, listProcesses, editMetadata, saveForm'],
    [\Wlb\Crowdsourcing\Controller\WorkflowController::class => 'index,listCampaigns,showCampaignDetails, listProcesses, editMetadata, saveForm']
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig(
    '@import \'EXT:crowdsourcing/Configuration/TypoScript/sfregister.typoscript\''
);
