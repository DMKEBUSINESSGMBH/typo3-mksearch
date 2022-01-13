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

/**
 * Marker class for core tt_content search results.
 */
class tx_mksearch_marker_CoreTtNews extends tx_mksearch_marker_SearchResultSimple
{
    /**
     * @param string                      $template  HTML template
     * @param tx_mksearch_model_SearchHit $item      search hit
     * @param \Sys25\RnBase\Frontend\Marker\FormatUtil   $formatter
     * @param string                      $confId    path of typoscript configuration
     * @param string                      $marker    name of marker
     *
     * @return string readily parsed template
     */
    public function parseTemplate($template, &$item, &$formatter, $confId, $marker = 'ITEM')
    {
        //wir benötigen das datetime_dt feld lediglich zusätzlich als timestamp
        $oDateTime = new DateTime($item->getProperty('datetime_dt'));
        $item->setProperty('datetime_i', $oDateTime->format('U'));

        return parent::parseTemplate($template, $item, $formatter, $confId, $marker);
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/marker/class.tx_mksearch_marker_CoreTtContent.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/marker/class.tx_mksearch_marker_CoreTtContent.php'];
}
