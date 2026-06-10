<?php

// SPDX-FileCopyrightText: 2026 Württembergische Landesbibliothek
//
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Wlb\Crowdsourcing\Services;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class IndexFieldConfigReader
{
    private $config;

    public function __construct(string $yamlFile = "")
    {
        if (!$yamlFile) {
            $yamlFile = GeneralUtility::getFileAbsFileName('EXT:crowdsourcing/Configuration/YAML/IndexFields.yaml');
        }

        $this->loadConfig($yamlFile);
    }

    private function loadConfig(string $yamlFile)
    {
        if (!file_exists($yamlFile)) {
            throw new \RuntimeException("YAML configuration file not found: " . $yamlFile);
        }

        try {
            $this->config = Yaml::parseFile($yamlFile);
        } catch (ParseException $exception) {
            // TODO Log "Error parsing YAML file: " . $exception->getMessage();
            $this->config = [];
        }
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
