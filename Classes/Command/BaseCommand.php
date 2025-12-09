<?php

namespace Wlb\Crowdsourcing\Command;

use Symfony\Component\Console\Command\Command;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use Wlb\Crowdsourcing\Services\ExtensionConfigurationService;

abstract class BaseCommand extends Command
{
    /**
     * @return int
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     */
    public function getStoragePid() {
        $storagePid = ExtensionConfigurationService::getInstance()->getConfigurationValue('storagePid');

        if (filter_var($storagePid, FILTER_VALIDATE_INT) !== false && (int)$storagePid >= 0) {
            return (int)$storagePid;
        }

        return 0;
    }

    /**
     * @param int $storagePid
     * @return Typo3QuerySettings
     */
    public function getQuerySettings(int $storagePid) {
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(true);
        $querySettings->setStoragePageIds([$storagePid]);

        return $querySettings;
    }
}