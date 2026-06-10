<?php

// SPDX-FileCopyrightText: 2026 Württembergische Landesbibliothek
//
// SPDX-License-Identifier: GPL-3.0-or-later

defined('TYPO3') or die();

return [
    'ctrl' => [
        'title' => 'LLL:EXT:crowdsourcing/Resources/Private/Language/locallang_db.xlf:tx_crowdsourcing_domain_model_clickstatistic',
        'label' => 'action_type',
        'label_alt' => 'action_identifier',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'sortby' => 'crdate',
        'delete' => 'deleted',
        'enablecolumns' => [],
        'searchFields' => 'action_type,action_identifier,uri',
        'iconfile' => 'EXT:crowdsourcing/Resources/Public/Icons/tx_crowdsourcing_domain_model_clickstatistic.gif'
    ],
    'types' => [
        '1' => ['showitem' => 'action_type,action_identifier,uri,user_agent,fe_user_uid,process_uid,process_state,campaign_uid,session_id,additional_data'],
    ],
    'columns' => [
        'user_agent' => [
            'exclude' => true,
            'label' => 'User Agent',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 3,
            ],
        ],
        'fe_user_uid' => [
            'exclude' => true,
            'label' => 'Frontend User',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int'
            ],
        ],
        'action_type' => [
            'exclude' => true,
            'label' => 'Action Type',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required'
            ],
        ],
        'action_identifier' => [
            'exclude' => true,
            'label' => 'Action Identifier',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required'
            ],
        ],
        'uri' => [
            'exclude' => true,
            'label' => 'URI',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 3,
            ],
        ],
        'referrer' => [
            'exclude' => true,
            'label' => 'Referrer',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 3,
            ],
        ],
        'process_uid' => [
            'exclude' => true,
            'label' => 'Process',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int'
            ],
        ],
        'process_state' => [
            'exclude' => true,
            'label' => 'Process State',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'campaign_uid' => [
            'exclude' => true,
            'label' => 'Campaign',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int'
            ],
        ],
        'session_id' => [
            'exclude' => true,
            'label' => 'Session ID',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'additional_data' => [
            'exclude' => true,
            'label' => 'Additional Data',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 5,
            ],
        ],
    ],
];