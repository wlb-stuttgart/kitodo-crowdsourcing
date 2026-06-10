<?php

// SPDX-FileCopyrightText: 2026 Württembergische Landesbibliothek
//
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

namespace Wlb\Crowdsourcing\Services;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ExtensionConfigurationService
{

    /**
     * @var ExtensionConfiguration|null
     */
    private static ?ExtensionConfigurationService $instance = null;

    /**
     * @var ExtensionConfiguration
     */
    private ExtensionConfiguration $extensionConfiguration;


    public static function getInstance(): ExtensionConfigurationService
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        }

        return self::$instance;
    }

    /**
     * Gets the whole extension configuration
     *
     * @return mixed
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     */
    public function getConfiguration()
    {
        return $this->extensionConfiguration->get('crowdsourcing');
    }

    /**
     * Gets a value for the given configuration variable name
     *
     * @param string $name
     * @return mixed
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     */
    public function getConfigurationValue($name)
    {
        return $this->extensionConfiguration->get('crowdsourcing', $name);
    }
}
