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

tx_rnbase::load('tx_mksearch_interface_Indexer');
tx_rnbase::load('tx_mksearch_interface_IndexerDocument');
tx_rnbase::load('tx_mksearch_util_Misc');
tx_rnbase::load('tx_mksearch_util_TCA');
tx_rnbase::load('tx_mksearch_service_indexer_core_Config');
tx_rnbase::load('tx_mksearch_util_Indexer');

/**
 * Base indexer class offering some common methods
 * to make the object orientated indexing ("indexing by models")
 * more comfortable
 *
 * @TODO: no_search in der pages tabelle beachten!
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_indexer
 */
abstract class tx_mksearch_indexer_Base
	implements tx_mksearch_interface_Indexer {

	/**
	 * @var tx_rnbase_IModel
	 */
	protected $modelToIndex;

	/**
	 * Prepare a searchable document from a source record.
	 *
	 * @param string $tableName
	 * @param array $rawData
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 *        Indexer document to be "filled",
	 *        instantiated based on self::getContentType()
	 * @param array $options | enthält die indexer konfig aber auch den queueRecord
	 * @return tx_mksearch_interface_IndexerDocument|null
	 *         return null if nothing should be indexed!
	 */
	public function prepareSearchData($tableName, $rawData, tx_mksearch_interface_IndexerDocument $indexDoc, $options) {

		// Set base id for specific indexer.
		// @TODO use getUid of the model as it will return the correct uid in respect of localisation
		$indexDoc->setUid($this->getUid($tableName, $rawData, $options));

		// pre process hoock
		tx_rnbase_util_Misc::callHook(
			'mksearch',
			'indexerBase_preProcessSearchData',
			array(
				'table' => $tableName,
				'rawData' => &$rawData,
				'indexDoc' => &$indexDoc,
				'options' => $options,
			),
			$this
		);
		// check, if the doc was skiped or has to deleted
		if (is_null($indexDoc) || $indexDoc->getDeleted()) {
			return $indexDoc;
		}

		// when an indexer is configured for more than one table
		// the index process may be different for the tables.
		// overwrite this method in your child class to stop processing and
		// do something different like putting a record into the queue.
		if ($this->stopIndexing($tableName, $rawData, $indexDoc, $options)) {
			return NULL;
		}

		// get a model from the source array
		$this->modelToIndex = $this->createModel($rawData, $tableName, $options);

		// Is the model valid and data indexable?
		if (
			!$this->modelToIndex->record
			|| !$this->isIndexableRecord($this->modelToIndex->record, $options)
		) {
			if (
				isset($options['deleteIfNotIndexable'])
				&& $options['deleteIfNotIndexable']
			) {
				$indexDoc->setDeleted(TRUE);
				return $indexDoc;
			} else {
				return NULL;
			}
		}

		// shall we break the indexing and set the doc to deleted?
		if ($this->hasDocToBeDeleted($this->modelToIndex, $indexDoc, $options)) {
			$indexDoc->setDeleted(TRUE);
			return $indexDoc;
		}

		$indexDoc = $this->indexEnableColumns(
			$this->modelToIndex, $tableName, $indexDoc
		);
		$indexDoc = $this->indexSiteRootPage(
			$this->modelToIndex, $tableName, $indexDoc, $options
		);
		$indexDoc = $this->indexData(
			$this->modelToIndex, $tableName, $rawData, $indexDoc, $options
		);

		// post precess hock
		tx_rnbase_util_Misc::callHook(
			'mksearch',
			'indexerBase_postProcessSearchData',
			array(
				'table' => $tableName,
				'rawData' => &$rawData,
				'indexDoc' => &$indexDoc,
				'options' => $options,
				'model' => &$this->modelToIndex,
			),
			$this
		);

		return $indexDoc;
	}

	/**
	 * Liefert die UID des Datensatzes
	 *
	 * @param string $tableName
	 * @param array $rawData
	 * @param array $options
	 * @return int
	 *
	 * @deprecated use tx_mksearch_util_Indexer::getInstance()->getRecordsUid
	 */
	protected function getUid($tableName, $rawData, $options) {
		$options = is_array($options) ? $options : array();
		return tx_mksearch_util_Indexer::getInstance()->getRecordsUid($tableName, $rawData, $options);
	}

	/**
	 * Shall we break the indexing for the current data?
	 *
	 * when an indexer is configured for more than one table
	 * the index process may be different for the tables.
	 * overwrite this method in your child class to stop processing and
	 * do something different like putting a record into the queue
	 * if it's not the table that should be indexed
	 *
	 * @param string $tableName
	 * @param array $sourceRecord
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @param array $options
	 * @return bool
	 */
	protected function stopIndexing(
		$tableName, $sourceRecord,
		tx_mksearch_interface_IndexerDocument $indexDoc,
		$options
	) {
		return $this->getIndexerUtility()->stopIndexing(
			$tableName, $sourceRecord, $indexDoc, $options
		);
	}

	/**
	 * @return tx_mksearch_util_Indexer
	 */
	protected function getIndexerUtility() {
		return tx_rnbase::makeInstance('tx_mksearch_util_Indexer');
	}

	/**
	* Indexes all fields of the model according to the given mapping
	*
	* @param tx_rnbase_IModel $model
	* @param array $recordIndexMapping
	* @param tx_mksearch_interface_IndexerDocument $indexDoc
	* @param string $prefix
	* @param array $options
	* @param boolean $dontIndexHidden
	* @return void
	*/
	protected function indexModelByMapping(
		tx_rnbase_IModel $model, array $recordIndexMapping,
		tx_mksearch_interface_IndexerDocument $indexDoc,
		$prefix = '', array $options = array(), $dontIndexHidden = TRUE
	) {
		$indexDoc =
			tx_mksearch_util_Indexer::getInstance()->indexModelByMapping(
				$model, $recordIndexMapping, $indexDoc,
				$prefix, $options, $dontIndexHidden
			);
	}


	/**
	 * Collects the values of all models inside the given array
	 * and adds them as multivalue (array)
	 *
	 * @param array $models array of tx_rnbase_IModel
	 * @param array $recordIndexMapping
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @param string $prefix
	 * @param array $options
	 * @param boolean $dontIndexHidden
	 * @return void
	 */
	protected function indexArrayOfModelsByMapping(
		array $models, array $recordIndexMapping,
		tx_mksearch_interface_IndexerDocument $indexDoc,
		$prefix = '', array $options = array(), $dontIndexHidden = TRUE
	) {
		$indexDoc =
			tx_mksearch_util_Indexer::getInstance()->indexArrayOfModelsByMapping(
				$models, $recordIndexMapping, $indexDoc,
				$prefix, $options, $dontIndexHidden
			);
	}

	/**
	 * Sets the index doc to deleted if neccessary
	 *
	 * @param tx_rnbase_model_base $model
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @param array $options
	 * @return bool
	 */
	protected function hasDocToBeDeleted(
		tx_rnbase_IModel $model, tx_mksearch_interface_IndexerDocument $indexDoc,
		$options = array()
	) {
		if (
			!$model ||
			!$model->isValid() ||
			$model->isHidden() ||
			$model->isDeleted()
		) {
			return TRUE;
		}

		// are our parent pages valid?
		// as soon as one of the parent pages is hidden we return true.
		// @todo support when a parent page is deleted! shouldn't be possible
		// without further configuration for a BE user but it's still possible!

		// wir rufen die Methode mit call_user_func_array auf, da sie
		// statisch ist, womit wir diese nicht mocken könnten
		$rootline = call_user_func_array(
			array($this->getCoreConfigUtility(), 'getRootLine'),
			array($model->record['pid'])
		);

		// @todo sollten nicht auch Shortcuts etc. invalide sein?
		$sysPage = tx_rnbase_util_TYPO3::getSysPage();
		foreach ($rootline as $page) {
			if (
				$page['hidden'] ||
				($page['doktype'] == $sysPage::DOKTYPE_BE_USER_SECTION)
			) {
				return TRUE;
			}
		}
		// else
		return FALSE;
	}

	/**
	 * @return string
	 */
	protected function getCoreConfigUtility() {
		return tx_mksearch_service_indexer_core_Config;
	}

	/**
	 * Prüft ob das Element speziell in einem Seitenbaum oder auf einer Seite liegt,
	 * der/die inkludiert oder ausgeschlossen werden soll.
	 * Der Entscheidungsbaum dafür ist relativ, sollte aber durch den Code
	 * illustriert werden.
	 *
	 * @param array $sourceRecord
	 * @param array $options
	 * @return bool
	 */
	protected function isIndexableRecord(array $sourceRecord, array $options) {
		return $this->isOnIndexablePage($sourceRecord, $options);
	}

	/**
	 * Indiziert alle eneblecolumns.
	 *
	 * @param tx_rnbase_IModel $model
	 * @param string $tableName
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @param string $indexDocFieldsPrefix
	 * @return tx_mksearch_interface_IndexerDocument
	 */
	protected function indexEnableColumns(
		tx_rnbase_IModel $model, $tableName,
		tx_mksearch_interface_IndexerDocument $indexDoc,
		$indexDocFieldsPrefix = ''
	) {
		$enableColumns = $GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns'];

		if (!is_array($enableColumns)) {
			return $indexDoc;
		}

		$recordIndexMapping = array();
		$tempModel = clone $model;
		foreach ($enableColumns as $typo3InternalName => $enableColumnName) {
			$recordIndexMapping = $this->enhanceRecordIndexMappingForEnableColumn(
				$recordIndexMapping, $typo3InternalName, $enableColumnName, $indexDocFieldsPrefix
			);

			$tempModel = $this->convertEnableColumnValue(
				$tempModel, $typo3InternalName, $enableColumnName
			);
		}
		$this->indexModelByMapping($tempModel, $recordIndexMapping, $indexDoc);

		return $indexDoc;
	}

	/**
	 * Indexes the Rootpage of the current models page
	 *
	 * @param tx_rnbase_IModel $model
	 * @param string $tableName
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @param array $options
	 * @return tx_mksearch_interface_IndexerDocument
	 */
	protected function indexSiteRootPage(
		tx_rnbase_IModel $model, $tableName,
		tx_mksearch_interface_IndexerDocument $indexDoc,
		$options = array()
	) {
		if ($this->shouldIndexSiteRootPage($options)) {
			$pageId = ($tableName == 'pages' ? $model->getUid() : $model->record['pid']);
			// wir rufen die Methode mit call_user_func_array auf, da sie
			// statisch ist, womit wir diese nicht mocken könnten
			$siteRootPage = call_user_func_array(
				array($this->getCoreConfigUtility(), 'getSiteRootPage'),
				array($pageId)
			);
			if (is_array($siteRootPage) && !empty($siteRootPage)) {
				$indexDoc->addField('siteRootPage', $siteRootPage['uid']);
			}
		}

		return $indexDoc;
	}

	/**
	 *
	 * @param array $options
	 * @return boolean
	 */
	protected function shouldIndexSiteRootPage($options) {
		$config = $this->getConfigValue('indexSiteRootPage', $options);
		return ((is_array($config) && !empty($config) && reset($config) == 1));

	}

	/**
	 * Erweitert den record um die enable columns.
	 *
	 * @param array $recordIndexMapping
	 * @param string $typo3InternalName
	 * @param string $enableColumnName
	 * @param string $indexDocFieldsPrefix
	 * @return array
	 */
	protected function enhanceRecordIndexMappingForEnableColumn(
		$recordIndexMapping, $typo3InternalName,
		$enableColumnName, $indexDocFieldsPrefix
	) {
		$fieldTypeMapping = array(
			'starttime' => 'dt',
			'endtime' => 'dt',
			'fe_group' => 'mi',
		);
		if (array_key_exists($typo3InternalName, $fieldTypeMapping)) {
			$recordIndexMapping[$enableColumnName] =
				$indexDocFieldsPrefix . $typo3InternalName .
				'_' . $fieldTypeMapping[$typo3InternalName];
		}

		return $recordIndexMapping;
	}

	/**
	 * phpdoc
	 *
	 * @param tx_rnbase_IModel $model
	 * @param string $typo3InternalName
	 * @param string $enableColumnName
	 * @return tx_rnbase_IModel
	 */
	protected function convertEnableColumnValue(
		$model, $typo3InternalName, $enableColumnName
	) {
		switch ($typo3InternalName) {
			case 'starttime':
				// starttime is treated the same as endtime
			case 'endtime':
				$model->record[$enableColumnName] =
					$this->convertTimestampToDateTime($model->record[$enableColumnName]);
				break;
			case 'fe_group':
				$model->record[$enableColumnName] = $this->getEffectiveFeGroups(
						$model->record[$enableColumnName], $model->record['pid']
				);
				break;
			default:
				break;
		}

		return $model;
	}

	/**
	 * Wandelt einen tstam in Y-m-d\TH:i:s\Z um.
	 *
	 * @param int $timestamp
	 * @return string
	 */
	protected function convertTimestampToDateTime($timestamp) {
		$dateTime = 0;
		if (!empty($timestamp)) {
			$util = tx_mksearch_util_Indexer::getInstance();
			$dateTime = $util->getDateTime('@' . $timestamp);
		}

		return $dateTime;
	}

	/**
	 * Return a content element's FE groups
	 *
	 * @param array $fegroups
	 * @param int $pid
	 * @return array
	 */
	protected function getEffectiveFeGroups($fegroups, $pid) {
		return tx_mksearch_service_indexer_core_Config::getEffectiveContentElementFeGroups(
			$pid,
			tx_rnbase_util_Strings::trimExplode(',', $fegroups, TRUE)
		);
	}

	/**
	 * Return the default Typoscript configuration for an indexer.
	 *
	 * Overwrite this method to return the indexer's TS config.
	 * Note that this config is not used for actual indexing
	 * but only serves as assistance when actually configuring an indexer!
	 * Hence all possible configuration options should be set or
	 * at least be mentioned to provide an easy-to-access inline documentation!
	 *
	 * @return string
	 */
	public function getDefaultTSConfig() {
		return <<<CONFIG
# Fields which are set statically to the given value
# Don't forget to add those fields to your Solr schema.xml
# For example it can be used to define site areas this
# contentType belongs to
#
# fixedFields{
#	my_fixed_field_singlevalue = first
#   my_fixed_field_multivalue{
#      0 = first
#      1 = second
#   }
# }
### delete from or abort indexing for the record if isIndexableRecord or no record?
# deleteIfNotIndexable = 0

### should the Root Page of the current records page be indexed?
# indexSiteRootPage = 0

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
CONFIG;
	}

	/**
	 * Adds a element to the queue
	 *
	 * @param tx_rnbase_IModel $model
	 * @param string $tableName
	 * @return void
	 */
	protected function addModelToIndex(tx_rnbase_IModel $model, $tableName) {
		tx_mksearch_util_Indexer::getInstance()
			->addModelToIndex($model, $tableName);
	}

	/**
	 * just a wrapper for addModelToIndex and an array of models
	 *
	 * @param array $models
	 * @param string $tableName
	 * @param 	boolean 	$prefer
	 * @param 	string 		$resolver class name of record resolver
	 * @param 	array 		$data
	 * @param 	array 		$options
	 * @return void
	 */
	protected function addModelsToIndex(
		$models, $tableName, $prefer=false, $resolver=false,
		$data=false, array $options = array()
	) {
		tx_mksearch_util_Indexer::getInstance()
			->addModelsToIndex($models, $tableName, $prefer, $resolver, $data, $options);
	}

	/**
	 * Checks if a include or exclude option was set for a given option key
	 *
	 * @param array $models array of tx_rnbase_IModel
	 * @param array $options
	 * @param int $mode 0 stands for "include" and 1 "exclude"
	 * @param string $optionKey
	 * @return bool
	 */
	protected function checkInOrExcludeOptions(
		$models, $options, $mode = 0, $optionKey = 'categories'
	) {
		return tx_mksearch_util_Indexer::getInstance()
			->checkInOrExcludeOptions(
					$models, $options, $mode, $optionKey
		);
	}

	/**
	 * Prüft ob das Element speziell in einem Seitenbaum oder auf einer Seite liegt,
	 * der/die inkludiert oder ausgeschlossen werden soll.
	 * Der Entscheidungsbaum dafür ist relativ, sollte aber durch den Code
	 * illustriert werden.
	 *
	 * @param array $sourceRecord
	 * @param array $options
	 * @return bool
	 */
	protected function isOnIndexablePage($sourceRecord, $options) {
		return tx_mksearch_util_Indexer::getInstance()
			->isOnIndexablePage(
					$sourceRecord, $options
		);
	}

	/**
	 * Wrapper for tx_rnbase_util_Misc::getPidList
	 *
	 * @param string $pidList
	 * @param int $recursive
	 * @return string with list of pids
	 */
	protected function _getPidList($pidList, $recursive=0) {
		// @TODO: BUG in rn_base. dort sollte sichergestellt werden,
		//        das die TSFE existiert.
		tx_rnbase_util_Misc::prepareTSFE();
		return tx_rnbase_util_Misc::getPidList($pidList, $recursive);
	}

	/**
	 * Liefert einen Wert aus der Konfig
	 * Beispiel: $key = test
	 * Dann wird geprüft ob test eine kommaseparierte Liste liegt
	 * Ist das nicht der Fall wird noch geprüft ob test. ein array ist
	 *
	 * @param string $key
	 * @param array $options
	 * @return array
	 */
	protected function getConfigValue($key, $options) {
		return tx_mksearch_util_Indexer::getInstance()
			->getConfigValue($key, $options);
	}

	/**
	 * Get's the page of the content element if it's not hidden/deleted
	 *
	 * @param int $pid
	 * @return array
	 */
	protected function getPageContent($pid) {
		return tx_mksearch_util_Indexer::getInstance()
			->getPageContent($pid);
	}

	/**
	 * Returns the model to be indexed,
	 * The indexer should override this method to return a specific model
	 *
	 * @param array $rawData
	 * @param string $tableName
	 * @param array $options
	 * @return tx_rnbase_IModel
	 */
	protected function createModel(array $rawData, $tableName = NULL, $options = array()) {
		/* @var $model tx_rnbase_model_Base */
		$model = tx_rnbase::makeInstance(
			'tx_rnbase_model_Base',
			$rawData
		);
		if (!empty($tableName)) {
			$model->setTableName($tableName);
		}
		return $model;
	}

	/**
	 * Do the actual indexing for the given model
	 *
	 * @param tx_rnbase_IModel $model
	 * @param string $tableName
	 * @param array $rawData
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @param array $options
	 * @return tx_mksearch_interface_IndexerDocument|null
	 */
	protected abstract function indexData(
		tx_rnbase_IModel $model,
		$tableName, $rawData,
		tx_mksearch_interface_IndexerDocument $indexDoc,
		$options
	);

	/**
	 * Liefert das Model welches aktuell indiziert wird.
	 *
	 * @return tx_rnbase_IModel
	 */
	protected function getModelToIndex() {
		return $this->modelToIndex;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_Base.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_Base.php']);
}
