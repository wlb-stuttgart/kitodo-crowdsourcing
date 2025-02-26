<?php

namespace Wlb\Crowdsourcing\Services;

use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Wlb\Crowdsourcing\Common\IndexFields;

class IndexFieldsService
{
    /**
     * Loads the index field list from the configuration file.
     *
     * @return IndexFields
     */
    public function load()
    {
        $configFile = GeneralUtility::getFileAbsFileName('EXT:crowdsourcing/Configuration/YAML/IndexFields.yaml');

        if (!file_exists($configFile)) {
            throw new \RuntimeException("YAML configuration file not found: " . $configFile);
        }

        try {
            $configData = Yaml::parseFile($configFile);
            return new IndexFields($configData['fields']);
        } catch (\Throwable $e) {
            throw new \RuntimeException("Error parsing YAML file: " . $configFile . " - " . $e->getMessage());
        }
    }
}
