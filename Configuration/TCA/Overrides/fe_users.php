<?php

$temporaryColumns = array(
    'consent_publish_username_stats' => array(
        'exclude' => 1,
        'label' => 'Consent to publish username for stats',
        'config' => array(
            'type' => 'check',
            'default' => 0,
            'readOnly' => TRUE,
        )
    ),
    'consent_publish_username_edits' => array(
        'exclude' => 1,
        'label' => 'Consent to publish username for edits',
        'config' => array(
            'type' => 'check',
            'default' => 0,
            'readOnly' => TRUE,
        )
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', $temporaryColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users', 'consent_publish_username_stats');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users', 'consent_publish_username_edits');

