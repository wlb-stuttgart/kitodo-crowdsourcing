<?php

declare(strict_types=1);

defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep(
    \TYPO3\CMS\Core\Core\Environment::getPublicPath(). '/fileadmin/uploads/tx_crowdsourcing'
);