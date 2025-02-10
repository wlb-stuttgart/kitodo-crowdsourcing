<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'crowdsourcing',
    'description' => 'Extension for the WLB crowdsourcing digitization project',
    'constraints' => [
        'depends' => [
            'php'   => '8.3',
            'typo3' => '11.5.0-11.5.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'Wlb\\Crowdsourcing\\' => 'Classes/',
        ],
    ],
];
