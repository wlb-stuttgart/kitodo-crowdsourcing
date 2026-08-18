<?php

// SPDX-FileCopyrightText: 2026 Württembergische Landesbibliothek
//
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Wlb\Crowdsourcing\Services;

use Wlb\Crowdsourcing\Domain\Model\MetadataConfiguration;
use Wlb\Crowdsourcing\Domain\Repository\MetadataConfigurationRepository;

/**
 * Parses the facet syntax of the metadata configuration.
 *
 *   '$firstname $lastname'          one facet, values joined per repetition: "Ada Lovelace"
 *   '$this'                         all child fields of a group, or the field itself if it has none
 *   '$firstname $lastname###$role'  '###' splits one field into several facets
 *
 * Every facet becomes a solr field named '<metadataKey>_<facetIndex>_faceting'.
 */
class FacetConfigurationService
{
    private const FACET_SEPARATOR = '###';

    private const FIELD_SEPARATOR = '$';

    private const SELF_REFERENCE = 'this';

    /** Input types whose values are option keys instead of free text */
    private const OPTION_INPUT_TYPES = ['select', 'checkbox'];

    /** @var array|null Facet field name => field holding the option labels, or null */
    private $optionSourceFieldMap;

    public function __construct(
        private readonly MetadataConfigurationRepository $metadataConfigurationRepository
    )
    {
    }

    /**
     * The configuration is passed in instead of being loaded here, so that callers keep control
     * over the query settings of the repository (see SolrIndexer::applyQuerySettings()).
     *
     * @param string $metadataKey
     * @param array $metadata
     * @return array Facet field name => ['metadataKey' => string, 'hasChildren' => bool, 'sourceFields' => array]
     */
    public function getFacetDefinitionsForField(string $metadataKey, array $metadata): array
    {
        if (empty($metadata['facet'])) {
            return [];
        }

        $hasChildren = isset($metadata['children']) && is_array($metadata['children']);
        $childKeys = $hasChildren ? array_keys($metadata['children']) : [];

        $definitions = [];
        $facetIndex = 0;

        foreach (explode(self::FACET_SEPARATOR, $metadata['facet']) as $facet) {
            $sourceFields = [];

            foreach (explode(self::FIELD_SEPARATOR, $facet) as $field) {
                $field = trim($field);
                if ($field === '') {
                    continue;
                }

                if ($field === self::SELF_REFERENCE) {
                    foreach ($hasChildren ? $childKeys : [$metadataKey] as $sourceField) {
                        $sourceFields[$sourceField] = true;
                    }
                } else {
                    $sourceFields[$field] = true;
                }
            }

            // Empty segments still consume an index, to stay in sync with SearchService::getFacetFields()
            $facetField = $metadataKey . '_' . $facetIndex . '_faceting';
            $facetIndex++;

            if (empty($sourceFields)) {
                continue;
            }

            $definitions[$facetField] = [
                'metadataKey' => $metadataKey,
                'hasChildren' => $hasChildren,
                'sourceFields' => $sourceFields,
            ];
        }

        return $definitions;
    }

    /**
     * @param array $documentTypeConfig
     * @return array
     */
    private function getFacetDefinitions(array $documentTypeConfig): array
    {
        $definitions = [];

        foreach ($documentTypeConfig as $metadataKey => $metadata) {
            $definitions += $this->getFacetDefinitionsForField($metadataKey, $metadata);
        }

        return $definitions;
    }

    /**
     * Decisive is the number of source fields, not whether the field has children: a value composed
     * of several fields is part of no option list, while a facet built from a single field holds
     * nothing but that field's values.
     *
     * @param string $facetField
     * @return string|null
     */
    public function getOptionSourceField(string $facetField): ?string
    {
        if (!isset($this->optionSourceFieldMap)) {
            $this->optionSourceFieldMap = $this->buildOptionSourceFieldMap();
        }

        return $this->optionSourceFieldMap[$facetField] ?? null;
    }

    /**
     * @return array
     */
    private function buildOptionSourceFieldMap(): array
    {
        $queryResult = $this->metadataConfigurationRepository->findAll();
        if ($queryResult->count() === 0) {
            return [];
        }

        /** @var MetadataConfiguration $dbConfiguration */
        $dbConfiguration = $queryResult->getFirst();
        $dbConfigArray = json_decode($dbConfiguration->getJson(), true);

        $map = [];
        foreach ($dbConfigArray as $documentTypeConfig) {
            foreach ($this->getFacetDefinitions($documentTypeConfig) as $facetField => $definition) {
                $map[$facetField] = $this->resolveOptionSourceField($documentTypeConfig, $definition);
            }
        }

        return $map;
    }

    /**
     * @param array $documentTypeConfig
     * @param array $definition
     * @return string|null
     */
    private function resolveOptionSourceField(array $documentTypeConfig, array $definition): ?string
    {
        if (count($definition['sourceFields']) !== 1) {
            return null;
        }

        $sourceField = array_key_first($definition['sourceFields']);
        $metadataKey = $definition['metadataKey'];

        $fieldConfig = $definition['hasChildren']
            ? ($documentTypeConfig[$metadataKey]['children'][$sourceField] ?? null)
            : ($documentTypeConfig[$sourceField] ?? null);

        if (!is_array($fieldConfig)) {
            return null;
        }

        return in_array($fieldConfig['inputtype'] ?? '', self::OPTION_INPUT_TYPES, true)
            ? $sourceField
            : null;
    }
}
