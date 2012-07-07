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
		$model = $this->createModel($rawData);

		// set base id for specific indexer
		$indexDoc->setUid($model->getUid());

		// Is the model valid and data indexable?
		if (!$model->record || !$this->isIndexableRecord($model->record, $options)) {
			if(isset($options['deleteIfNotIndexable']) && $options['deleteIfNotIndexable']) {
				$indexDoc->setDeleted(true);
				return $indexDoc;
			} else return null;
		}

		//shall we break the indexing and set the doc to deleted?
		if($this->hasDocToBeDeleted($model,$indexDoc,$options)){
			$indexDoc->setDeleted(true);
			return $indexDoc;
		}

		$indexDoc = $this->indexEnableColumns($model, $tableName, $indexDoc);
		$indexDoc = $this->indexData($model, $tableName, $rawData, $indexDoc, $options);

		//done
		return $indexDoc;
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
		//nothing to handle by default. continue indexing
		return false;
	}

	/**
	 * @see tx_mksearch_util_Indexer::indexModelByMapping()
	 */
	protected function indexModelByMapping(
		tx_rnbase_model_base $model, array $recordIndexMapping, 
		tx_mksearch_interface_IndexerDocument $indexDoc, 
		$prefix = '', array $options = array()
	) {
		$indexDoc = 
			tx_mksearch_util_Indexer::indexModelByMapping(
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
			tx_mksearch_util_Indexer::indexArrayOfModelsByMapping(
				$models, $recordIndexMapping, $indexDoc, $prefix
			);
	}

	/**
	 * Sets the index doc to deleted if neccessary
	 * @param tx_rnbase_model_base $model
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @return bool
	 */
	protected function hasDocToBeDeleted(tx_rnbase_model_base $model, tx_mksearch_interface_IndexerDocument $indexDoc, $options = array()) {
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
	 * @see tx_mksearch_util_Misc::isIndexable()
	 */
	protected function isIndexableRecord(array $sourceRecord, array $options) {
		return tx_mksearch_util_Misc::isIndexable($sourceRecord, $options);
	}
	
	/**
	 * @param tx_rnbase_model_base $model
	 * @param string $tableName
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @param string $indexDocFieldsPrefix
	 * 
	 * @return tx_mksearch_interface_IndexerDocument
	 */
	protected function indexEnableColumns(
		tx_rnbase_model_base $model, $tableName, 
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
	
	private function enhanceRecordIndexMappingForEnableColumn(
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
	
	private function convertEnableColumnValue(
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
	
	private function convertTimestampToDateTime($timestamp) {
		$dateTime = 0;
		if(!empty($timestamp))
			$dateTime = tx_mksearch_util_Indexer::getDateTime('@' . $timestamp);
		
		return $dateTime;
	}
	
	/**
	 * @see tx_mksearch_service_indexer_core_Config::getEffectiveContentElementFeGroups()
	 */
	private function getEffectiveFeGroups($fegroups, $pid) {
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
	protected function addModelToIndex(tx_rnbase_model_base $model, $tableName){
		tx_mksearch_util_Indexer::addModelToIndex($model, $tableName);
	}

	/**
	 * @see tx_mksearch_util_Indexer::addModelsToIndex()
	 */
	protected function addModelsToIndex($models, $tableName) {
		tx_mksearch_util_Indexer::addModelsToIndex($models, $tableName);
	}

	/**
	 * @see tx_mksearch_util_Indexer::checkInOrExcludeOptions()
	 */
	protected function checkInOrExcludeOptions(
		$models,$options, $mode = 0, $optionKey = 'categories'
	) {
		return tx_mksearch_util_Indexer::checkInOrExcludeOptions(
			$models, $options, $mode, $optionKey
		);
	}

	/**
	 * @see tx_mksearch_util_Indexer::isInValidPageTree()
	 */
	protected function isInValidPageTree($sourceRecord, $options) {
		return tx_mksearch_util_Indexer::isInValidPageTree(
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
		return tx_mksearch_util_Indexer::getConfigValue($key, $options);
	}

	/**
	 * @see tx_mksearch_util_Indexer::getPageContent()
	 */
	protected function getPageContent($pid) {
		return tx_mksearch_util_Indexer::getPageContent($pid);
	}

	/**
	 * Returns the model to be indexed
	 *
	 * @param array $rawData
	 *
	 * @return tx_rnbase_model_base
	 */
	protected abstract function createModel(array $rawData);

	/**
	 * Do the actual indexing for the given model
	 *
	 * @param tx_rnbase_model_base $model
	 * @param string $tableName
	 * @param array $rawData
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @param array $options
	 */
	protected abstract function indexData(tx_rnbase_model_base $model, $tableName, $rawData, tx_mksearch_interface_IndexerDocument $indexDoc, $options);
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_Base.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_Base.php']);
}