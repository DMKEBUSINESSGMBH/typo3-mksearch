<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_util
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

/**
 * Der FacetBuilder erstellt aus den Rohdaten der Facets passende Objekte für das Rendering.
 * @package tx_mksearch
 * @subpackage tx_mksearch_util
 */
class tx_mksearch_util_FacetBuilder {
	/**
	 * Baut die Daten für die Facets zusammen
	 * @param array $facetData Daten von Solr
	 * @return array Ausgabedaten
	 */
	public static function buildFacets($facetData) {
		$facetGroups = array();
		if(!$facetData) return $facetGroups;
		foreach ($facetData As $field => $facetGroup) {
			foreach($facetGroup As $id => $count) {
				$facetGroups[] = self::getSimpleFacet($field, $id, $count);
			}
		}
		return $facetGroups;
	}

	/**
	 * Liefert eine simple Facette zurück
	 * @param string $field
	 * @param int $id
	 * @param int $count
	 */
	private static function getSimpleFacet($field, $id, $count) {
		$title = $id;
		return tx_rnbase::makeInstance('tx_mksearch_model_Facet', $field, $id, $title, $count);		
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_FacetBuilder.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_FacetBuilder.php']);
}