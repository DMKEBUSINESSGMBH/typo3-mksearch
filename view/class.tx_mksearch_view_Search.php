<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 das Medienkombinat
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

tx_rnbase::load('tx_rnbase_view_Base');
tx_rnbase::load('tx_rnbase_util_BaseMarker');

/**
 * View class for displaying a list of modular products.
 */
class tx_mksearch_view_Search extends tx_rnbase_view_Base
{
    public function createOutput($template, &$viewData, &$configurations, &$formatter)
    {
        // Get data from action
        $items = &$viewData->offsetGet('search');

        $listBuilder = tx_rnbase::makeInstance(
            'tx_rnbase_util_ListBuilder',
            $viewData->offsetGet('filter') instanceof ListBuilderInfo ? $viewData->offsetGet('filter') : null
        );

        // wurden options für die markerklassen gesetzt?
        $markerParams = $viewData->offsetExists('markerParams') ? $viewData->offsetGet('markerParams') : array();
        $confId = $this->getController()->getConfId();
        $out = $listBuilder->render(
            $items,
            $viewData,
            $template,
            'tx_mksearch_marker_Search',
            $confId.'hit.',
            'SEARCHRESULT',
            $formatter,
            $markerParams
        );

        return $out;
    }

    /**
     * Subpart der im HTML-Template geladen werden soll.
     *
     * @return string
     */
    public function getMainSubpart(&$viewData)
    {
        return '###SEARCH###';
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/view/class.tx_mksearch_view_Search.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/view/class.tx_mksearch_view_Search.php'];
}
