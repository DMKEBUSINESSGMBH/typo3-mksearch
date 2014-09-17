<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_util
 *
 *  Copyright notice
 *
 *  (c) 2011 DMK E-Business GmbH <dev@dmk-ebusiness.de>
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

require_once(t3lib_extMgm::extPath('rn_base', 'class.tx_rnbase.php'));

/**
 * Der FacetBuilder erstellt aus den Rohdaten der Facets passende Objekte für das Rendering.
 * @package tx_mksearch
 * @subpackage tx_mksearch_util
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_util_SolrResponseProcessor {
	/**
	 * Konfigurations Objekt
	 * @var tx_rnbase_configurations
	 */
	private $configurations = null;
	private $confId = 'responseProcessor.';

	/**
	 *
	 * Enter description here ...
	 * @param array $response
	 * @param array $options
	 * @param tx_rnbase_configurations $configurations
	 * @param string $confId
	 * @return bool
	 */
	public static function processSolrResult(array &$result, $options, &$configurations, $confId) {
		static $instance = null;

		if(
			!array_key_exists('response', $result)
		|| !($result['response'] instanceof Apache_Solr_Response)
		) return false;

		if (!$instance)
			$instance = new tx_mksearch_util_SolrResponseProcessor($configurations, $confId);

		$response = &$result['response'];
		$result = $instance->processSolrResponse($response, $options, $result);
		return true;
	}

	public function tx_mksearch_util_SolrResponseProcessor(&$configurations, $confId) {
		$this->configurations = $configurations;
		$this->confId = $confId;
	}
	/**
	 * @return tx_rnbase_configurations
	 */
	private function getConfigurations() {
		return $this->configurations;
	}
	/**
	 * @return string
	 */
	private function getConfId() {
		return $this->confId;
	}

	/**
	 *
	 * Enter description here ...
	 * @param Apache_Solr_Response $response
	 * @param unknown_type $result
	 */
	public function processSolrResponse(Apache_Solr_Response &$response, $options, $result = array()) {
		$result['items'] = $this->processHits($response, $options, empty($result['items']) ? array() : $result['items']);
		$result['facets'] = $this->processFacets($response);
		$result['suggestions'] = $this->processSuggestions($response);
		return $result;
	}

	/**
	 * @TODO: sollte es hierfür nicht auch eine klasse wie tx_mksearch_util_HitBuilder geben?
	 *
	 * @param Apache_Solr_Response $response
	 * @return array
	 */
	public function processHits(Apache_Solr_Response &$response, array $options, array $hits = array()) {
		$confId = $this->getConfId().'hit.';

		//highlighting einfügen
		$highlights = $this->getHighlighting($response);

		// hier wird nur highlighting gesetzt
		// wenn keins existiert brauchen wir nichts machen
		if (empty($highlights)) return $hits;

		// Gibt es docs?
		// Im Fall eines Autocompletes, haben wir keinen Responce, nur Suggestions!
		if($response->response->docs) {
			if (empty($hits)) { // wir erzeugen die hits, sollte vom service allerdings schon geschehen sein.
				foreach($response->response->docs as $doc) {
					$hits[] = tx_rnbase::makeInstance('tx_mksearch_model_SolrHit', $doc);
				}
			}
			foreach($hits as &$hit) {
				//highlighting hinzufügen für alle Felder
				if(!empty($highlights[$hit->record['id']])){
					foreach($highlights[$hit->record['id']] as $docField => $highlightValue) {
						//Solr liefert die Highlightings gesondert weshalb wir diese in das
						//eigentliche Dokument bekommen müssen. Dafür gibts es 2 Möglichkeiten:
						//1. wenn overrideWithHl auf true gesetzt ist werden die jeweiligen Inhaltsfelder
						//mit den korrespondierenden Highlighting Snippets überschrieben. Dabei muss man auf
						//hl.fragsize achten da die Snippets nur so lang sind wie in hl.fragsize angegeben
						//2. ist overrideWithHl nicht gesetzt dann werden die Highlighting Snippets
						//in ein eigenes Feld nach folgendem Schema ins Dokument geschrieben: $Feldname_hl
						//dabei wäre es dann möglich die Felder flexibel über TS überschrieben zu lassen
						//indem bspw. ein TS wie content.override.field = content_hl angegeben wird ;)
						$overrideWithHl = $this->getConfigurations()->get($confId.'overrideWithHl');
						$overrideWithHl = $overrideWithHl ? $overrideWithHl : (isset($options['overrideWithHl']) && $options['overrideWithHl']);
						$highlightField = ($overrideWithHl) ? $docField : $docField.'_hl';

						$hit->record[$highlightField] = $highlightValue;
					}
				}
			}
		}
		return $hits;
	}
	/**
	 *
	 * @param Apache_Solr_Response $response
	 * @return array
	 */
	public function processFacets(Apache_Solr_Response &$response) {
		$confId = $this->getConfId().'facet.';
		//Facets
		if($response->facet_counts) {
			$builderClass = $this->getConfigurations()->get($confId . 'builderClass');
			$builderClass = $builderClass ? $builderClass : 'tx_mksearch_util_FacetBuilder';
			tx_rnbase::load('tx_mksearch_util_FacetBuilder');
			$facetBuilder = tx_mksearch_util_FacetBuilder::getInstance($builderClass);
			$facets = $facetBuilder->buildFacets($response->facet_counts->facet_fields);
		} else $facets = array();
		return $facets;
	}
	/**
	 *
	 * @param Apache_Solr_Response $response
	 * @return array
	 */
	public function processSuggestions(Apache_Solr_Response &$response) {
		$confId = $this->getConfId().'suggestions.';
		//Suggestions
		if($response->spellcheck->suggestions){
			$builderClass = $this->getConfigurations()->get($confId.'builderClass');
			$builderClass = $builderClass ? $builderClass : 'tx_mksearch_util_SuggestionBuilder';
			tx_rnbase::load('tx_mksearch_util_SuggestionBuilder');
			$builder = tx_mksearch_util_SuggestionBuilder::getInstance($builderClass);
			$suggestions = $builder->buildSuggestions($response->spellcheck->suggestions);
		} else $suggestions = array();
		return $suggestions;
	}

	/**
	 * Checks if we got highlightings and wraps them in case in an array
	 *
	 * @param Apache_Solr_Response $response
	 * @return array
	 */
	private function getHighlighting(Apache_Solr_Response $response) {
		$aHighlights = array();
		//Highlighting für jedes gefundene Dokument
		if(!empty($response->highlighting)) {
			foreach($response->highlighting as $iHighlightId => $aHighlighting) {
				//jedes Feld mit einem Highlighting
				foreach($aHighlighting as $sHighlightFieldsName => $aHighlightFields) {
					//jedes Highlighting
					foreach($aHighlightFields as $sHighlightField) {
						//wir nehmen als key die Dokument ID ($highlightId) zwecks Zuordnung
						$aHighlights[$iHighlightId][$sHighlightFieldsName] = $sHighlightField;
					}
				}
			}
		}

		return $aHighlights;
	}


}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_SolrResponseProcessor.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_SolrResponseProcessor.php']);
}
