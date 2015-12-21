<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_indexer
 *  @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2011 Hannes Bochmann <dev@dmk-ebusiness.de>
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
 */

/**
 * benötigte Klassen einbinden
 */

tx_rnbase::load('tx_mksearch_indexer_Base');
tx_rnbase::load('tx_mksearch_service_indexer_core_Config');
tx_rnbase::load('tx_mksearch_util_Misc');

/**
 * Indexer service for core.tt_content called by the "mksearch" extension.
 */
class tx_mksearch_indexer_Page extends tx_mksearch_indexer_Base {

	/**
	 * Return content type identification.
	 * This identification is part of the indexed data
	 * and is used on later searches to identify the search results.
	 * You're completely free in the range of values, but take care
	 * as you at the same time are responsible for
	 * uniqueness (i.e. no overlapping with other content types) and
	 * consistency (i.e. recognition) on indexing and searching data.
	 *
	 * @return array([extension key], [key of content type])
	 */
	public static function getContentType() {
		return array('core', 'page');
	}

/**
	 * (non-PHPdoc)
	 * @see tx_mksearch_interface_Indexer::prepareSearchData()
	 */
	public function prepareSearchData($tableName, $sourceRecord, tx_mksearch_interface_IndexerDocument $indexDoc, $options) {
		return parent::prepareSearchData($tableName, $sourceRecord, $indexDoc, $options);
	}

	/**
	 * check if we have a shortcut and index the target instead
	 * @see tx_mksearch_indexer_Base::stopIndexing()
	 */
	protected function stopIndexing($tableName, $rawData, tx_mksearch_interface_IndexerDocument $indexDoc, $options) {
		if($tableName == 'pages') {
			// this our first entry point. so we fetch all subpages and put them into
			// the queue in case changes on this page have effects on subpages.
			$aPidList = explode(',',$this->_getPidList($rawData['uid'],999));

			// the last element is always the page itself. so we can pop this.
			array_pop($aPidList);

			$oIndexSrv = tx_mksearch_util_ServiceRegistry::getIntIndexService();
			foreach ($aPidList as $iUid) {
				$oIndexSrv->addRecordToIndex($tableName, $iUid);
			}

			// Current page is a short cut? Follow up short-cutted page
			if($rawData['doktype'] == 4) {
				$oIndexSrv->addRecordToIndex('pages', $rawData['shortcut']);
				return true;
			}
		}
		//else
		return parent::stopIndexing($tableName, $rawData, $indexDoc, $options);
	}

	/**
	 * @see tx_mksearch_indexer_Base::indexData()
	 */
	public function indexData(tx_rnbase_IModel $oModel, $tableName, $rawData, tx_mksearch_interface_IndexerDocument $indexDoc, $options) {
		$lang = isset($this->options['lang'])? $this->options['lang'] : 0;

		// Localize record, if necessary
		if ($lang) {
			$page = tx_rnbase_util_TYPO3::getSysPage();
			$rawData = $page->getPageOverlay ($rawData, $lang);
			// No success in record translation?
			if (!isset($rawData['_PAGES_OVERLAY'])) return null;
		}
		// Fill indexer document
		$indexDoc->setUid($rawData['uid']);
		$indexDoc->setTitle($rawData['title']);
		$indexDoc->setTimestamp($rawData['tstamp']);

		// You are strongly encouraged to use $doc->getMaxAbstractLength() to limit the length of your abstract!
		// You are indeed free to ignore that limit if you have good reasons to do so, but always
		// keep in mind that the abstract data is stored with the indexed document - so think about your data traffic
		// and last but not least about the index size!
		// You may define the limit for your content type centrally with the key
		// "abstractMaxLength_[your extkey]_[your content type]" as defined at $indexDoc constructor
		if(!empty($oModel->record['abstract']))
			$indexDoc->setAbstract(
					$options['keepHtml'] ? $oModel->record['abstract'] : tx_mksearch_util_Misc::html2plain($oModel->record['abstract']),
					$indexDoc->getMaxAbstractLength()
			);

		//now let's use the given mapping to get the fields separatly into solr
		if(!empty($options['mapping.']))
			$this->indexModelByMapping($oModel,$options['mapping.'],$indexDoc,'',$options);

		return $indexDoc;
	}

	/**
	 * @see tx_mksearch_indexer_Base::isIndexableRecord()
	 * @todo Backendbenutzerbereiche nicht indizieren
	 */
	protected function isIndexableRecord(array $sourceRecord, array $options) {
		//as those functions check via the pid we have to copy the uid of the
		//current page into the pid this is what we want to check
		$sourceRecord['pid'] = $sourceRecord['uid'];
		return $this->isOnIndexablePage($sourceRecord,$options);
	}

	/**
	 * Returns the model to be indexed
	 *
	 * @param array $aRawData
	 *
	 * @return tx_mksearch_model_irfaq_Question
	 */
	protected function createModel(array $aRawData) {
		$oModel = tx_rnbase::makeInstance('tx_rnbase_model_Base', $aRawData);

		return $oModel;
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
	 * Return the default Typoscript configuration for this indexer.
	 *
	 * Note that this config is not used for actual indexing
	 * but only serves as assistance when actually configuring an indexer!
	 * Hence all possible configuration options should be set or
	 * at least be mentioned to provide an easy-to-access inline documentation!
	 *
	 * @return string
	 *
	 */
	/**
	 * Return the default Typoscript configuration for this indexer.
	 *
	 * @return string
	 */
	public function getDefaultTSConfig() {
		return <<<CONF
# Fields which are set statically to the given value
# Don't forget to add those fields to your Solr schema.xml
# For example it can be used to define site areas this
# contentType belongs to
#
# fixedFields {
#	my_fixed_field_singlevalue = first
#   my_fixed_field_multivalue {
#      0 = first
#      1 = second
#   }
# }

# the mapping which record field should be put into which solr field
 mapping {
	nav_title = nav_title_s
	subtitle = subtitle_s
#	my_record_field = my_solr_field
}

### delete from or abort indexing for the record if isIndexableRecord or no record?
deleteIfNotIndexable = 0

# Note: you should always configure the root pageTree for this indexer in the includes. mostly the domain
# White lists: Explicitely include items in indexing by various conditions.
# Note that defining a white list deactivates implicite indexing of ALL pages,
# i.e. only white-listed pages are defined yet!
# May also be combined with option "exclude"
include {
# Include several pages in indexing:
#		# Include page #18 and #27
#	pages = 18,27
# Include complete page trees (i. e. pages with all their children) in indexing:
#	pageTrees {
#		# Include page tree with root page #19
#		0 = 19
#		# Include page  tree with root page #28
#		1 = 28
#	}
}
# Black lists: Exclude pages from indexing by various conditions.
# May also be combined with option "include", while "exclude" option
# takes precedence over "include" option.
exclude {
	# Exclude several pages from indexing. @see respective include option
#	pages ...
 	# Exclude complete page trees (i. e. pages with all their children) from indexing.
 	# @see respective include option
#	pageTrees ...
}
CONF;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_TtContent.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_TtContent.php']);
}