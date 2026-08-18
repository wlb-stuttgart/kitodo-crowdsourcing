<?php

// SPDX-FileCopyrightText: 2026 Württembergische Landesbibliothek
//
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Wlb\Crowdsourcing\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use Wlb\Crowdsourcing\Services\FacetConfigurationService;
use Wlb\Crowdsourcing\Services\RulesetService;

/**
 * Resolves a facet value to the label of its option list,
 * if it is part of an option list and is based on a select or checkbox field.
 */
class FacetValueLabelViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = true;

    public function __construct(
        private readonly FacetConfigurationService $facetConfigurationService,
        private readonly RulesetService $rulesetService
    ) {}

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('facetField', 'string', 'Name of the facet field', true);
        $this->registerArgument('value', 'string', 'Facet value to be resolved', true);
    }

    private static array $rulesetDefinitions = [];

    public function render(): string
    {
        $facetField = (string)$this->arguments['facetField'];
        $value = (string)$this->arguments['value'];

        $sourceField = $this->facetConfigurationService->getOptionSourceField($facetField);
        if ($sourceField === null) {
            return $value;
        }

        if (empty(self::$rulesetDefinitions)) {
            self::$rulesetDefinitions = $this->rulesetService->getRulesetDefinitions();
        }

        return self::$rulesetDefinitions[$sourceField]['options'][$value] ?? $value;
    }
}
