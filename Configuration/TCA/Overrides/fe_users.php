<?php

$temporaryColumns = array(
    'extending' => array(
        'exclude' => 1,
        'label' => 'extending',
        'config' => array(
            'type' => 'input',
            'readOnly' => FALSE,
        )
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', $temporaryColumns, 1);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users', 'extending');

