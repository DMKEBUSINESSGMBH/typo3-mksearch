<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of the "mksearch" Extension for TYPO3 CMS.
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GNU Lesser General Public License can be found at
 * www.gnu.org/licenses/lgpl.html
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 * View class for displaying a list of modular products.
 */
class tx_mksearch_view_Search extends Sys25\RnBase\Frontend\View\Marker\BaseView
{
    protected function createOutput($template, Sys25\RnBase\Frontend\Request\RequestInterface $request, $formatter)
    {
        $viewData = $request->getViewContext();
        $request->getConfigurations();
        $items = &$viewData->offsetGet('search');

        $listBuilder = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Frontend\Marker\ListBuilder::class,
            $viewData->offsetGet('filter') instanceof Sys25\RnBase\Frontend\Marker\IListBuilderInfo ? $viewData->offsetGet('filter') : null
        );

        // wurden options für die markerklassen gesetzt?
        $markerParams = $viewData->offsetExists('markerParams') ? $viewData->offsetGet('markerParams') : [];
        $confId = $request->getConfId();

        return $listBuilder->render(
            $items,
            $viewData,
            $template,
            'tx_mksearch_marker_Search',
            $confId.'hit.',
            'SEARCHRESULT',
            $formatter,
            $markerParams
        );
    }

    /**
     * Subpart der im HTML-Template geladen werden soll.
     *
     * @return string
     */
    protected function getMainSubpart(Sys25\RnBase\Frontend\View\ContextInterface $viewData)
    {
        return '###SEARCH###';
    }
}
