<?php

namespace Wlb\Crowdsourcing\Common;

class XMLExtractor
{
    private const DEFAULT_DELIMITER = ' ';

    /**
     * Extract the data recursively based on the index config
     *
     * @param $config
     * @param $xml
     * @param $parent
     * @return array
     */
    public function extractData($config, $xml, $parent = null)
    {
        $data = [];
        foreach ($config['_fields'] as $field => $fieldConfig) {
            if (is_array($fieldConfig)) { // Handle subgroup
                $fieldGroups = $parent
                    ? $parent->xpath("kitodo:metadataGroup[@name='$field']")
                    : $xml->xpath("//kitodo:metadataGroup[@name='$field']");

                foreach ($fieldGroups as $group) {
                    $nestedData = $this->extractData($fieldConfig, $xml, $group);
                    if (!empty($nestedData)) {
                        // Add the nested data to the current group
                        $test = trim(
                            implode(
                                $this->getDelimiter($fieldConfig),
                                $nestedData
                            ),
                            $this->getDelimiter($fieldConfig)
                        );
                        if (!empty($test)) {
                            $data[] = $test;
                        }
                    }
                }
            } else { // Handle simple fields
                $values = $parent
                    ? $parent->xpath("kitodo:metadata[@name='$field']")
                    : $xml->xpath("//kitodo:metadata[@name='$field']");

                $fieldValues = [];
                foreach ($values as $value) {
                    $fieldValues[] = (string)$value;
                }

                if (!empty($fieldValues)) {
                    $test = implode($this->getDelimiter($config), $fieldValues);
                    if (!empty($test)) {
                        $data[] = $test;
                    }
                }
            }
        }
        return $data;
    }


    /**
     * Reads the delimiter from the given configuration
     *
     * @param array $config
     * @return void
     */
    private function getDelimiter($config)
    {
        if (!isset($config['_delimiter']) || empty($config['_delimiter'])) {
            return self::DEFAULT_DELIMITER;
        }

        return $config['_delimiter'];
    }
}
