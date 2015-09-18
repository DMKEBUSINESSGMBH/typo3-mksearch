<?php
/***************************************************************
 *  Copyright notice
 *
 * (c) 2015 DMK E-BUSINESS GmbH <kontakt@dmk-ebusiness.de>
 * All rights reserved
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
require_once t3lib_extMgm::extPath('rn_base', 'class.tx_rnbase.php');

tx_rnbase::load('tx_rnbase_util_SimpleMarker');

/**
 * Anzeige der Vorschläge des Spell-Checkers
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_marker
 * @author René Nitzsche <nitzsche@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_marker_Suggestion extends tx_rnbase_util_SimpleMarker {

	/**
	 * rendert die suggestions
	 *
	 * @param string $template HTML template
	 * @param tx_mksearch_model_SearchHit $item search hit
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param string $confId path of typoscript configuration
	 * @param string $marker name of marker
	 * @return string readily parsed template
	 */
	public function parseTemplate($template, $item, $formatter, $confId, $marker = 'ITEM') {
		if(!is_object($item)) {
			return $template;
		}

		// TODO: Link auf neue Suche

		// Fill MarkerArray
		$unused = $this->findUnusedCols($item->record, $template, $marker);
		$markerArray = $formatter->getItemMarkerArrayWrapped($item->record, $confId, $unused, $marker.'_');

		// subparts erzeugen
		$subpartArray = $wrappedSubpartArray = array();
		$out = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
		return $out;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/marker/class.tx_mksearch_marker_Suggestion.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/marker/class.tx_mksearch_marker_Suggestion.php']);
}
