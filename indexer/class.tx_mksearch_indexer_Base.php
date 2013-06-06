<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_indexer
 *  @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2011 Hannes Bochmann <hannes.bochmann@das-medienkombinat.de>
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
require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_mksearch_interface_Indexer');
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
abstract class tx_mksearch_indexer_Base implements tx_mksearch_interface_Indexer {

	/**
	 * @var tx_rnbase_IModel
	 */
	private $modelToIndex;

	/**
	 * (non-PHPdoc)
	 * @see tx_mksearch_interface_Indexer::prepareSearchData()
	 */
	public function prepareSearchData($tableName, $rawData, tx_mksearch_interface_IndexerDocument $indexDoc, $options) {
		//when an indexer is configured for more than one table
		//the index process may be different for the tables.
		//overwrite this method in your child class to stop processing and
		//do something different like putting a record into the queue.
		if($this->stopIndexing($tableName, $rawData, $indexDoc, $options))
			return null;

		// get a model from the source array
		$this->modelToIndex = $this->createModel($rawData);

		// Set base id for specific indexer.
		$indexDoc->setUid($this->getUid($tableName, $rawData, $options));

		// Is the model valid and data indexable?
		if (!$this->modelToIndex->record || !$this->isIndexableRecord($this->modelToIndex->record, $options)) {
			if(isset($options['deleteIfNotIndexable']) && $options['deleteIfNotIndexable']) {
				$indexDoc->setDeleted(true);
				return $indexDoc;
			} else return null;
		}

		//shall we break the indexing and set the doc to deleted?
		if($this->hasDocToBeDeleted($this->modelToIndex,$indexDoc,$options)){
			$indexDoc->setDeleted(true);
			return $indexDoc;
		}

		$indexDoc = $this->indexEnableColumns($this->modelToIndex, $tableName, $indexDoc);
		$indexDoc = $this->indexData($this->modelToIndex, $tableName, $rawData, $indexDoc, $options);

		//done
		return $indexDoc;
	}

	/**
	 * Liefert die UID des Datensatzes
	 *
	 * @param string $tableName
	 * @param array $rawData
	 * @param array $options
	 * @return int
	 */
	protected function getUid($tableName, $rawData, $options) {
		// Take care for localized records where uid of original record
		// is stored in $rawData['l18n_parent'] instead of $rawData['uid']!
		$sysLanguageUidField = tx_mksearch_util_TCA::getLanguageFieldForTable($tableName);
		$lnParentField = tx_mksearch_util_TCA::getTransOrigPointerFieldForTable($tableName);
		return isset($rawData[$sysLanguageUidField]) && $rawData[$sysLanguageUidField] && isset($rawData[$lnParentField])
			? $rawData[$lnParentField]
			: $rawData['uid']
		;
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
	 * @param array $rawData
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @param array $options
	 *
	 * @return bool
	 */
	protected function stopIndexing($tableName, $rawData, tx_mksearch_interface_IndexerDocument $indexDoc, $options) {
		// Wir prüfen, ob die zu indizierende Sprache stimmt.
		$sysLanguageUidField = tx_mksearch_util_TCA::getLanguageFieldForTable($tableName);
		if (isset($rawData[$sysLanguageUidField])) {
			// @TODO: getTransOrigPointerFieldForTable abprüfen, wenn $lang!=0 !
			$lang = isset($options['lang']) ? (int) $options['lang'] : 0;
			if($rawData[$sysLanguageUidField] != $lang) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @see tx_mksearch_util_Indexer::indexModelByMapping()
	 */
	protected function indexModelByMapping(
		tx_rnbase_IModel $model, array $recordIndexMapping,
		tx_mksearch_interface_IndexerDocument $indexDoc,
		$prefix = '', array $options = array()
	) {
		$indexDoc =
			tx_mksearch_util_Indexer::getInstance()
				->indexModelByMapping(
				$model, $recordIndexMapping, $indexDoc, $prefix, $options
			);
	}

	/**
	 * @see tx_mksearch_util_Indexer::indexArrayOfModelsByMapping()
	 */
	protected function indexArrayOfModelsByMapping(
		array $models, array $recordIndexMapping,
		tx_mksearch_interface_IndexerDocument $indexDoc, $prefix = ''
	) {
		$indexDoc =
			tx_mksearch_util_Indexer::getInstance()
				->indexArrayOfModelsByMapping(
				$models, $recordIndexMapping, $indexDoc, $prefix
			);
	}

	/**
	 * Sets the index doc to deleted if neccessary
	 * @param tx_rnbase_IModel $model
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @return bool
	 */
	protected function hasDocToBeDeleted(tx_rnbase_IModel $model, tx_mksearch_interface_IndexerDocument $indexDoc, $options = array()) {
		// @FIXME hidden und deleted sind enablecolumns, können jeden belibigen Namen tragen und stehen in der tca.
		// @see tx_mklib_util_TCA::getEnableColumn
		// $deletedField = $GLOBALS['TCA'][$model->getTableName()]['ctrl']['enablecolumns']['delete'];
		// $disabledField = $GLOBALS['TCA'][$model->getTableName()]['ctrl']['enablecolumns']['disabled'];
		// wäre vlt. auch was für rn_base, das model könnte diese informationen ja bereit stellen!
		if ($model->record['hidden'] == 1 || $model->record['deleted'] == 1)
			return true;

		// are our parent pages valid?
		// as soon as one of the parent pages is hidden we return true.
		// @todo support when a parent page is deleted! shouldn't be possible
		// without further configuration for a BE user but it's still possible!
		$rootline = tx_mksearch_service_indexer_core_Config::getRootLine($model->record['pid']);

		foreach ($rootline as $page) {
			if($page['hidden'])
				return true;
		}
		//else
		return false;
	}

	/**
	 * @see tx_mksearch_util_Indexer::isOnIndexablePage()
	 */
	protected function isIndexableRecord(array $sourceRecord, array $options) {
		return $this->isOnIndexablePage($sourceRecord, $options);
	}

	/**
	 * @param tx_rnbase_IModel $model
	 * @param string $tableName
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @param string $indexDocFieldsPrefix
	 *
	 * @return tx_mksearch_interface_IndexerDocument
	 */
	protected function indexEnableColumns(
		tx_rnbase_IModel $model, $tableName,
		tx_mksearch_interface_IndexerDocument $indexDoc, $indexDocFieldsPrefix = ''
	) {
		global $TCA;
		$enableColumns = $TCA[$tableName]['ctrl']['enablecolumns'];

		if(!is_array($enableColumns))
			return $indexDoc;

		$recordIndexMapping = array();
		$tempModel = $model;
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

	protected function enhanceRecordIndexMappingForEnableColumn(
		$recordIndexMapping, $typo3InternalName, $enableColumnName, $indexDocFieldsPrefix
	) {
		$fieldTypeMapping = array(
			'starttime' => 'dt',
			'endtime' => 'dt',
			'fe_group' => 'mi',
		);
		if(array_key_exists($typo3InternalName, $fieldTypeMapping)){
			$recordIndexMapping[$enableColumnName] =
				$indexDocFieldsPrefix.$typo3InternalName . '_' . $fieldTypeMapping[$typo3InternalName];
		}

		return $recordIndexMapping;
	}

	protected function convertEnableColumnValue(
		$model, $typo3InternalName, $enableColumnName
	) {
		switch ($typo3InternalName) {
			case 'starttime':
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

	protected function convertTimestampToDateTime($timestamp) {
		$dateTime = 0;
		if(!empty($timestamp))
			$dateTime = tx_mksearch_util_Indexer::getInstance()->getDateTime('@' . $timestamp);

		return $dateTime;
	}

	/**
	 * @see tx_mksearch_service_indexer_core_Config::getEffectiveContentElementFeGroups()
	 */
	protected function getEffectiveFeGroups($fegroups, $pid) {
		return tx_mksearch_service_indexer_core_Config::getEffectiveContentElementFeGroups(
				$pid,
				t3lib_div::trimExplode(',', $fegroups, true)
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
CONFIG;
	}

	/**
	 * @see tx_mksearch_util_Indexer::addModelToIndex()
	 */
	protected function addModelToIndex(tx_rnbase_IModel $model, $tableName){
		tx_mksearch_util_Indexer::getInstance()
			->addModelToIndex($model, $tableName);
	}

	/**
	 * @see tx_mksearch_util_Indexer::addModelsToIndex()
	 */
	protected function addModelsToIndex($models, $tableName) {
		tx_mksearch_util_Indexer::getInstance()
			->addModelsToIndex($models, $tableName);
	}

	/**
	 * @see tx_mksearch_util_Indexer::checkInOrExcludeOptions()
	 */
	protected function checkInOrExcludeOptions(
		$models,$options, $mode = 0, $optionKey = 'categories'
	) {
		return tx_mksearch_util_Indexer::getInstance()
			->checkInOrExcludeOptions(
					$models, $options, $mode, $optionKey
		);
	}

	/**
	 * @see tx_mksearch_util_Indexer::isOnIndexablePage()
	 */
	protected function isOnIndexablePage($sourceRecord, $options) {
		return tx_mksearch_util_Indexer::getInstance()
			->isOnIndexablePage(
					$sourceRecord, $options
		);
	}

	/**
	 * wrapper for tx_rnbase_util_DB::_getPidList
	 * we load the TSFE addtionaly
	 */
	protected function _getPidList($pid_list, $recursive=0) {
		tx_rnbase::load('tx_rnbase_util_Misc');
		tx_rnbase_util_Misc::prepareTSFE();

		tx_rnbase::load('tx_rnbase_util_DB');
		return tx_rnbase_util_DB::_getPidList($pid_list, $recursive, true);
	}

	/**
	 * @see tx_mksearch_util_Indexer::getConfigValue()
	 */
	protected function getConfigValue($key, $options) {
		return tx_mksearch_util_Indexer::getInstance()
			->getConfigValue($key, $options);
	}

	/**
	 * @see tx_mksearch_util_Indexer::getPageContent()
	 */
	protected function getPageContent($pid) {
		return tx_mksearch_util_Indexer::getInstance()
			->getPageContent($pid);
	}

	/**
	 * Returns the model to be indexed
	 *
	 * @param array $rawData
	 *
	 * @return tx_rnbase_IModel
	 */
	protected abstract function createModel(array $rawData);

	/**
	 * Do the actual indexing for the given model
	 *
	 * @param tx_rnbase_IModel $model
	 * @param string $tableName
	 * @param array $rawData
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @param array $options
	 */
	protected abstract function indexData(tx_rnbase_IModel $model, $tableName, $rawData, tx_mksearch_interface_IndexerDocument $indexDoc, $options);

	/**
	 * @return tx_rnbase_IModel
	 */
	protected function getModelToIndex() {
		return $this->modelToIndex;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_Base.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_Base.php']);
}