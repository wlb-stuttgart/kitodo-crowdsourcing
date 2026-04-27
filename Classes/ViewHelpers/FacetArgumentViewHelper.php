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
        $this->registerArgument('facets', 'array', '', false, []);

        $this->registerArgument('activeFacets', 'array', '', false, []);
    }

    /**
     *
     * @return array
     */
    public function render() {
        $result = [];

        $facetField = $this->arguments['facetField'];
        $facetValue = $this->arguments['facetValue'];
        $facets = $this->arguments['facets'];
        $activeFacets = $this->arguments['activeFacets'];

        if (!is_array($activeFacets)) {
            $activeFacets = [];
        }

        if (!empty($facets)) {
            foreach ($facets as $facet) {
                if (
                    !is_array($facet)
                    || empty($facet['field'])
                    || !array_key_exists('value', $facet)
                ) {
                    continue;
                }

                $field = (string)$facet['field'];
                $value = (string)$facet['value'];

                $activeFacets[$field][$value] = 1;
            }

            if (!empty($activeFacets)) {
                $result['facet'] = $activeFacets;
            }

            return $result;
        }

        // Facetfield already active, remove it
        if (is_array($activeFacets) && array_key_exists($facetField, $activeFacets)
            && array_key_exists($facetValue, $activeFacets[$facetField])) {

            unset($activeFacets[$facetField][$facetValue]);

            if (empty($activeFacets[$facetField])) {
                unset($activeFacets[$facetField]);
            }

            if (!empty($activeFacets)) {
                $result['facet'] = $activeFacets;
            }
        } else if (!empty($activeFacets)) {
            $mergedArray = array_merge_recursive($activeFacets, [$facetField => [$facetValue => 1]]);
            $result['facet'] = $mergedArray;
        } else {
            $result['facet'] = [$facetField => [$facetValue => 1]];
        }

        return $result;
    }
}