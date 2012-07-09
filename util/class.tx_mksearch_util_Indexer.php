<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 das Medienkombinat
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
tx_rnbase::load('tx_mksearch_service_indexer_core_Config');

/**
 */
class tx_mksearch_util_Indexer {

	/**
	 * Liefert eine Datumsfeld für Solr
	 * @param 	string 	$date | insert "@" before timestamps
	 * @return string
	 */
	public static function getDateTime($date){
		$dateTime = tx_rnbase::makeInstance('DateTime', $date, tx_rnbase::makeInstance('DateTimeZone', 'GMT-0'));
		$dateTime->setTimeZone(tx_rnbase::makeInstance('DateTimeZone', 'UTC'));

		return $dateTime->format('Y-m-d\TH:i:s\Z');
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
	* @return tx_mksearch_interface_IndexerDocument
	*/
	public static function indexModelByMapping(
		tx_rnbase_model_base $model, array $recordIndexMapping,
		tx_mksearch_interface_IndexerDocument $indexDoc,
		$prefix = '', array $options = array()
	) {
		foreach ($recordIndexMapping as $recordKey => $indexDocKey) {
			if(!empty($model->record[$recordKey])){
				$indexDoc->addField(
					$prefix.$indexDocKey,
					$options['keepHtml'] ? $model->record[$recordKey] :
						tx_mksearch_util_Misc::html2plain($model->record[$recordKey])
				);
			}
		}

		return $indexDoc;
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
	 * @return tx_mksearch_interface_IndexerDocument
	 */
	public static function indexArrayOfModelsByMapping(
		array $models, array $recordIndexMapping,
		tx_mksearch_interface_IndexerDocument $indexDoc, $prefix = ''
	) {
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

		return $indexDoc;
	}

	/**
	 * Adds a element to the queue
	 * @param tx_rnbase_model_base $model
	 * @return void
	 */
	public static function addModelToIndex(
		tx_rnbase_model_base $model, $tableName
	){
		static $indexSrv;
		if(!empty($model) && $model->isValid()){
			if(!$indexSrv)
				$indexSrv = tx_mksearch_util_ServiceRegistry::getIntIndexService();
			$indexSrv->addRecordToIndex($tableName, $model->getUid());
		}
	}

	/**
	 * just a wrapper for addModelToIndex and an array of models
	 * @param array $rawData
	 * @param array $models
	 * @param string $tableName
	 * @return void
	 */
	public static function addModelsToIndex($models, $tableName) {
		if(!empty($models))
			foreach ($models as $model) {
				self::addModelToIndex($model,$tableName);
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
	public static function checkInOrExcludeOptions($models,$options, $mode = 0, $optionKey = 'categories') {
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
	*/
	public static function isInValidPageTree($sourceRecord, $options) {
		$includePageTrees = self::getConfigValue('pageTrees', $options['include.']);
		$excludePageTrees = self::getConfigValue('pageTrees', $options['exclude.']);

		return
			(
				empty($includePageTrees) ||
				self::isInPageTrees($sourceRecord['pid'], $includePageTrees)
			) &&
			!self::isInPageTrees($sourceRecord['pid'], $excludePageTrees)
		;
	}

	/**
	 * @param int $pid
	 * @param array $pageTrees
	 *
	 * @return boolean
	 */
	private static function isInPageTrees($pid, $pageTrees) {
		$rootline = tx_mksearch_service_indexer_core_Config::getRootLine($pid);

		foreach ($rootline as $page) {
			if (in_array($page['uid'], $pageTrees))
				return true;
		}

		return false;
	}

	/**
	* Liefert einen Wert aus der Konfig
	* Beispiel: $key = test
	* Dann wird geprüft ob test eine kommaseparierte Liste liegt
	* Ist das nicht der Fall wird noch geprüft ob test. ein array ist
	* @param string $key
	* @param array $options
	*
	* @return array
	*/
	public static function getConfigValue($key, $options) {
		$config = array();
		if(is_array($options)) {
			if (isset($options[$key]) && strlen(trim($options[$key]))) {
				$config = t3lib_div::trimExplode(',', $options[$key]);
			}
			elseif(isset($options[$key.'.']) && is_array($options[$key.'.'])) {
				$config = $options[$key.'.'];
			}
		}
		return $config;
	}

	/**
	* Get's the page of the content element if it's not hidden/deleted
	* @return array
	*/
	public static function getPageContent($pid) {
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
}