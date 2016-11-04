<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2016 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
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
 ***************************************************************/

tx_rnbase::load('tx_mksearch_indexer_ttcontent_Normal');

/**
 * Gridelements indexer
 *
 * @package TYPO3
 * @subpackage DMK\Mkleipzig
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_indexer_ttcontent_Gridelements
	extends tx_mksearch_indexer_ttcontent_Normal
{
	/**
	 * Sets the index doc to deleted if neccessary
	 *
	 * @param tx_rnbase_IModel $oModel
	 * @param tx_mksearch_interface_IndexerDocument $oIndexDoc
	 * @param array $aOptions
	 *
	 * @return bool
	 */
	// @codingStandardsIgnoreStart (interface/abstract mistake)
	protected function hasDocToBeDeleted(
		tx_rnbase_IModel $oModel,
		tx_mksearch_interface_IndexerDocument $oIndexDoc,
		$aOptions = array()
	) {
		// @codingStandardsIgnoreEnd
		// should the element be removed from the index?
		if ((
			// only for gridelements? no, other elements should be deleted too!
			// $this->isGridelement($oModel->getRecord()) &&
			// only if not directly set do indexable or not indexable!
			$oModel->getTxMksearchIsIndexable() == self::USE_INDEXER_CONFIGURATION &&
			// only, if there are a parent container
			$oModel->getTxGridelementsContainer() > 0
		)) {
			// add the parent do index, so the changes are writen to index
			$this->addGridelementsContainerToIndex($oModel);

			return true;
		}

		return $this->hasNonGridelementDocToBeDeleted($oModel, $oIndexDoc, $aOptions);
	}

	/**
	 * Sets the index doc to deleted if neccessary
	 *
	 * @param tx_rnbase_IModel $oModel
	 * @param tx_mksearch_interface_IndexerDocument $oIndexDoc
	 * @param array $aOptions
	 *
	 * @return bool
	 */
	// @codingStandardsIgnoreStart (interface/abstract mistake)
	protected function hasNonGridelementDocToBeDeleted(
		tx_rnbase_IModel $oModel,
		tx_mksearch_interface_IndexerDocument $oIndexDoc,
		$aOptions = array()
	) {
		// @codingStandardsIgnoreEnd
		return parent::hasDocToBeDeleted($oModel, $oIndexDoc, $aOptions);
	}

	/**
	 * Adds the parent to index
	 *
	 * @param tx_rnbase_IModel $oModel
	 *
	 * @return void
	 */
	protected function addGridelementsContainerToIndex(
		tx_rnbase_IModel $oModel
	) {
		// add the parent do index, so the changes are writen to index
		$indexSrv = tx_mksearch_util_ServiceRegistry::getIntIndexService();
		$indexSrv->addRecordToIndex(
			'tt_content',
			$oModel->getTxGridelementsContainer()
		);
	}

	/**
	 * Get the content by CType
	 *
	 * @param array $rawData
	 *
	 * @return string
	 */
	protected function getContentByContentType(
		array $rawData
	) {
		if (!$this->isGridelement($rawData)) {
			return '';
		}

		return $this->getGridelementElementContent($rawData);
	}

	/**
	 * Is the given record an gridelement?
	 *
	 * @param array $rawData
	 *
	 * @return bool
	 */
	protected function isGridelement(
		array $rawData
	) {
		return (
			tx_rnbase_util_Extensions::isLoaded('gridelements') &&
			$rawData['CType'] == 'gridelements_pi1'
		);
	}

	/**
	 * Fetches the content of an grid element
	 *
	 * @param array $record
	 *
	 * @return string
	 */
	protected function getGridelementElementContent(
		array $record
	) {
		$this->initTsForFrontend($record['pid']);

		$uid = $this->getUid('tt_content', $record, array());

		/* @var $gridelements \GridElementsTeam\Gridelements\Plugin\Gridelements */
		$gridelements = tx_rnbase::makeInstance(
			'\GridElementsTeam\\Gridelements\\Plugin\\Gridelements'
		);
		$gridelements->cObj = tx_rnbase::makeInstance(
			tx_rnbase_util_Typo3Classes::getContentObjectRendererClass()
		);
		$gridelements->cObj->data = $record;
		$gridelements->cObj->currentRecord = 'tt_content:' . $uid;

		return $gridelements->main('', array());
	}

	/**
	 * Initializes the tsfe for the tv elements
	 *
	 * @param int $pid
	 *
	 * @return void
	 */
	private function initTsForFrontend($pid)
	{
		$tsfe = tx_rnbase_util_Misc::prepareTSFE(
			array(
				'force' => true,
				'pid'	=> $pid
			)
		);

		// add cobject for some plugins like tt_news
		$tsfe->cObj = tx_rnbase::makeInstance(
			tx_rnbase_util_Typo3Classes::getContentObjectRendererClass()
		);

		tx_rnbase::load('tx_mksearch_service_indexer_core_Config');
		$rootlineByPid = tx_mksearch_service_indexer_core_Config::getRootLine($pid);

		// disable cache for be indexing!
		$tsfe->no_cache = true;
		$tsfe->tmpl->start($rootlineByPid);
		$tsfe->rootLine = $rootlineByPid;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/ttcontent/class.tx_mksearch_indexer_ttcontent_Gridelements.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/ttcontent/class.tx_mksearch_indexer_ttcontent_Gridelements.php']);
}
