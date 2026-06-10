<?php

// SPDX-FileCopyrightText: 2026 Württembergische Landesbibliothek
//
// SPDX-License-Identifier: GPL-3.0-or-later

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
        $this->registerArgument('facetValue', 'string', '', false, '');

        $this->registerArgument('activeFacets', 'array', '', false, '');
    }

    /**
     *
     * @return bool
     */
    public function render() {

        $facetField = $this->arguments['facetField'];
        $activeFacets = $this->arguments['activeFacets'];
        $facetValue = $this->arguments['facetValue'];


        if (is_array($activeFacets)) {
            if (array_key_exists($facetField, $activeFacets)) {
                if (array_key_exists($facetValue, $activeFacets[$facetField])) {
                    return true;
                }
            } else {
                return false;
            }
        }
    }
}