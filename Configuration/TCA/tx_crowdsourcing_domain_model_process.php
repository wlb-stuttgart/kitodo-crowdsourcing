<?php

$tca = [
    'ctrl' => [
        'title' => 'LLL:EXT:crowdsourcing/Resources/Private/Language/locallang_db.xlf:tx_crowdsourcing_domain_model_process',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'title',
        'iconfile' => 'EXT:crowdsourcing/Resources/Public/Icons/crowdsourcing.gif',
        'searchFields' => 'label, description',
        'enablecolumns' => [
            'fe_group' => 'fe_group',
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'languageField' => 'sys_language_uid',
        'translationSource' => 'l10n_source',
    ],
    'types' => [
        '1' => [
            'showitem' =>
                '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    title, identifier, images, state, type, metadata, campaign,
                 --div--;LLL:EXT:crowdsourcing/Resources/Private/Language/locallang_db.xlf:tx_crowdsourcing_domain_model_process.tabs.access,
                    --palette--;;hidden,
                    --palette--;;access,',
        ],
    ],
    'palettes' => [
        'hidden' => [
            'showitem' => '
                hidden;LLL:EXT:crowdsourcing/Resources/Private/Language/locallang_db.xlf:tx_crowdsourcing_domain_model_process.hidden
            ',
        ],
        'access' => [
            'label' => 'LLL:EXT:crowdsourcing/Resources/Private/Language/locallang_db.xlf:tx_crowdsourcing_domain_model_process.palettes.access',
            'showitem' => '
                starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:starttime_formlabel,
                endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:endtime_formlabel,
                --linebreak--,
                fe_group;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:fe_group_formlabel,
            ',
        ],
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:crowdsourcing/Resources/Private/Language/locallang_db.xlf:tx_crowdsourcing_domain_model_process.hidden',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038),
                ],
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038),
                ],
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
        ],
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l18n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => '',
                        'value' => 0,
                    ],
                ],
                'foreign_table' => 'tx_crowdsourcing_domain_model_process',
                'foreign_table_where' =>
                    'AND {#tx_crowdsourcing_domain_model_process}.{#pid}=###CURRENT_PID###
                     AND {#tx_crowdsourcing_domain_model_process}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_source' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'l18n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],
        'title' => [
            'label' => 'LLL:EXT:crowdsourcing/Resources/Private/Language/locallang_db.xlf:tx_crowdsourcing_domain_model_process.title',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'identifier' => [
            'label' => 'LLL:EXT:crowdsourcing/Resources/Private/Language/locallang_db.xlf:tx_crowdsourcing_domain_model_process.identifier',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'int'
            ],
        ],
        'images' => [
            'label' => 'LLL:EXT:crowdsourcing/Resources/Private/Language/locallang_db.xlf:tx_crowdsourcing_domain_model_process.images',
            'config' => [
                'type' => 'text',
                'enableRichtext' => false,
                'rows' => 8,
                'cols' => 40,
                'max' => 2000,
                'eval' => 'trim',
            ],
        ],
        'state' => [
            'label' => 'LLL:EXT:crowdsourcing/Resources/Private/Language/locallang_db.xlf:tx_crowdsourcing_domain_model_process.state',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'type' => [
            'label' => 'LLL:EXT:crowdsourcing/Resources/Private/Language/locallang_db.xlf:tx_crowdsourcing_domain_model_process.type',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 255,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'metadata' => [
            'label' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang_db.xlf:tx_crowdsourcing_domain_model_process.metadata',
            'config' => [
                'type' => 'text',
                'enableRichtext' => false,
                'rows' => 8,
                'cols' => 40,
                'eval' => 'trim',
            ],
        ],
        'fe_group' => [
            'exclude' => true,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:crowdsourcing/Resources/Private/Language/locallang_db.xlf:tx_crowdsourcing_domain_model_process.fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 7,
                'maxitems' => 20,
                'items' => [
                    [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hide_at_login',
                        'value' => -1,
                    ],
                    [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.any_login',
                        'value' => -2,
                    ],
                    [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.usergroups',
                        'value' => '--div--',
                    ],
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
            ],
        ],
        'campaign' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:crowdsourcing/Resources/Private/Language/locallang_db.xlf:tx_crowdsourcing_domain_model_process.campaign',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_crowdsourcing_domain_model_campaign',
                'foreign_table_where' => 'AND tx_crowdsourcing_domain_model_campaign.pid = ###CURRENT_PID### AND tx_crowdsourcing_domain_model_campaign.deleted = 0 ORDER BY tx_crowdsourcing_domain_model_campaign.title',
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
    ],
];

$typo3Version = new \TYPO3\CMS\Core\Information\Typo3Version();
if ($typo3Version->getMajorVersion() < 12) {
    $tca = array_replace_recursive(
        $tca,
        [
            'ctrl' => [
                'cruser_id' => 'cruser_id',
            ],
            'columns' => [
                'title' => [
                    'config' => [
                        'eval' => 'trim,required',
                    ],
                ],
            ],
        ]
    );
    unset($tca['columns']['title']['required']);

    $tca['columns']['l18n_parent']['config']['items'] = [
        [
            0 => '',
            1 => 0,
        ],
    ];
    $tca['columns']['hidden']['config'] = [
        'type' => 'check',
        'label' => 'LLL:EXT:crowdsourcing/Resources/Private/Language/locallang_db.xlf:tx_crowdsourcing_domain_model_process.hidden',
        'items' => [
            [
                0 => '',
                'invertStateDisplay' => true,
            ],
        ],
    ];
    $tca['columns']['starttime']['config'] = [
        'type' => 'input',
        'renderType' => 'inputDateTime',
        'eval' => 'datetime,int',
        'default' => 0,
    ];
    $tca['columns']['endtime']['config'] = [
        'type' => 'input',
        'renderType' => 'inputDateTime',
        'eval' => 'datetime,int',
        'default' => 0,
    ];
    $tca['columns']['fe_group']['config']['items'] = [
        [
            0 => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hide_at_login',
            1 => -1,
        ],
        [
            0 => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.any_login',
            1 => -2,
        ],
        [
            0 => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.usergroups',
            1 => '--div--',
        ],
    ];
}

return $tca;
