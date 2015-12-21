<?php
/**
 * @package tx_mksearch
 * @subpackage tx_mksearch_action
 *
 * Copyright notice
 *
 * (c) 2013 DMK E-Business GmbH <dev@dmk-ebusiness.de>
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


tx_rnbase::load('tx_rnbase_action_BaseIOC');
tx_rnbase::load('tx_mksearch_util_ServiceRegistry');

/**
 * Detailseite eines beliebigen Datensatzes aus Momentan Lucene oder Solr.
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_action
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_action_ShowHit extends tx_rnbase_action_BaseIOC {

	/**
	 *
	 * @var tx_mksearch_model_internal_Index
	 */
	private $index = false;

	/**
	 *
	 *
	 * @param tx_rnbase_IParameters $parameters
	 * @param tx_rnbase_configurations $configurations
	 * @param ArrayObject $viewdata
	 * @return string Errorstring or null
	 */
	protected function handleRequest(&$parameters, &$configurations, &$viewdata) {
		$item = $this->findItem();
		$viewdata->offsetSet('item', $item);
		return null;
	}

	/**
	 *
	 * @throws InvalidArgumentException
	 * @throws Ambigous <Exception, LogicException, LogicException, tx_mksearch_service_engine_SolrException>
	 * @return tx_mksearch_interface_SearchHit
	 */
	protected function findItem() {
		$item = null;
		$configurations = $this->getConfigurations();
		$confId = $this->getConfId();

		$extKey = $configurations->get($confId.'extkey');
		$contentType = $configurations->get($confId.'contenttype');
		$uid = $configurations->get($confId.'uid');
		if (!$uid) {
			$uidParamName = $configurations->get($confId.'uidParamName');
			$uidParamName = $uidParamName ? $uidParamName : 'item';
			$uid = $configurations->getParameters()->getInt($uidParamName);
		}

		tx_rnbase_util_Misc::callHook(
			'mksearch',
			'action_showhit_finditem_pre',
			array(
				'ext_key' => &$extKey,
				'content_type' => &$contentType,
				'uid' => &$uid,
			),
			$this
		);

		$item = $this->searchByContentUid($uid, $extKey, $contentType);

		tx_rnbase_util_Misc::callHook(
			'mksearch',
			'action_showhit_finditem_post',
			array(
				'ext_key' => &$extKey,
				'content_type' => &$contentType,
				'uid' => &$uid,
				'item' => &$item,
			),
			$this
		);

		if (is_null($item)) {
			throw new LogicException(
				'No hit found for "'.$extKey.':'.$contentType.':'.$uid.'" in index "'.$this->getIndex()->getUid().'".',
				1377774172
			);
		}
		if (!$item instanceof tx_mksearch_interface_SearchHit) {
			throw new LogicException(
				'The hit has to be an object instance of "tx_mksearch_interface_SearchHit",'
				. '"' . (is_object($item) ? get_class($item) : gettype($item)) . '" given.',
				1377774178
			);
		}

		return $item;
	}

	/**
	 *
	 * @param integer $uid
	 * @param string $extKey
	 * @param string $contentType
	 * @throws Ambigous <Exception, InvalidArgumentException, tx_mksearch_service_engine_SolrException>
	 * @return
	 */
	protected function searchByContentUid($uid, $extKey, $contentType) {
		$item = null;

		if (!($extKey && $contentType && $uid)) {
			throw new InvalidArgumentException(
				'Missing Parameters. extkey, contenttype and item are required.',
				1370429706
			);
		}

		try {
			// in unserem fall sollte es der solr service sein!
			/* @var $searchEngine tx_mksearch_service_engine_Solr */
			$searchEngine = tx_mksearch_util_ServiceRegistry::getSearchEngine($this->getIndex());
			$searchEngine->openIndex($this->getIndex());
			$item = $searchEngine->getByContentUid($uid, $extKey, $contentType);
			$searchEngine->closeIndex();
		}
		catch(Exception $e) {
			$lastUrl = $e instanceof tx_mksearch_service_engine_SolrException ? $e->getLastUrl() : '';
			//Da die Exception gefangen wird, würden die Entwickler keine Mail bekommen
			//also machen wir das manuell
			if($addr = tx_rnbase_configurations::getExtensionCfgValue('rn_base', 'sendEmailOnException')) {
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
			$configurations = $this->getConfigurations();
			if($configurations->getBool($confId.'throwSolrSearchException')) {
				throw $e;
			}
		}

		return $item;
	}

	/**
	 * returns the dataset for the current used index
	 *
	 * @throws Exception
	 * @return tx_mksearch_model_internal_Index
	 */
	protected function getIndex() {
		if ($this->index === false) {
			$indexUid = $this->getConfigurations()->get($this->getConfId(). 'usedIndex');
			//let's see if we got a index to use via parameters
			if(empty($indexUid))
				$indexUid = $this->getConfigurations()->getParameters()->get('usedIndex');

			$index = tx_mksearch_util_ServiceRegistry::getIntIndexService()->get($indexUid);

			if(!$index->isValid())
				throw new Exception('Configured search index not found!');

			$this->index = $index;
		}
		return $this->index;
	}

	function getTemplateName() {
		return 'showhit';
	}

	function getViewClassName() {
		return 'tx_mksearch_view_ShowHit';
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/action/class.tx_mksearch_action_ShowHit.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/action/class.tx_mksearch_action_ShowHit.php']);
}
