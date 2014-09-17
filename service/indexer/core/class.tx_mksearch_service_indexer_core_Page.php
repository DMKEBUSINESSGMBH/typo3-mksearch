<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Lars Heber <dev@dmk-ebusiness.de>
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
tx_rnbase::load('tx_mksearch_interface_Indexer');
tx_rnbase::load('tx_mksearch_service_indexer_core_Config');
tx_rnbase::load('tx_mksearch_util_Misc');

/**
 * Indexer service for core.page called by the "mksearch" extension.
 * 
 * The page's metadata field "abstract" is used for search result abstract.
 * 
 * Expected option for indexer configuration:
 * * indexedFields (mandatory - set by default):
 *		Define which fields are used for building the index
 * 		E.g. $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config']['core.page']['indexedFields'] = array('header', 'bodytext');
 * * lang (optional):
 * 		$sys_language_uid of the desired language
 * 		E.g. $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config']['core.page']['lang'] = 1;
 * * include (optional):
 *		Several white lists: Explicitely pages from indexing by various conditions.
 * 		Note that defining a white list deactivates implicite indexing of ALL pages,
 *		i.e. only white-listed pages are defined yet!
 *		May also be combined with option "exclude"
 * 		* [array] pages:
 * 			Include several pages in indexing.
 * 		 	E.g. $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config']['core.page']['include']['pages'] = array(2, 4);
 * 		* [array] pageTrees:
 * 			Include complete page trees (i. e. pages with all their children) in indexing
 * 		 	E.g. $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config']['core.page']['include']['pageTrees'] = array(3, 5, 8);
 * * exclude (optional):
 *		Several black lists: Exclude pages from indexing by various conditions.
 * 		May also be combined with option "include"
 * 		* [array] pages:
 * 			Exclude several pages from indexing.
 * 		 	E.g. $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config']['core.page']['exclude']['pages'] = array(2, 4);
 * 		* [array] pageTrees:
 * 			Exclude complete page trees (i. e. pages with all their children) from indexing
 * 		 	E.g. $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config']['core.page']['exclude']['pageTrees'] = array(3, 5, 8);
 *
 */
class tx_mksearch_service_indexer_core_Page implements tx_mksearch_interface_Indexer {
	
	/**
	 * Array of processed uids.
	 * Used to avoid multiple indexing of the same page
	 * which can happen e.g. on processing page shortcuts.
	 *
	 * @var array
	 */
	protected $processedUids = array();
	
	/**
	 * Array of page uids which are to be processed
	 * in a follow-up db-query
	 * @see parent::getFollowUpSqlData()
	 *
	 * @var array
	 */
	protected $additionalUids = array();
	
	/**
	 * Mandatory data base fields 
	 *
	 * @var array
	 */
	protected $baseFields = array('uid', 'title', 'tstamp', 'abstract', 'doktype', 'shortcut');
	
	/**
	 * Return content type identification.
	 * This identification is part of the indexed data
	 * and is used on later searches to identify the search results.
	 * You're completely free in the range of values, but take care
	 * as you at the same time are responsible for
	 * uniqueness (i.e. no overlapping with other content types) and 
	 * consistency (i.e. recognition) on indexing and searching data. 
	 *
	 * @return array('extKey' => [extension key], 'name' => [key of content type]
	 */
	public static function getContentType() {
		return array('core', 'page');
	}
	
	/**
	 * Get sql data necessary to grab data to be indexed from data base 
	 * TODO: Check if parameter data is necessary for this indexer
	 * 
	 * @param array $options from service configuration
	 * @param array $data		Tablename <-> uids matrix of records to be indexed (array('tab1' => array(2,5,6), 'tab2' => array(4,5,8))
	 * @return array
	 * @see tx_mksearch_service_indexer_BaseDataBase::getSqlData()
	 */
	protected function getSqlData(array $options, array $data=array()) {
		if (!isset($options['indexedFields']))
			throw new Exception('tx_mksearch_service_indexer_core_Page->getSqlData(): mandatory option "indexedFields" not set!');
		
		$fields = array_unique(array_merge($this->baseFields, $options['indexedFields']));
		
		$where = 	// restrict page types to FE relevant ones
					'doktype <= 5' . 
					// Include / exclude restrictions
					tx_mksearch_service_indexer_core_Config::getIncludeExcludeWhere(
						isset($options['include']) ? $options['include'] : array(),
						isset($options['exclude']) ? $options['exclude'] : array()
					);
		
		return array(
					'fields' => implode(',', $fields), 
					'table' => 'pages',
					'where' => $where,	
					'skipEnableFields' => array('fe_group'), 
				);
	}
	
	/**
	 * Get sql data for an optional follow-up data base query
	 * 
	 * @param array $options from service configuration
	 * @return null | array
	 * @see self::getSqlData()
	 */
	protected function getFollowUpSqlData(array $options) {
		// Remove all pages from $this->additionalUids which have still been processed earlier. 
		$this->additionalUids = array_diff($this->additionalUids, $this->processedUids);
		
		// No additionalUids?
		if (!$this->additionalUids) return null;
		// else:

		// Retain options, but update include option
		$options['include'] = array('pages' => $this->additionalUids);
		
		// Finally, delete $this->additionalUids 
		$this->additionalUids = array();
		
		return $this->getSqlData($options);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see tx_mksearch_interface_Indexer::prepareSearchData()
	 */
	public function prepareSearchData($tableName, $sourceRecord, tx_mksearch_interface_IndexerDocument $indexDoc, $options) {
		return $this->prepareData($sourceRecord, $options, $indexDoc);
	}

	/**
	 * Prepare / transform data from database for indexing
	 *
	 * @param array								$rawData
	 * @param array 							$options from service configuration
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc Model to be filled
	 * @return tx_mksearch_interface_IndexerDocument or null, if record is not to be indexed
	 */
	protected function prepareData(array $rawData, array $options, tx_mksearch_interface_IndexerDocument $indexDoc) {
		// Avoid multiple indexing of the same page
		if (in_array($rawData['uid'], $this->processedUids)) return null;
		// else:
		
		$this->processedUids[] = $rawData['uid'];
		
		// Current page is a short cut? Follow up short-cutted page
		if ($rawData['doktype'] == 4) {
			$this->additionalUids[] = $rawData['shortcut'];
		}
		
		$lang = isset($this->options['lang'])? $this->options['lang'] : 0;
		
		// Localize record, if necessary
		if ($lang) {
			$page = t3lib_div::makeInstance('t3lib_pageSelect');
			$rawData = $page->getPageOverlay ($rawData, $lang);
			// No success in record translation?
			if (!isset($rawData['_PAGES_OVERLAY'])) return null;
		}
		// Fill indexer document
		$indexDoc->setUid($rawData['uid']);
		$indexDoc->setTitle($rawData['title']);
		$indexDoc->setTimestamp($rawData['tstamp']);
		
		$content = '';
		// Index all fields according to configuration
		if(!empty($options['indexedFields']))
			foreach ($options['indexedFields'] as $field) 
				if (!empty($rawData[$field])) $content .= $rawData[$field] . ' ';
			
		// Decode HTML 
		if(!empty($content))
			$indexDoc->setContent(tx_mksearch_util_Misc::html2plain($content));
		
		// You are strongly encouraged to use $doc->getMaxAbstractLength() to limit the length of your abstract!
		// You are indeed free to ignore that limit if you have good reasons to do so, but always
		// keep in mind that the abstract data is stored with the indexed document - so think about your data traffic
		// and last but not least about the index size!
		// You may define the limit for your content type centrally with the key
		// "abstractMaxLength_[your extkey]_[your content type]" as defined at $indexDoc constructor
		if(!empty($rawData['abstract']))
			$indexDoc->setAbstract(
									tx_mksearch_util_Misc::html2plain($rawData['abstract']),
									$indexDoc->getMaxAbstractLength()
								);
		
		return $indexDoc;
	}
	public function getDefaultTSConfig() {
		return 'testPAGE';
	}

}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/service/indexer/core/class.tx_mksearch_service_indexer_core_Page.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/service/indexer/core/class.tx_mksearch_service_indexer_core_Page.php']);
}