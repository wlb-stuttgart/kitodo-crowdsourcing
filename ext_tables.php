<?php

declare(strict_types=1);

use Wlb\Crowdsourcing\Controller\Backend\CampaignController;
use Wlb\Crowdsourcing\Controller\Backend\ConfigurationController;

defined('TYPO3') or die();

/*
// Module System > Backend Users
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'Crowdsourcing',
    'tx_crowdsourcing',
    '',
    '',
    [],
    [
        'access' => 'admin',
        'iconIdentifier' => 'module-crowdsourcing',
        'labels' => 'LLL:EXT:crowdsourcing/Resources/Private/Language/locallang_mod_main.xlf',
        'navigationComponentId' => 'TYPO3/CMS/Backend/PageTree/PageTreeElement',
        'inheritNavigationComponentFromMainModule' => false,
    ]
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'Crowdsourcing',
    'tx_crowdsourcing',
    'tx_crowdsourcing_campaign',
    '',
    [
        CampaignController::class =>
            'list, new, create, edit, update, editProcesses, listProcesses, '
            . 'addProcessToCampaign, removeProcessFromCampaign, publish, close, reopen'
    ],
    [
        'access' => 'user,group',
        'iconIdentifier' => 'module-crowdsourcing-campaign',
        'labels' => 'LLL:EXT:crowdsourcing/Resources/Private/Language/locallang_mod_campaign.xlf'
    ]
);

// Configuration
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'Crowdsourcing',
    'tx_crowdsourcing',
    'tx_crowdsourcing_configuration',
    '',
    [
        ConfigurationController::class => 'index, save, saveDemoForm'
    ],
    [
        'access' => 'user,group',
        'iconIdentifier' => 'module-crowdsourcing-campaign',
        'labels' => 'LLL:EXT:crowdsourcing/Resources/Private/Language/locallang_mod_configuration.xlf'
    ]
);
*/

\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep(
    \TYPO3\CMS\Core\Core\Environment::getPublicPath(). '/fileadmin/uploads/tx_crowdsourcing'
);