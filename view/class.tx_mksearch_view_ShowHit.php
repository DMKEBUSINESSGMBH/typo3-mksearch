<?php
/**
 * @package tx_mksearch
 * @subpackage tx_mksearch_view
 *
 * Copyright notice
 *
 * (c) 2013 das MedienKombinat GmbH <kontakt@das-medienkombinat.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

require_once t3lib_extMgm::extPath('rn_base', 'class.tx_rnbase.php');
tx_rnbase::load('tx_rnbase_view_Single');


/**
 * Detailseite eines beliebigen Datensatzes aus Momentan Lucene oder Solr.
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_view
 * @author Michael Wagner <michael.wagner@das-medienkombinat.de>
 */
class tx_mksearch_view_ShowHit extends tx_rnbase_view_Single {

	protected function getItemPath($configurations, $confId) {
		$itemPath = $configurations->get($confId.'template.itempath');
		return $itemPath ? $itemPath : 'hit';
	}

	protected function getMarkerClass($configurations, $confId) {
		$marker = $configurations->get($confId.'template.markerclass');
		return $marker ? $marker : 'tx_mksearch_marker_Search';
	}

	/**
	 * Subpart der im HTML-Template geladen werden soll. Dieser wird der Methode
	 * createOutput automatisch als $template übergeben.
	 *
	 * @return string
	 */
	function getMainSubpart() {
		$subpart = $this->getController()->getConfigurations()->get($confId.'template.subpart');
		return $subpart ? $subpart : '###SHOWHIT###';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/view/class.tx_mksearch_view_Search.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/view/class.tx_mksearch_view_Search.php']);
}