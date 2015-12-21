<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011-2013 das Medienkombinat
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

tx_rnbase::load('tx_rnbase_view_Base');
tx_rnbase::load('tx_rnbase_util_BaseMarker');
tx_rnbase::load('tx_rnbase_util_Templates');

/**
 * View class for displaying a list of solr search results
 */
class tx_mksearch_view_SearchSolr extends tx_rnbase_view_Base {

	/**
	 * @var string
	 */
	private $confId = '';
	/**
	 * @var tx_rnbase_configurations
	 */
	private $configurations = null;

	/**
	 *
	 * @param string 						$template
	 * @param ArrayObject 					$viewData
	 * @param tx_rnbase_configurations 		$configurations
	 * @param tx_rnbase_util_FormatUtil 	$formatter
	 */
	function createOutput($template, &$viewData, &$configurations, &$formatter) {
		// Get data from action
		$result =& $viewData->offsetGet('result');

		//shall we parse the content just as json
		if($configurations->getParameters()->get('ajax')) {
			// if the frontend debug is enabled, so the json will be invalid.
			// so we has to disable the debug.
			$GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = 0;
			tx_rnbase::load('tx_rnbase_util_TYPO3');
			if (tx_rnbase_util_TYPO3::isTYPO60OrHigher()) {
				$GLOBALS['TSFE']->TYPO3_CONF_VARS['FE']['debug'] = 0;
			}
			return json_encode($result);
		}
		//else

		$items = $result ? $result['items'] : array();
		/* @var $listBuilder tx_rnbase_util_ListBuilder */
		$listBuilder = tx_rnbase::makeInstance(
				'tx_rnbase_util_ListBuilder',
				$viewData->offsetGet('filter') instanceof ListBuilderInfo ? $viewData->offsetGet('filter') : null
			);

		// wurden options für die markerklassen gesetzt?
		$markerParams = $viewData->offsetExists('markerParams') ? $viewData->offsetGet('markerParams') : array();

		$markerClass = $configurations->get($this->confId.'mainmarkerclass');
		$markerClass = $markerClass ? $markerClass : 'tx_mksearch_marker_Search';

		$out = $listBuilder->render(
			$items, $viewData,
			$template, $markerClass,
			$this->confId.'hit.', 'SEARCHRESULT',
			$formatter, $markerParams
		);


		//noch die Facetten parsen wenn da
		$out = $this->handleFacets($out, $viewData, $configurations, $formatter, $listBuilder, $result);
		$out = $this->handleSuggestions($out, $viewData, $configurations, $formatter, $listBuilder, $result);

		return $out;
	}
	/**
	 * Ausgabe von Suggestions für alternative Suchbegriffe
	 *
	 * @param string $template
	 * @param array_object $viewData
	 * @param tx_rnbase_configurations $configurations
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param tx_rnbase_util_ListBuilder $listBuilder
	 * @param array $result
	 *
	 * @return string
	 */
	protected function handleSuggestions($template, $viewData, $configurations, $formatter, $listBuilder, $result) {

		$suggestions = $result ? $result['suggestions'] : array();

		if(!empty($suggestions)) {
			$suggestions = reset($suggestions);
		}

		if (tx_rnbase_util_BaseMarker::containsMarker($template, 'SUGGESTIONS')) {
			$markerClass = $configurations->get($this->confId.'suggestions.markerClass');
			$markerClass = $markerClass ? $markerClass : 'tx_rnbase_util_SimpleMarker';

			$template = $listBuilder->render(
					$suggestions, $viewData, $template,
					$markerClass,
					$this->confId . 'suggestions.',
					'SUGGESTION',
					$formatter
			);
		}
		return $template;
	}

	/**
	 * Kümmert sich um das Parsen der Facetten
	 *
	 * @param string $template
	 * @param array_object $viewData
	 * @param tx_rnbase_configurations $configurations
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param tx_rnbase_util_ListBuilder $listBuilder
	 * @param array $result
	 *
	 * @return string
	 */
	protected function handleFacets($template, $viewData, $configurations, $formatter, $listBuilder, $result) {
		$out = $template;

		// dann Liste parsen
		$facets = $result ? (array) $result['facets'] : array();

		// the old way!
		if (tx_rnbase_util_BaseMarker::containsMarker($out, 'FACETS')) {
			//erstmal die Markerklasse holen
			$facetMarkerClass = $configurations->get($this->confId.'facet.markerClass');
			$facetMarkerClass = $facetMarkerClass ? $facetMarkerClass : 'tx_mksearch_marker_Facet';

			// früher wurden alle facetten in einer liste nacheinander herausgerendert!
			// dies muss aus kompatibilitätsgründen beibehalten werden.
			$mergedFacets = array();
			foreach ($facets as $group) {
				$mergedFacets = array_merge($mergedFacets, $group->getItems());
			}
			$out = $listBuilder->render(
				$mergedFacets, $viewData, $out,
				$facetMarkerClass, $this->confId.'facet.', 'FACET', $formatter
			);

			// den alten zurücklink
			/* @var $baseMarker tx_rnbase_util_BaseMarker */
			$baseMarker = tx_rnbase::makeInstance('tx_rnbase_util_BaseMarker');
			$wrappedSubpartArray = $subpartArray = $markerArray = array();
			$baseMarker->initLink(
				$markerArray, $subpartArray, $wrappedSubpartArray,
				$formatter, $this->confId.'facet.', 'reset', 'FACET', array(), $out
			);
			$out = tx_rnbase_util_Templates::substituteMarkerArrayCached(
				$out, $markerArray, $subpartArray, $wrappedSubpartArray
			);
		}

		// wir geben die facetten grupiert aus.
		if (tx_rnbase_util_BaseMarker::containsMarker($out, 'GROUPEDFACETS')) {
			//erstmal die Markerklasse holen
			$groupedMarkerClass = $configurations->get($this->confId.'groupedfacet.markerClass');
			$groupedMarkerClass = $groupedMarkerClass ? $groupedMarkerClass : 'tx_mksearch_marker_GroupedFacet';

			$out = $listBuilder->render(
				$facets, $viewData, $out,
				$groupedMarkerClass,
				$this->confId . 'groupedfacet.',
				'GROUPEDFACET',
				$formatter
			);
		}

		return $out;
	}

	/**
	 * This method is called first.
	 *
	 * @param tx_rnbase_configurations $configurations
	 */
	function _init($configurations){
		$this->confId = $this->getController()->getConfId();
		$this->configurations = &$configurations;
	}

	/**
	 * Subpart der im HTML-Template geladen werden soll. Dieser wird der Methode
	 * createOutput automatisch als $template übergeben.
	 *
	 * @param ArrayObject 					$viewData
	 * @return string
	 */
	function getMainSubpart(&$viewData) {
		// Wir versuchen den Mainpart aus der viewdata zu holen.
		// Das kann der Fall sein, wenn der Mainpart im Filter oder einer eigenen Action gesetzt wurde.
		$mainSubpart = $viewData->offsetGet('mainsubpart');
		// Wir holen uns den Mainpart vom Typoscript.
		$mainSubpart = $mainSubpart ? $mainSubpart : $this->configurations->get($this->confId.'mainsubpart');
		// Fallback, wenn kein Mainpart gesetzt wurde.
		return $mainSubpart ? $mainSubpart : '###SEARCH###';
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/view/class.tx_mksearch_view_SearchSolr.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/view/class.tx_mksearch_view_SearchSolr.php']);
}
