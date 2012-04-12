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
	public function prepareSearchData($sTableName, $aRawData, tx_mksearch_interface_IndexerDocument $oIndexDoc, $aOptions) {
		//when an indexer is configured for more than one table
		//the index process may be different for the tables.
		//overwrite this method in your child class to stop processing and
		//do something different like putting a record into the queue.
		if($this->stopIndexing($sTableName, $aRawData, $oIndexDoc, $aOptions))
			return null;

		// get a model from the source array
		$oModel = $this->createModel($aRawData);

		// set base id for specific indexer
		$oIndexDoc->setUid($oModel->getUid());

		// Is the model valid and data indexable?
		if (!$oModel->record || !$this->isIndexableRecord($oModel->record, $aOptions)) {
			if(isset($aOptions['deleteIfNotIndexable']) && $aOptions['deleteIfNotIndexable']) {
				$oIndexDoc->setDeleted(true);
				return $oIndexDoc;
			} else return null;
		}

		//shall we break the indexing and set the doc to deleted?
		if($this->hasDocToBeDeleted($oModel,$oIndexDoc,$aOptions)){
			$oIndexDoc->setDeleted(true);
			return $oIndexDoc;
		}

		$oIndexDoc = $this->indexData($oModel, $sTableName, $aRawData, $oIndexDoc, $aOptions);

		//done
		return $oIndexDoc;
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
	 * @param string $sTableName
	 * @param array $aRawData
	 * @param tx_mksearch_interface_IndexerDocument $oIndexDoc
	 * @param array $aOptions
	 *
	 * @return bool
	 */
	protected function stopIndexing($sTableName, $aRawData, tx_mksearch_interface_IndexerDocument $oIndexDoc, $aOptions) {
		//nothing to handle by default. continue indexing
		return false;
	}

	/**
	 * Indexes all fields of the model according to the given mapping
	 *
	 * @param tx_rnbase_model_Base $oModel
	 * @param array $aMapping
	 * @param tx_mksearch_interface_IndexerDocument $oIndexDoc
	 * @param string $sPrefix
	 * @param array $aOptions
	 * 
	 * @todo move function to a helper class as the method has nothing to do
	 * with actual indexing. it's just a helper.
	 */
	protected function indexModelByMapping(tx_rnbase_model_base $oModel, array $aRecordIndexMapping, tx_mksearch_interface_IndexerDocument $oIndexDoc, $sPrefix = '', array $aOptions = array()) {
		foreach ($aRecordIndexMapping as $sRecordKey => $sIndexDocKey) {
			if(!empty($oModel->record[$sRecordKey]))
				$oIndexDoc->addField(
					$sPrefix.$sIndexDocKey,
					$aOptions['keepHtml'] ? $oModel->record[$sRecordKey] : tx_mksearch_util_Misc::html2plain($oModel->record[$sRecordKey])
				);
		}
	}

	/**
	 * Collects the values of all models inside the given array
	 * and adds them as multivalue (array)
	 *
	 * @param tx_rnbase_model_Base $oModel
	 * @param array $aMapping
	 * @param tx_mksearch_interface_IndexerDocument $oIndexDoc
	 * @param string $sPrefix
	 * 
	 * @todo move function to a helper class as the method has nothing to do
	 * with actual indexing. it's just a helper.
	 */
	protected function indexArrayOfModelsByMapping(array $aModels, array $aRecordIndexMapping, tx_mksearch_interface_IndexerDocument $oIndexDoc, $sPrefix = '') {
		//collect values
		$aTempIndexDoc = array();
		foreach ($aModels as $oModel){
			foreach ($aRecordIndexMapping as $sRecordKey => $sIndexDocKey) {
				if(!empty($oModel->record[$sRecordKey]))
					$aTempIndexDoc[$sPrefix.$sIndexDocKey][] = $oModel->record[$sRecordKey];
			}
		}

		//and now add the fields
		if(!empty($aTempIndexDoc)){
			foreach ($aTempIndexDoc as $sIndexDocKey => $aValues){
				$oIndexDoc->addField($sIndexDocKey, $aValues);
			}
		}
	}

	/**
	 * Sets the index doc to deleted if neccessary
	 * @param tx_rnbase_model_base $oModel
	 * @param tx_mksearch_interface_IndexerDocument $oIndexDoc
	 * @return bool
	 */
	protected function hasDocToBeDeleted(tx_rnbase_model_base $oModel, tx_mksearch_interface_IndexerDocument $oIndexDoc, $aOptions = array()) {
		// @FIXME hidden und deleted sind enablecolumns, können jeden belibigen Namen tragen und stehen in der tca.
		// @see tx_mklib_util_TCA::getEnableColumn
		// $deletedField = $GLOBALS['TCA'][$oModel->getTableName()]['ctrl']['enablecolumns']['delete'];
		// $disabledField = $GLOBALS['TCA'][$oModel->getTableName()]['ctrl']['enablecolumns']['disabled'];
		// wäre vlt. auch was für rn_base, das model könnte diese informationen ja bereit stellen!
		if ($oModel->record['hidden'] == 1 || $oModel->record['deleted'] == 1)
			return true;

		// are our parent pages valid?
		// as soon as one of the parent pages is hidden we return true.
		// @todo support when a parent page is deleted! shouldn't be possible
		// without further configuration for a BE user but it's still possible!
		$aRootline = tx_mksearch_service_indexer_core_Config::getRootLine($oModel->record['pid']);
		foreach ($aRootline as $aPage) {
			if($aPage['hidden'])
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
	 * @param tx_rnbase_model_base $oModel
	 * @return void
	 * 
	 * @todo move function to a helper class as the method has nothing to do
	 * with actual indexing. it's just a helper.
	 */
	protected function addModelToIndex(tx_rnbase_model_base $oModel, $sTableName){
		if(!empty($oModel) && $oModel->isValid()){
			if(!$this->oIndexSrv)
				$this->oIndexSrv = tx_mksearch_util_ServiceRegistry::getIntIndexService();
			$this->oIndexSrv->addRecordToIndex($sTableName, $oModel->getUid());
		}
	}

	/**
	 * just a wrapper for addModelToIndex and an array of models
	 * @param array $aRawData
	 * @param array $aModels
	 * @param string $sTableName
	 * @return void
	 * 
	 * @todo move function to a helper class as the method has nothing to do
	 * with actual indexing. it's just a helper.
	 */
	protected function addModelsToIndex($aModels, $sTableName) {
		if(!empty($aModels))
			foreach ($aModels as $oModel) {
				$this->addModelToIndex($oModel,$sTableName);
		}
	}

	/**
	 * Checks if a include or exclude option was set for a
	 * given option key
	 * if set we check if at least one of the uids given in the options
	 * is found in the given models
	 * for the include option returning true means no option set or we have a hit
	 * for the exclude option returning true means no option was set or we have no hit
	 * @param array $aModels | array of models
	 * @param array $aOptions
	 * @param int $iMode	| 0 stands for "include" and 1 "exclude"
	 * @return bool
	 * 
	 * @todo move function to a helper class as the method has nothing to do
	 * with actual indexing. it's just a helper.
	 */
	protected function checkInOrExcludeOptions($aModels,$aOptions, $iMode = 0, $sOptionKey = 'categories') {
		//set base returns depending on the mode
		switch ($iMode) {
			case 0:
			default:
				$sMode = 'include';
				$bNoHit = false;//if we have no hit
				$bHit = true;//if we have a hit
				break;
			case 1:
				$sMode = 'exclude';
				$bNoHit = true;//if we have no hit
				$bHit = false;//if we have a hit
				break;
		}

		//should only element with a special category be indexed
		if(!empty($aOptions[$sMode.'.']) && !empty($aModels)){
			//include categories as array like
			//include.categories{
			//	0 = 1
			//	1 = 2
			//}
			if(!empty($aOptions[$sMode.'.'][$sOptionKey.'.'])){
				$aIncludeCategories = $aOptions[$sMode.'.'][$sOptionKey.'.'];
			}
			//include categories as string like
			//include.categories = 1,2
			elseif(!empty($aOptions[$sMode.'.'][$sOptionKey])){
				$aIncludeCategories = explode(',',$aOptions[$sMode.'.'][$sOptionKey]);
			}

			//if config is empty nothing to do and everything is alright
			if(empty($aIncludeCategories))
				return true;

			//check if at least one category of the current element
			//is in the given include categories
			$bIsValid = $bNoHit;//if we find nothing
			foreach ($aModels as $oModel){
				if(in_array($oModel->getUid(),$aIncludeCategories)){
					$bIsValid = $bHit;
				}
			}
		}else
			$bIsValid = true;//no option given
		return $bIsValid;
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
		$aPids = $this->getPidList($sourceRecord, $options['exclude.'], 'pageTrees');
		if (array_search($sourceRecord['pid'], $aPids) !== false)
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
		$aPids = $this->getPidList($sourceRecord, $options['include.'], 'pageTrees');
		if (array_search($sourceRecord['pid'], $aPids) !== false || empty($aPids))
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
	 * @param string $sKey
	 * @return array
	 */
	private function getPidList($sourceRecord, $options, $sKey) {

		$aPids = array();
		$aPageTrees = $this->getConfigValue($sKey, $options);
		if (is_array($aPageTrees) && !empty($aPageTrees)){
			foreach ($aPageTrees as $iUid)
				$aPidsByPageTree[] = explode(',', $this->_getPidList($iUid,999));
		}

		if (is_array($aPidsByPageTree) && count($aPidsByPageTree)) {
			//jetzt alle pids zusammenführen für alle angegebenen Seitenbäume
			foreach ($aPidsByPageTree as $aPidsTemp)
				$aPids = array_merge($aPids, $aPidsTemp);
		}

		return $aPids;
	}

	/**
	 * Liefert einen Wert aus der Konfig
	 * Beispiel: $key = test
	 * Dann wird geprüft ob test eine kommaseparierte Liste liegt
	 * Ist das nicht der Fall wird noch geprüft ob test. ein array ist
	 * @param string $sKey
	 *
	 * @return array
	 * 
	 * @todo move function to a helper class as the method has nothing to do
	 * with actual indexing. it's just a helper.
	 */
	protected function getConfigValue($sKey, $options) {
		if(is_array($options)){
			$aConfig = array();
			$aConfig = (array_key_exists($sKey, $options) && strlen(trim($options[$sKey]))) ? t3lib_div::trimExplode(',', $options[$sKey]) : false;
			if(!is_array($aConfig) && array_key_exists($sKey.'.', $options)) {
				$aConfig = $options[$sKey.'.'];
			}
		}
		return $aConfig;
	}

	/**
	 * Get's the page of the content element if it's not hidden/deleted
	 * @param tx_rnbase_model_base $oModel
	 * @return null || array
	 * 
	 * @todo move function to a helper class as the method has nothing to do
	 * with actual indexing. it's just a helper.
	 */
	protected function checkPageRights(tx_rnbase_model_base $oModel) {
		//first of all we have to check if the page is not hidden/deleted
		$aSqlOptions = array(
			'where' => 'pages.uid=' . $oModel->record['pid'],
			//so we get only pages that are valid for the FE
			'enablefieldsfe' => true,
		);
		$aFrom = array('pages', 'pages');
		return $aRows = tx_rnbase_util_DB::doSelect('*', $aFrom, $aSqlOptions);
	}

	/**
	 * Returns the model to be indexed
	 *
	 * @param array $aRawData
	 *
	 * @return tx_rnbase_model_base
	 */
	protected abstract function createModel(array $aRawData);

	/**
	 * Do the actual indexing for the given model
	 *
	 * @param tx_rnbase_model_base $oModel
	 * @param string $sTableName
	 * @param array $aRawData
	 * @param tx_mksearch_interface_IndexerDocument $oIndexDoc
	 * @param array $aOptions
	 */
	protected abstract function indexData(tx_rnbase_model_base $oModel, $sTableName, $aRawData, tx_mksearch_interface_IndexerDocument $oIndexDoc, $aOptions);
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_Base.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_Base.php']);
}