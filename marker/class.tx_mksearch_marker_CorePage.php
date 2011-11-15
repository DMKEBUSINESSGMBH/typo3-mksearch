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

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_mksearch_marker_SearchResultSimple');

/**
 * Marker class for core page search results
 */
class tx_mksearch_marker_CorePage extends tx_mksearch_marker_SearchResultSimple {

	/**
	 * Prepare links
	 *
	 * @param tx_mksearch_model_SearchHit $item
	 * @param string $marker
	 * @param array $markerArray
	 * @param array $wrappedSubpartArray
	 * @param string $confId
	 * @param tx_rnbase_util_FormatUtil $formatter
	 */
	public function prepareLinks(&$item, $marker, &$markerArray, &$subpartArray, &$wrappedSubpartArray, $confId, &$formatter) {
		// Fill TSFE register with adequate page id.
		// Usually you won't need that - it's a special case for core.tt_content.
		// For other contents you just configure the page id via TS statically
		// as all search results of one particular content type will be shown
		// within the same page which is to be configured preliminarily. 
		$GLOBALS['TSFE']->register['mksearch.core.page'] = $item->uid;
		$linkId = 'show';
		$this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, $linkId, $marker, array());
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/marker/class.tx_mksearch_marker_CorePage.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/marker/class.tx_mksearch_marker_CorePage.php']);
}