<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_filter
 *  @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2011 Hannes Bochmann <hannes.bochmann@das-medienkombinat.de>
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
require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');

tx_rnbase::load('tx_mksearch_filter_SolrBase');
tx_rnbase::load('tx_mksearch_util_Misc');

/**
 * Filter für autocomplete Anfragen
 *
 * @author hbochmann
 * @package tx_mksearch
 * @subpackage tx_mksearch_filter
 */
class tx_mksearch_filter_SolrAutocomplete extends tx_mksearch_filter_SolrBase {
	
	/**
	 * Filter for search form
	 *
	 * @param array $fields
	 * @param array $options
	 * @param tx_rnbase_parameters $parameters
	 * @param tx_rnbase_configurations $configurations
	 * @param string $confId
	 * @return bool	Should subsequent query be executed at all?
	 *
	 */
	protected function initFilter(&$fields, &$options, &$parameters, &$configurations, $confId) {
		
		// @TODO: könnte auch über TS gesetzt sein
		$usedIndex = $parameters->get('usedIndex');
		$term = $parameters->get('term');
		// Es muss ein Submit-Parameter im request liegen, damit der Filter greift
		// für die solr abfrage benötigen wir dringend den usedIndex und term parameter
		// sind beide nicht gegeben brauchen wir nicht suchen!
		if(empty($usedIndex) || empty($term) || !($parameters->offsetExists('submit') || $configurations->get($confId.'force'))) {
			return false;
		}
		
		// set request handler
		// should be a request handler dealing with suggestions. take a look in the
		// default solrconfig.xml and search for a requestHandler named suggest and
		// the appropiate search component suggest
		if($requestHandler = $parameters->get('qt'))
			$options['qt'] = $requestHandler;
			
		// suchstring beachten
		$this->handleTerm($fields, $parameters, $configurations, $confId);
		
		
		return true;
	}

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
		//we just need the plain, given term, sanitize it and put it in
		$fields['term'] = tx_mksearch_util_Misc::sanitizeTerm($parameters->get('term'));
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/filter/class.tx_mksearch_filter_SolrAutocomplete.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/filter/class.tx_mksearch_filter_SolrAutocomplete.php']);
}