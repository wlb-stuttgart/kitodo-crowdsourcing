<?php

namespace Wlb\Crowdsourcing\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class IsFacetActiveViewHelper extends AbstractViewHelper
{
    /**
     * Register arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('facetField', 'string', '', false, '');

        $this->registerArgument('activeFacets', 'array', '', false, '');
    }

    /**
     *
     * @return bool
     */
    public function render() {

        $facetField = $this->arguments['facetField'];
        $activeFacets = $this->arguments['activeFacets'];

        if (is_array($activeFacets)) {
            if (array_key_exists($facetField, $activeFacets)) {
                return true;
            } else {
                return false;
            }
        }
    }
}