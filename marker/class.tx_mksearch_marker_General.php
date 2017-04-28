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

tx_rnbase::load('tx_rnbase_util_Templates');
tx_rnbase::load('tx_rnbase_util_BaseMarker');

/**
 * Generic class for rendering contents
 * Renders just anything you pass in ;-)
 */
class tx_mksearch_marker_General extends tx_rnbase_util_BaseMarker
{

    /**
     * @param string $template das HTML-Template
     * @param array $data Daten, die ersetzt werden sollen
     * @param tx_rnbase_util_FormatUtil $formatter der zu verwendente Formatter
     * @param string $confId Pfad der TS-Config des Vereins, z.B. 'listView.club.'
     * @param string $marker name of marker, z.B. CLUB
     *        Von diesem String hängen die entsprechenden weiteren Marker ab: ###CLUB_NAME###, ###COACH_ADDRESS_WEBSITE###
     * @return String das geparste Template
     */
    public function parseTemplate($template, &$data, &$formatter, $confId, $marker)
    {
        $marker = $marker ? $marker.'_' : '';
        // Es wird das MarkerArray gefüllt.
        $markerArray = $formatter->getItemMarkerArrayWrapped($data, $confId, 0, $marker, array_keys($data));

        return tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/marker/class.tx_mksearch_marker_General.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/marker/class.tx_mksearch_marker_General.php']);
}
