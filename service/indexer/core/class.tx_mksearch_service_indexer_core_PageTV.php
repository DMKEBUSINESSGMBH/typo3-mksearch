<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Lars Heber <lars.heber@das-medienkombinat.de>
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
tx_rnbase::load('tx_mksearch_service_indexer_core_Page');

/**
 * Indexer service for templavoila.page called by the "mksearch" extension.
 *
 * This class extends tx_mksearch_service_indexer_core_Page in the way that
 * it is aware of some TemplaVoilà-specific things like references. 
 * 
 * IMPORTANT NOTES:
 * * The content type of indexed pages stays unchanged, meaning that
 *   all TypoScript configuration related to displaying search results 
 *   refers to 'core.page'. As a consequence, the default search result
 *   configuration of tx_mksearch_service_indexer_core_Page is re-used.
 * * The subtype of this indexer service (which is used for activation and
 *   configuration) of course MUST be unique and thus differs from
 *   tx_mksearch_service_indexer_core_Page's service's subtype.
 *   THIS class's service's subtype is 'templavoila.page'.
 * * This indexer MUST be called before 'core.tt_content' as 'core.tt_content'
 *   configuration may be changed by this class!
 * * There is no need to explicitely configure include options for the
 *   'core.tt_content' indexer service as the content elements to be indexed
 *   are determined by THIS 'templavoila.page' service.
 *   Note that implicitely indexing ALL 'core.tt_content' documents is 
 *   not possible when combinated with THIS 'templavoila.page' service.
 *   Use the include.pageTrees option instead with the page root as needed.
 * * This indexer must be activated explicitely as needed (i.e. if TemplaVoilà
 *   is installed). Make sure to DEACTIVATE the 'core.page' indexer service at the same time!
 *     1. $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['active'][] = 'templavoila.page';
 *     2. $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['active'] = array_diff($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['active'], array('core.page'));
 * 
 * Options are identical to tx_mksearch_service_indexer_core_Page (except the key / service subtype!), 
 * e.g. $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config']['templavoila.page']['indexedFields'] = array('header', 'bodytext');
 * @see tx_mksearch_service_indexer_core_Page
 */
class tx_mksearch_service_indexer_core_PageTV extends tx_mksearch_service_indexer_core_Page {
	
	/**
	 * Special TV fields needed in background, but not for actual indexing
	 *
	 * @var unknown_type
	 */
	private $tvFields = array('tx_templavoila_flex');
	
	/**
	 * Shortcut to templavoila.tt_content include pages configuration
	 *
	 * @var array
	 */
	private $ttcConfInclude = array();
	
	/**
	 * Shortcut to templavoila.tt_content mapping configuration
	 *
	 * @var array
	 */
	private $ttcConfMap = array();
	
	/**
	 * Get sql data necessary to grab data to be indexed from data base 
	 * 
	 * @param array $options from service configuration
	 * @return array
	 * @see tx_mksearch_service_indexer_BaseDataBase::getSqlData()
	 */
	protected function getSqlData(array $options) {
		$this->ttcConfInclude = &$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config']['templavoila.tt_content']['include']['elements'];
		if (!is_array($this->ttcConfInclude)) $this->ttcConfInclude = array(); 
		$this->ttcConfMap = &$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config']['templavoila.tt_content']['pidMapping'];
		if (!is_array($this->ttcConfMap)) $this->ttcConfMap = array(); 
		
		$this->baseFields = array_merge($this->baseFields, $this->tvFields);		
		return parent::getSqlData($options);
	}
	
	/**
	 * Prepare / transform data from database for indexing
	 *
	 * @param array								$rawData
	 * @param array 							$options from service configuration
	 * @param tx_mksearch_model_IndexerDocument	$indexDoc Model to be filled
	 * @return tx_mksearch_model_IndexerDocument or null, if record is not to be indexed
	 */
	protected function prepareData(array $rawData, array $options, tx_mksearch_model_IndexerDocument $indexDoc) {
		if ($rawData['tx_templavoila_flex']) {
			// Get tt_content uids and add them to templavoila.tt_content indexer config
			$flex = t3lib_div::xml2array($rawData['tx_templavoila_flex']);
			$uids = t3lib_div::trimExplode(',', $flex['data']['sDEF']['lDEF']['field_content']['vDEF'], 1);
							
			$this->ttcConfInclude = array_merge($this->ttcConfInclude, $uids);

			// Fill templavoila.tt_content indexer PID mapping
			if ($uids) {
				foreach ($uids as $uid) {
					if (!isset($this->ttcConfMap[$uid])) $this->ttcConfMap[$uid] = array();
					$this->ttcConfMap[$uid][] = $rawData['uid'];
				} 	
			}
		}
		// Call parent's method
		return parent::prepareData($rawData, $options, $indexDoc);
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/service/indexer/core/class.tx_mksearch_service_indexer_core_PageTV.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/service/indexer/core/class.tx_mksearch_service_indexer_core_PageTV.php']);
}