<?php

namespace Wlb\Crowdsourcing\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class FacetArgumentViewHelper extends AbstractViewHelper
{
    /**
     * Register arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('facetField', 'string', '', false, '');
        $this->registerArgument('facetValue', 'string', '', false, '');

        $this->registerArgument('activeFacets', 'array', '', false, '');
    }

    /**
     *
     * @return array
     */
    public function render() {
        $result = [];

        $facetField = $this->arguments['facetField'];
        $facetValue = $this->arguments['facetValue'];
        $activeFacets = $this->arguments['activeFacets'];

        // Facetfield already active, remove it
        if (is_array($activeFacets) && array_key_exists($facetField, $activeFacets)) {
            unset($activeFacets[$facetField]);
            if (!empty($activeFacets)) {
                $result['facet'] = $activeFacets;
            }
        } else if (!empty($activeFacets)) {
            $mergedArray = array_merge($activeFacets, [$facetField => [$facetValue => 1]]);
            $result['facet'] = $mergedArray;
        } else {
            $result['facet'] = [$facetField => [$facetValue => 1]];
        }

        return $result;
    }
}