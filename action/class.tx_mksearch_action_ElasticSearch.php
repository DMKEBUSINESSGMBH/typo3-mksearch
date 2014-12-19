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
 * @author Hannes Bochmann <hannes.bochmann@dmk-business.de>
 */
class tx_mksearch_action_ElasticSearch extends tx_rnbase_action_BaseIOC {

	/**
	 *
	 * @param array_object $parameters
	 * @param tx_rnbase_configurations $configurations
	 * @param array_object $viewData
	 * @return string error msg or null
	 */
	function handleRequest(&$parameters,&$configurations, &$viewData){
		$confId = $this->getConfId();
		tx_mksearch_action_SearchSolr::handleSoftLink(
			$parameters, $configurations, $confId
		);

		$filter = tx_rnbase_filter_BaseFilter::createFilter(
			$parameters, $configurations, $viewData, $confId  .'filter.'
		);
		//manchmal will man nur ein Suchformular auf jeder Seite im Header einbinden
		//dieses soll dann aber nur auf eine Ergebnisseite verweisen ohne
		//selbst zu suchen
		if($configurations->get($confId.'nosearch')) return null;

		$fields = array();
		$options = array();
		$items = array();
		if($filter->init($fields, $options)) {
			$index = tx_mksearch_action_SearchSolr::findSearchIndex(
				$configurations, $confId
			);
			if(!$index->isValid()) {
				throw new Exception('Configured search index not found!');
			}

			$searchEngine = tx_mksearch_util_ServiceRegistry::getSearchEngine($index);
			$searchEngine->openIndex($index);
			$filter->handlePageBrowser($configurations,
				$confId . 'hit.pagebrowser.', $viewData, $fields, $options,
				array('searchcallback'=> array($searchEngine, 'search'))
			);
			$searchResult = $searchEngine->search($fields, $options, $configurations);
			$searchEngine->closeIndex();
		}

		$viewData->offsetSet('searchcount', $searchResult['numFound']);
		$viewData->offsetSet('search', $searchResult['items']);
		
		return NULL;
	}


	/**
	 * (non-PHPdoc)
	 * @see tx_rnbase_action_BaseIOC::getTemplateName()
	 */
	public function getTemplateName() { 
		return 'elasticsearch';
	}
	
	/**
	 * (non-PHPdoc)
	 * @see tx_rnbase_action_BaseIOC::getViewClassName()
	 */
	public function getViewClassName() { 
		return 'tx_mksearch_view_Search';
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/action/class.tx_mksearch_action_ElasticSearch.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/action/class.tx_mksearch_action_ElasticSearch.php']);
}
