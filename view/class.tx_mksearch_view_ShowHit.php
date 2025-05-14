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
 * Detailseite eines beliebigen Datensatzes aus Momentan Lucene oder Solr.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_view_ShowHit extends Sys25\RnBase\Frontend\View\Marker\ListView
{
    public function createOutput($template, Sys25\RnBase\Frontend\Request\RequestInterface $request, $formatter)
    {
        $confId = $request->getConfId();
        $item = $request->getViewContext()->offsetGet('item');
        $itemPath = $this->getItemPath($request->getConfigurations(), $confId);
        $markerClass = $this->getMarkerClass($request->getConfigurations(), $confId);

        $marker = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($markerClass);

        return $marker->parseTemplate($template, $item, $formatter, $confId.$itemPath.'.', strtoupper($itemPath));
    }

    protected function getItemPath($configurations, $confId)
    {
        $itemPath = $configurations->get($confId.'template.itempath');

        return $itemPath ?: 'hit';
    }

    protected function getMarkerClass($configurations, $confId)
    {
        $marker = $configurations->get($confId.'template.markerclass');

        return $marker ?: 'tx_mksearch_marker_Search';
    }

    /**
     * Subpart der im HTML-Template geladen werden soll. Dieser wird der Methode
     * createOutput automatisch als $template übergeben.
     *
     * @return string
     */
    protected function getMainSubpart(Sys25\RnBase\Frontend\View\ContextInterface $viewData)
    {
        return $this->subpart ?: '###SHOWHIT###';
    }
}
