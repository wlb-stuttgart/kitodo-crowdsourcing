<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */


use Wlb\Crowdsourcing\Controller\Backend\CampaignController;
use Wlb\Crowdsourcing\Controller\Backend\ConfigurationController;

/**
 * Definitions for modules provided by EXT:crowdsourcing
 */

return [
    'tx_crowdsourcing' => [
        'parent' => null,
        'position' => [],
        'access' => 'admin',
        'workspaces' => 'live',
        //'path' => '/module/page/example',
        'labels' => 'LLL:EXT:crowdsourcing/Resources/Private/Language/locallang_mod_main.xlf',
        'extensionName' => 'Crowdsourcing',
        'iconIdentifier' => 'module-crowdsourcing',
        //'inheritNavigationComponentFromMainModule' => false,
        'navigationComponentId' => ''
    ],
    'tx_crowdsourcing_campaign' => [
        'parent' => 'tx_crowdsourcing',
        'position' => ['after' => 'web_info'],
        'access' => 'user,group ',
        'workspaces' => 'live',
        //'path' => '/module/page/example',
        'labels' => 'LLL:EXT:crowdsourcing/Resources/Private/Language/locallang_mod_campaign.xlf',
        'extensionName' => 'Crowdsourcing',
        'iconIdentifier' => 'module-crowdsourcing-campaign',
        'controllerActions' => [
            CampaignController::class =>
                'list, new, create, edit, update, editProcesses, listProcesses, '
                . 'addProcessToCampaign, removeProcessFromCampaign, publish, close, reopen, delete'
        ]
    ],
    'tx_crowdsourcing_configuration' => [
        'parent' => 'tx_crowdsourcing',
        'position' => ['after' => 'tx_crowdsourcing_campaign'],
        'access' => 'user,group ',
        'workspaces' => 'live',
        //'path' => '/module/page/example',
        'labels' => 'LLL:EXT:crowdsourcing/Resources/Private/Language/locallang_mod_configuration.xlf',
        'extensionName' => 'Crowdsourcing',
        'iconIdentifier' => 'module-crowdsourcing-campaign',
        'controllerActions' => [
            ConfigurationController::class => 'index, save, saveDemoForm'
        ],
    ]
];

