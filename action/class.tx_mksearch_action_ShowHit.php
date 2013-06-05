<?php
/**
 * @package tx_mksearch
 * @subpackage tx_mksearch_action
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

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_rnbase_action_BaseIOC');
tx_rnbase::load('tx_mksearch_util_ServiceRegistry');

/**
 * Detailseite eines beliebigen Datensatzes aus Momentan Lucene oder Solr.
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_action
 * @author Michael Wagner <michael.wagner@das-medienkombinat.de>
 */
class tx_mksearch_action_ShowHit extends tx_rnbase_action_BaseIOC {

	/**
	 *
	 * @param array_object $parameters
	 * @param tx_rnbase_configurations $configurations
	 * @param array_object $viewData
	 * @return string error msg or null
	 */
	function handleRequest(&$parameters,&$configurations, &$viewData){
		$confId = $this->getConfId();

		$index = $this->getIndex();
		$item = $this->findItem($index);

		$viewData->offsetSet('item', $item);
		return null;
	}

	protected function findItem($index) {
		$configurations = $this->getConfigurations();
		$confId = $this->getConfId();

		$extKey = $configurations->get($confId.'extkey');
		$contentType = $configurations->get($confId.'contenttype');
		$uid = $configurations->get($confId.'uid');
		$uid = $uid ? $uid : $configurations->getParameters()->getInt('item');

		if (!($extKey && $contentType && $uid)) {
			throw new InvalidArgumentException(
				'Missing Parameters. extkey, contenttype and item are required.', 1370429706);
		}

		try {
			// in unserem fall sollte es der solr service sein!
			/* @var $searchEngine tx_mksearch_service_engine_Solr */
			$searchEngine = tx_mksearch_util_ServiceRegistry::getSearchEngine($index);
			$searchEngine->openIndex($index);
			$item = $searchEngine->getByContentUid($uid, $extKey, $contentType);
			$searchEngine->closeIndex();
		}
		catch(Exception $e) {
			$lastUrl = $e instanceof tx_mksearch_service_engine_SolrException ? $e->getLastUrl() : '';
			//Da die Exception gefangen wird, würden die Entwickler keine Mail bekommen
			//also machen wir das manuell
			if($addr = tx_rnbase_configurations::getExtensionCfgValue('rn_base', 'sendEmailOnException')) {
				tx_rnbase::load('tx_rnbase_util_Misc');
				tx_rnbase_util_Misc::sendErrorMail($addr, 'tx_mksearch_action_SearchSolr_searchSolr', $e);
			}
			tx_rnbase::load('tx_rnbase_util_Logger');
			tx_rnbase_util_Logger::fatal(
				'Solr search failed with Exception!',
				'mksearch', array(
					'Exception' => $e->getMessage(),
					'fields' => $fields,
					'options' => $options,
					'URL'=> $lastUrl
				)
			);
			if($configurations->getBool($confId.'throwSolrSearchException')) {
				throw $e;
			}
			return null;
		}
		return $item;
	}

	/**
	 * returns the dataset for the current used index
	 *
	 * @throws Exception
	 * @return tx_mksearch_service_internal_Index
	 */
	protected function getIndex() {
		$indexUid = $this->getConfigurations()->get($this->getConfId(). 'usedIndex');
		//let's see if we got a index to use via parameters
		if(empty($indexUid))
			$indexUid = $this->getConfigurations()->getParameters()->get('usedIndex');

		$index = tx_mksearch_util_ServiceRegistry::getIntIndexService()->get($indexUid);

		if(!$index->isValid())
			throw new Exception('Configured search index not found!');

		return $index;
	}

	function getTemplateName() { return 'showhit';}
	function getViewClassName() { return 'tx_mksearch_view_ShowHit';}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/action/class.tx_mksearch_action_ShowHit.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/action/class.tx_mksearch_action_ShowHit.php']);
}
