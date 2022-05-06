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
 * Generic class for rendering contents
 * Renders just anything you pass in ;-).
 */
class tx_mksearch_marker_General extends \Sys25\RnBase\Frontend\Marker\BaseMarker
{
    /**
     * @param string                    $template  das HTML-Template
     * @param array                     $data      Daten, die ersetzt werden sollen
     * @param \Sys25\RnBase\Frontend\Marker\FormatUtil $formatter der zu verwendente Formatter
     * @param string                    $confId    Pfad der TS-Config des Vereins, z.B. 'listView.club.'
     * @param string                    $marker    name of marker, z.B. CLUB
     *                                             Von diesem String hängen die entsprechenden weiteren Marker ab: ###CLUB_NAME###, ###COACH_ADDRESS_WEBSITE###
     *
     * @return string das geparste Template
     */
    public function parseTemplate($template, &$data, &$formatter, $confId, $marker)
    {
        $marker = $marker ? $marker.'_' : '';
        // Es wird das MarkerArray gefüllt.
        $markerArray = $formatter->getItemMarkerArrayWrapped($data, $confId, 0, $marker, array_keys($data));

        return \Sys25\RnBase\Frontend\Marker\Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
    }
}
