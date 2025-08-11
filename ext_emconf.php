<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'crowdsourcing',
    'description' => 'Extension for the WLB crowdsourcing digitization project',
    'constraints' => [
        'depends' => [
            'php'   => '8.4',
            'typo3' => '13.4.0-13.4.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'Wlb\\Crowdsourcing\\' => 'Classes/',
        ],
    ],
];
