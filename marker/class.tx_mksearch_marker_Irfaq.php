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
 * @author Hannes Bochmann
 */
class tx_mksearch_marker_Irfaq extends tx_mksearch_marker_SearchResultSimple
{
    /**
     * Prepare links.
     *
     * @param tx_mksearch_model_SearchHit             $item
     * @param string                                  $marker
     * @param array                                   $markerArray
     * @param array                                   $wrappedSubpartArray
     * @param string                                  $confId
     * @param Sys25\RnBase\Frontend\Marker\FormatUtil $formatter
     * @param string                                  $template
     */
    protected function prepareLinks($item, $marker, &$markerArray, &$subpartArray, &$wrappedSubpartArray, $confId, $formatter, $template): void
    {
        parent::prepareLinks($item, $marker, $markerArray, $subpartArray, $wrappedSubpartArray, $confId, $formatter, $template);

        // nachträglich entfernen. geht nicht über rnbase da remove nur möglich ist, wenn das model
        // nicht persisted ist. Das ist ein Solr Dokument aber immer.
        if (!$item->getProperty('category_first_shortcut_s')) {
            $linkMarker = $marker.'_SHOWFIRSTCATEGORYLINK';
            $this->disableLink($markerArray, $subpartArray, $wrappedSubpartArray, $linkMarker, true);
            unset($wrappedSubpartArray['###ITEM_SHOWFIRSTCATEGORYLINK###']);
        }
    }
}
