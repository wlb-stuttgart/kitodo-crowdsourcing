<?php

namespace Wlb\Crowdsourcing\ViewHelpers;

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class IsFacetVisibleViewHelper extends AbstractViewHelper
{
    /**
     * Register arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('facetField', 'string', '', false, '');
        $this->registerArgument('facetCounters', 'array', '', false, '');
        $this->registerArgument('activeFacets', 'array', '', false, '');
    }

    /**
     *
     * @return bool
     */
    public function render() {

        $facetField = $this->arguments['facetField'];
        $facetCounters = $this->arguments['facetCounters'];
        $activeFacets = $this->arguments['activeFacets'];

        if ($facetField == 'campaign_faceting') {
            $counters = $facetCounters[$facetField];
            return array_key_exists('campaign_faceting', $activeFacets) ||
                count(array_filter($counters, fn($v) => $v > 0)) > 1;
        }

        return true;
    }
}
