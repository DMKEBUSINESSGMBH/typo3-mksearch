<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2014 DMK E-BUSINESS GmbH
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

tx_rnbase::load('tx_rnbase_action_BaseIOC');
tx_rnbase::load('tx_rnbase_filter_BaseFilter');
tx_rnbase::load('tx_mksearch_util_ServiceRegistry');
tx_rnbase::load('tx_mksearch_action_SearchSolr');


/**
 * Controller to do search requests against lucene.
 */
class tx_mksearch_action_Search extends tx_rnbase_action_BaseIOC {

	/**
	 *
	 * @param array_object $parameters
	 * @param tx_rnbase_configurations $configurations
	 * @param array_object $viewData
	 * @return string error msg or null
	 */
	function handleRequest(&$parameters,&$configurations, &$viewData){

		$confId = $this->getConfId();
		tx_mksearch_action_SearchSolr::handleSoftLink($parameters, $configurations, $confId);

		$filter = tx_rnbase_filter_BaseFilter::createFilter($parameters, $configurations, $viewData, $confId);
		//manchmal will man nur ein Suchformular auf jeder Seite im Header einbinden
		//dieses soll dann aber nur auf eine Ergebnisseite verweisen ohne
		//selbst zu suchen
		if($configurations->get($confId.'nosearch')) return null;

		$fields = array();
		$options = array();
		$items = array();
		if($filter->init($fields, $options)) {
			// @TODO: Adjust Pagebrowser
//			$this->handlePageBrowser($parameters,$configurations, $viewData, $fields, $options);
			$index = tx_mksearch_action_SearchSolr::findSearchIndex($configurations, $confId);
			if(!$index->isValid())
				throw new Exception('Configured search index not found!');

			$searchEngine = tx_mksearch_util_ServiceRegistry::getSearchEngine($index);
			$searchEngine->openIndex($index);
			$items = $searchEngine->search($fields, $options, $configurations);
			$searchEngine->closeIndex();
		}

		$viewData->offsetSet('searchcount', count($items));
		$viewData->offsetSet('search', $items);
		return null;
	}

	function handlePageBrowser(&$parameters,&$configurations, &$viewdata, &$fields, &$options) {
		if(is_array($configurations->get('searchlucene.pagebrowser.'))) {
			$service = tx_mkmodularproducts_util_ServiceRegistry::getProductService();
			// Mit Pagebrowser benötigen wir zwei Zugriffe, um die Gesamtanzahl der Teams zu ermitteln
			$options['count']= 1;
			$listSize = $service->search($fields, $options);
			// 'count'-Option wieder deaktivieren, um "normale" Suche zu ermöglichen
			unset($options['count']);
			// PageBrowser initialisieren
			$pageBrowser = tx_rnbase::makeInstance('tx_rnbase_util_PageBrowser', 'search');
			$pageSize = intval($configurations->get('search.pagebrowser.limit'));
			$pageBrowser->setState($parameters, $listSize, $pageSize);
			$limit = $pageBrowser->getState();
			// Ist schon ein Limit per TS < PB-Limit gesetzt, dann hat dieses Vorrang
			if(intval($options['limit']) && (intval($options['limit']) < intval($limit['limit'])))
				$limit['limit'] = intval($options['limit']);
			$options = array_merge($options, $limit);
			$viewdata->offsetSet('pagebrowser', $pageBrowser);
		}
	}


	function getTemplateName() { return 'searchlucene';}
	function getViewClassName() { return 'tx_mksearch_view_Search';}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/action/class.tx_mksearch_action_Search.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/action/class.tx_mksearch_action_Search.php']);
}
