<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_filter
 *  @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2011 Hannes Bochmann <dev@dmk-ebusiness.de>
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
 */

/**
 * benötigte Klassen einbinden
 */


tx_rnbase::load('tx_mksearch_filter_SolrBase');
tx_rnbase::load('tx_mksearch_util_Misc');

/**
 * Filter für autocomplete Anfragen
 *
 * @author hbochmann
 * @package tx_mksearch
 * @subpackage tx_mksearch_filter
 * @TODO Unit Test ob das hinzufügen der FE Gruppen funktioniert.
 */
class tx_mksearch_filter_SolrAutocomplete extends tx_mksearch_filter_SolrBase {

	/**
	 * Fügt den Suchstring zu dem Filter hinzu.
	 *
	 * @param 	array 						$fields
	 * @param 	array 						$options
	 * @param 	tx_rnbase_IParameters 		$parameters
	 * @param 	tx_rnbase_configurations 	$configurations
	 * @param 	string 						$confId
	 */
	protected function handleTerm(&$fields, &$parameters, &$configurations, $confId) {
		$term = $parameters->get('term');
		// lowercase term? default is true!
		if (
			$configurations->get($confId . 'autocomplete.termToLower') === NULL
			|| $configurations->getBool($confId . 'autocomplete.termToLower')
		) {
			$term = mb_strtolower($term, 'UTF-8');
		}
		//we just need the plain, given term, sanitize it and put it in
		$fields['term'] = tx_mksearch_util_Misc::sanitizeTerm($term);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/filter/class.tx_mksearch_filter_SolrAutocomplete.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/filter/class.tx_mksearch_filter_SolrAutocomplete.php']);
}