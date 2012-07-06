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
	 * @var tx_mksearch_service_internal_Index
	 */
	protected $oIndexSrv;
	
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
	 * Indexes all fields of the model according to the given mapping
	 *
	 * @param tx_rnbase_model_Base $model
	 * @param array $aMapping
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @param string $prefix
	 * @param array $options
	 * 
	 * @todo move function to a helper class as the method has nothing to do
	 * with actual indexing. it's just a helper.
	 */
	protected function indexModelByMapping(tx_rnbase_model_base $model, array $recordIndexMapping, tx_mksearch_interface_IndexerDocument $indexDoc, $prefix = '', array $options = array()) {
		foreach ($recordIndexMapping as $recordKey => $indexDocKey) {
			if(!empty($model->record[$recordKey]))
				$indexDoc->addField(
					$prefix.$indexDocKey,
					$options['keepHtml'] ? $model->record[$recordKey] : 
						tx_mksearch_util_Misc::html2plain($model->record[$recordKey])
				);
		}
	}

	/**
	 * Collects the values of all models inside the given array
	 * and adds them as multivalue (array)
	 *
	 * @param tx_rnbase_model_Base $model
	 * @param array $aMapping
	 * @param tx_mksearch_interface_IndexerDocument $indexDoc
	 * @param string $prefix
	 * 
	 * @todo move function to a helper class as the method has nothing to do
	 * with actual indexing. it's just a helper.
	 */
	protected function indexArrayOfModelsByMapping(array $models, array $recordIndexMapping, tx_mksearch_interface_IndexerDocument $indexDoc, $prefix = '') {
		//collect values
		$tempIndexDoc = array();
		foreach ($models as $model){
			foreach ($recordIndexMapping as $recordKey => $indexDocKey) {
				if(!empty($model->record[$recordKey]))
					$tempIndexDoc[$prefix.$indexDocKey][] = $model->record[$recordKey];
			}
		}

		//and now add the fields
		if(!empty($tempIndexDoc)){
			foreach ($tempIndexDoc as $indexDocKey => $values){
				$indexDoc->addField($indexDocKey, $values);
			}
		}
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
	 * Checks if the dataset is indexable at all
	 * @param array $sourceRecord
	 * @param array $options
	 * @return bool
	 */
	protected function isIndexableRecord(array $sourceRecord, array $options) {
		$ret = tx_mksearch_util_Misc::isIndexable($sourceRecord, $options);
		return $ret;
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
		foreach ($enableColumns as $typo3InternalName => $enableColumnName) {
			//we always use a string field as this can take every value
			$recordIndexMapping[$enableColumnName] = $indexDocFieldsPrefix.$enableColumnName . '_s'; 
		}

		$this->indexModelByMapping($model, $recordIndexMapping, $indexDoc);
		
		return $indexDoc;
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
	 * Adds a element to the queue
	 * @param tx_rnbase_model_base $model
	 * @return void
	 * 
	 * @todo move function to a helper class as the method has nothing to do
	 * with actual indexing. it's just a helper.
	 */
	protected function addModelToIndex(tx_rnbase_model_base $model, $tableName){
		if(!empty($model) && $model->isValid()){
			if(!$this->oIndexSrv)
				$this->oIndexSrv = tx_mksearch_util_ServiceRegistry::getIntIndexService();
			$this->oIndexSrv->addRecordToIndex($tableName, $model->getUid());
		}
	}

	/**
	 * just a wrapper for addModelToIndex and an array of models
	 * @param array $rawData
	 * @param array $models
	 * @param string $tableName
	 * @return void
	 * 
	 * @todo move function to a helper class as the method has nothing to do
	 * with actual indexing. it's just a helper.
	 */
	protected function addModelsToIndex($models, $tableName) {
		if(!empty($models))
			foreach ($models as $model) {
				$this->addModelToIndex($model,$tableName);
		}
	}

	/**
	 * Checks if a include or exclude option was set for a
	 * given option key
	 * if set we check if at least one of the uids given in the options
	 * is found in the given models
	 * for the include option returning true means no option set or we have a hit
	 * for the exclude option returning true means no option was set or we have no hit
	 * @param array $models | array of models
	 * @param array $options
	 * @param int $mode	| 0 stands for "include" and 1 "exclude"
	 * @return bool
	 * 
	 * @todo move function to a helper class as the method has nothing to do
	 * with actual indexing. it's just a helper.
	 */
	protected function checkInOrExcludeOptions($models,$options, $mode = 0, $optionKey = 'categories') {
		//set base returns depending on the mode
		switch ($mode) {
			case 0:
			default:
				$mode = 'include';
				$noHit = false;//if we have no hit
				$isHit = true;//if we have a hit
				break;
			case 1:
				$mode = 'exclude';
				$noHit = true;//if we have no hit
				$isHit = false;//if we have a hit
				break;
		}

		//should only element with a special category be indexed
		if(!empty($options[$mode.'.'])){
			$isValid = $noHit;//no option given
			if (!empty($models)) {
				//include categories as array like
				//include.categories{
				//	0 = 1
				//	1 = 2
				//}
				if(!empty($options[$mode.'.'][$optionKey.'.'])){
					$includeCategories = $options[$mode.'.'][$optionKey.'.'];
				}
				//include categories as string like
				//include.categories = 1,2
				elseif(!empty($options[$mode.'.'][$optionKey])){
					$includeCategories = explode(',',$options[$mode.'.'][$optionKey]);
				}
	
				//if config is empty nothing to do and everything is alright
				if(empty($includeCategories))
					return true;
	
				//check if at least one category of the current element
				//is in the given include categories
				foreach ($models as $model){
					if(in_array($model->getUid(),$includeCategories)){
						$isValid = $isHit;
					}
				}
			}
		}
		else {
			$isValid = true;
		}
		return $isValid;
	}

	/**
	 * Prüft ob die Seite speziell in einem Seitenbaum liegt,
	 * der inkludiert oder ausgeschlossen werden soll
	 *
	 * @param array $sourceRecord
	 * @param array $options
	 * @return bool
	 * 
	 * @todo move function to a helper class as the method has nothing to do
	 * with actual indexing. it's just a helper.
	 */
	protected function checkPageTree($sourceRecord, $options) {

		if (!$this->checkPageTreeIncludes($sourceRecord, $options)) return false;
		if ( $this->checkPageTreeExcludes($sourceRecord, $options)) return false;

		return true;
	}

	/**
	 * Wenn das Element im gegebenen Seitenbaum ist, wird NICHT indiziert
	 *
	 * @param array $sourceRecord
	 * @param array $options
	 * @return bool
	 */
	private function checkPageTreeExcludes($sourceRecord, $options) {
		// Prüfen ob die Seite in den index darf sprich nicht in den gegebenen Seitenbäumen liegt
		$pids = $this->getPidList($sourceRecord, $options['exclude.'], 'pageTrees');
		if (array_search($sourceRecord['pid'], $pids) !== false)
			return true;
		//else
		return false;
	}

	/**
	 * Nur wenn das Element im gegebenen Seitenbaum ist, wird indiziert
	 *
	 * @param array $sourceRecord
	 * @param array $options
	 * @return bool
	 */
	private function checkPageTreeIncludes($sourceRecord, $options) {
		// Prüfen ob die Seite in den index darf sprich in den gegebenen Seitenbäumen liegt
		$pids = $this->getPidList($sourceRecord, $options['include.'], 'pageTrees');
		if (array_search($sourceRecord['pid'], $pids) !== false || empty($pids))
			return true;
		//else
		return false;
	}

	/**
	 * wrapper for tx_rnbase_util_DB::_getPidList
	 * we load the TSFE addtionaly
	 */
	protected function _getPidList($pid_list, $recursive=0) {
		tx_rnbase::load('tx_rnbase_util_Misc');
		tx_rnbase_util_Misc::prepareTSFE();
		
		tx_rnbase::load('tx_rnbase_util_DB');
		return tx_rnbase_util_DB::_getPidList($pid_list, $recursive);
	}

	/**
	 * Liefert ein Array mit allen Pids, die in den Seitenbäumen
	 * vorhanden sind
	 * @param array $sourceRecord
	 * @param mixed $options
	 * @param string $key
	 * @return array
	 */
	private function getPidList($sourceRecord, $options, $key) {

		$pids = array();
		$pageTrees = $this->getConfigValue($key, $options);
		if (is_array($pageTrees) && !empty($pageTrees)){
			foreach ($pageTrees as $iUid)
				$pidsByPageTree[] = explode(',', $this->_getPidList($iUid,999));
		}

		if (is_array($pidsByPageTree) && count($pidsByPageTree)) {
			//jetzt alle pids zusammenführen für alle angegebenen Seitenbäume
			foreach ($pidsByPageTree as $pidsTemp)
				$pids = array_merge($pids, $pidsTemp);
		}

		return $pids;
	}

	/**
	 * Liefert einen Wert aus der Konfig
	 * Beispiel: $key = test
	 * Dann wird geprüft ob test eine kommaseparierte Liste liegt
	 * Ist das nicht der Fall wird noch geprüft ob test. ein array ist
	 * @param string $key
	 *
	 * @return array
	 * 
	 * @todo move function to a helper class as the method has nothing to do
	 * with actual indexing. it's just a helper.
	 */
	protected function getConfigValue($key, $options) {
		if(is_array($options)){
			$config = array();
			$config = (array_key_exists($key, $options) && strlen(trim($options[$key]))) ? t3lib_div::trimExplode(',', $options[$key]) : false;
			if(!is_array($config) && array_key_exists($key.'.', $options)) {
				$config = $options[$key.'.'];
			}
		}
		return $config;
	}

	/**
	 * Get's the page of the content element if it's not hidden/deleted
	 * @return array
	 * 
	 * @todo move function to a helper class as the method has nothing to do
	 * with actual indexing. it's just a helper.
	 */
	protected function getPageContent($pid) {
		//first of all we have to check if the page is not hidden/deleted
		$sqlOptions = array(
			'where' => 'pages.uid=' . $pid . ' AND hidden=0 AND deleted=0',
			'enablefieldsoff' => true,//ignore fe_group and so on
			'limit' => 1
		);
		$from = array('pages', 'pages');
		$page = tx_rnbase_util_DB::doSelect('*', $from, $sqlOptions);
		return !empty($page[0]) ? $page[0] : array();
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