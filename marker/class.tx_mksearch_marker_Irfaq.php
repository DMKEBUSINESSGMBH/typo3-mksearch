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
 * @author Hannes Bochmann
 */
class tx_mksearch_marker_Irfaq
	extends tx_mksearch_marker_SearchResultSimple {


	/**
	 * Prepare links
	 *
	 * @param tx_mksearch_model_SearchHit $item
	 * @param string $marker
	 * @param array $markerArray
	 * @param array $wrappedSubpartArray
	 * @param string $confId
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param string $template
	 */
	public function prepareLinks(&$item, $marker, &$markerArray, &$subpartArray, &$wrappedSubpartArray, $confId, &$formatter, $template) {
		parent::prepareLinks($item, $marker, $markerArray, $subpartArray, $wrappedSubpartArray, $confId, $formatter, $template);

		//nachträglich entfernen. geht nicht über rnbase da remove nur möglich ist, wenn das model
		//nicht persisted ist. Das ist ein Solr Dokument aber immer.
		if(!$item->record['category_first_shortcut_s']) {
			$linkMarker = $marker . '_SHOWFIRSTCATEGORYLINK';
			$this->disableLink($markerArray, $subpartArray, $wrappedSubpartArray, $linkMarker, true);
			unset($wrappedSubpartArray['###ITEM_SHOWFIRSTCATEGORYLINK###']);
		}
	}
}
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/marker/class.tx_mksearch_marker_Irfaq.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/marker/class.tx_mksearch_marker_Irfaq.php']);
}