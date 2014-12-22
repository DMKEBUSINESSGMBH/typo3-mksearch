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
		
		if ($configurations->get($confId . 'nosearch')) {
			return NULL;
		}

		$fields = array();
		$options = array();
		$items = array();
		if($filter->init($fields, $options)) {
			$searchSolrAction = $this->getSearchSolrAction();
			$index = $searchSolrAction::findSearchIndex(
				$configurations, $confId
			);

			$serviceRegistry = $this->getServiceRegistry();
			$searchEngine = $serviceRegistry::getSearchEngine($index);
			$searchEngine->openIndex($index);
			
			$this->handlePageBrowser(
				$parameters, $configurations, $confId, $viewData, 
				$fields, $options, $searchEngine
			);
			
			$searchResult = $searchEngine->search($fields, $options, $configurations);
		}

		$viewData->offsetSet('searchcount', $searchResult['numFound']);
		$viewData->offsetSet('search', $searchResult['items']);
		
		return NULL;
	}
	
	/**
	 * 
	 * @return string
	 */
	protected function getSearchSolrAction() {
		return tx_mksearch_action_SearchSolr;
	}
	
	/**
	 * 
	 * @return string
	 */
	protected function getServiceRegistry(){
		return tx_mksearch_util_ServiceRegistry;
	}
	
	/**
	 * @param tx_rnbase_parameters $parameters
	 * @param tx_rnbase_configurations $configurations
	 * @param unknown $confId
	 * @param ArrayObject $viewdata
	 * @param array $fields
	 * @param array $options
	 * @param tx_mksearch_service_engine_ElasticSearch $index
	 * @return void
	 */
	function handlePageBrowser(
		tx_rnbase_parameters $parameters, tx_rnbase_configurations $configurations, 
		$confId, ArrayObject $viewdata, array &$fields, array &$options, 
		tx_mksearch_service_engine_ElasticSearch $searchEngine
	) {
		if(
			(isset($options['limit']))
			&& is_array($conf = $configurations->get($confId.'hit.pagebrowser.'))
		) {
			// PageBrowser initialisieren
			$pageBrowserId = $conf['pbid'] ? $conf['pbid'] : 
							'search' . $configurations->getPluginId();
			/* @var $pageBrowser tx_rnbase_util_PageBrowser */
			$pageBrowser = tx_rnbase::makeInstance(
				'tx_rnbase_util_PageBrowser', $pageBrowserId
			);
			
			$listSize = 0;
			if($result = $searchEngine->getIndex()->count($fields['term'])) {
				$listSize = $result;
			}
			
			$pageBrowser->setState($parameters, $listSize, $options['limit']);
			$state = $pageBrowser->getState();
			
			$options = array_merge($options, $state);
			$viewdata->offsetSet('pagebrowser', $pageBrowser);
		}
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
