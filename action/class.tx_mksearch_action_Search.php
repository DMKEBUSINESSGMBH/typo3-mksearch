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
			// Adjust Pagebrowser
			$this->handlePageBrowser($parameters,$configurations, $viewData, $fields, $options);
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

	/**
	 * In Lucene funktioniert die Pagination etwas anders. Lucene liefert nämlich immer alle
	 * Suchergebnisse. Daher muss man selbst den passenden Ausschnitt auswählen. Damit das trotzdem
	 * performant bleibt, liefert Lucene nur kleine Flyweight-Objekte für die Treffer. Der Abruf der
	 * eigentlichen Dokumente erfolgt erst in einem zweiten Schritt. Somit wird für die Pagination nur
	 * mit den Flyweights gearbeitet. Und das übernimmt der Lucene-Engine gleich selbst, wenn man ihr
	 * eine Instanz des PageBrowsers übergibt.
	 *
	 * @param unknown $parameters
	 * @param unknown $configurations
	 * @param unknown $viewdata
	 * @param unknown $fields
	 * @param unknown $options
	 */
	protected function handlePageBrowser($parameters, $configurations, $viewdata, &$fields, &$options) {
		//if($_SERVER['REMOTE_ADDR']  == '178.15.114.146')
		$confId = $this->getConfId();
		if(is_array($conf = $configurations->get($confId.'hit.pagebrowser.'))) {
			// PageBrowser initialisieren
			$pageBrowserId = $conf['pbid'] ? $conf['pbid'] : 'search'.$configurations->getPluginId();
			/* @var $pageBrowser tx_rnbase_util_PageBrowser */
			$pageBrowser = tx_rnbase::makeInstance('tx_rnbase_util_PageBrowser', $pageBrowserId);
			$pageSize = intval($conf['limit']);
			// Den PageBrowser mit PageSize und der aktuellen Seite füllen
			$pageBrowser->setPageSize($pageSize);
			$pageBrowser->setPointerByParameters($parameters);
			$options['pb'] = $pageBrowser;
			$viewdata->offsetSet('pagebrowser', $pageBrowser);
		}
	}


	function getTemplateName() { return 'searchlucene';}
	function getViewClassName() { return 'tx_mksearch_view_Search';}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/action/class.tx_mksearch_action_Search.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/action/class.tx_mksearch_action_Search.php']);
}
