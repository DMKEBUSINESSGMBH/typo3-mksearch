<?php
/**
 *
 *  @package tx_mksearch
 *  @subpackage tx_mksearch_mod1
 *
 *  Copyright notice
 *
 *  (c) 2011 das MedienKombinat GmbH <kontakt@das-medienkombinat.de>
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
tx_rnbase::load('tx_rnbase_mod_BaseModFunc');
tx_rnbase::load('tx_rnbase_util_Templates');
tx_rnbase::load('tx_mksearch_util_ServiceRegistry');
tx_rnbase::load('tx_mksearch_mod1_util_IndexStatusHandler');


/**
 * Mksearch backend module
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_mod1
 * @author Michael Wagner <michael.wagner@das-medienkombinat.de>
 */
class tx_mksearch_mod1_IndizeIndizes extends tx_rnbase_mod_BaseModFunc {

	/**
	 * Return function id (used in page typoscript etc.)
	 *
	 * @return string
	 */
	protected function getFuncId() {
		return 'indizeindizes';
	}
	
	protected function getPageId() {
		return $this->getModule()->id;
	}
	
	/**
	 * Return the actual html content
	 *
	 * Actually, just the list view of the defined storage folder
	 * is displayed within an iframe.
	 *
	 * @param string $template
	 * @param tx_rnbase_configurations $configurations
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param tx_rnbase_util_FormTool $formTool
	 * @return string
	 */
	protected function getContent($template, &$configurations, &$formatter, $formTool) {
		$status = array();
		if(!$GLOBALS['BE_USER']->isAdmin())
			return '';

		$oIntIndexSrv = tx_mksearch_util_ServiceRegistry::getIntIndexService();
			
		if(t3lib_div::_GP('updateIndex')) {
			$status[] = $this->handleReset($oIntIndexSrv, $configurations);
			$status[] = $this->handleClear($oIntIndexSrv, $configurations);
			$status[] = $this->handleTrigger($oIntIndexSrv, $configurations);
		}
		
		$markerArray = array();
		$bIsStatus = count($status=array_filter($status)) ? true : false;
		$markerArray['###ISSTATUS###'] = $bIsStatus ? 'block' : 'none';
		$markerArray['###STATUS###'] = $bIsStatus ? implode('<br />', $status) : '';
		$markerArray['###QUEUESIZE###'] = $oIntIndexSrv->countItemsInQueue();
		$indexItems = intval(t3lib_div::_GP('triggerIndexingQueueCount'));
		$markerArray['###INDEXITEMS###'] = $indexItems > 0 ? $indexItems : 100;

		$markerArray['###CORE_STATUS###'] = tx_mksearch_mod1_util_IndexStatusHandler::getInstance()->handleRequest();

		$this->showTables($template, $configurations, $formTool, $markerArray, $oIntIndexSrv);
		
		$out = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray);
		
		return $out;
	}
	/**
	 * Returns search form
	 * @param 	string $template
	 * @param 	tx_rnbase_configurations $configurations
	 * @param 	tx_rnbase_util_FormTool $formTool
	 * @param 	array $markerArray
	 * @param 	tx_mksearch_service_internal_Index $oIntIndexSrv
	 */
	protected function showTables($template, $configurations, $formTool, &$markerArray, $oIntIndexSrv) {
		tx_rnbase::load('tx_mksearch_util_Config');
		$aIndexers = tx_mksearch_util_Config::getIndexers();
		$aIndices = $oIntIndexSrv->getByPageId($this->getPageId());

		$aDefinedTables = array();
		
		foreach($aIndices as $oIndex){
			foreach($aIndexers as $indexer){
				if(!$oIntIndexSrv->isIndexerDefined($oIndex, $indexer))
					continue;

				list($extKey, $contentType) = $indexer->getContentType();
				$aTables = tx_mksearch_util_Config::getDatabaseTablesForIndexer($extKey, $contentType);
				$aRecord = array();
				$aRecord['name'] = array_shift($aTables);
				// Die Tabelle nur einmal darstellen, auch wenn Sie in mehreren Indexern definiert ist.
				if(!in_array($aRecord, $aDefinedTables)) {
					$aDefinedTables[] = $aRecord;
				}
			}
		}
		
		$decor = tx_rnbase::makeInstance('tx_mksearch_mod1_decorator_Indizes', $this->getModule());
		$columns = array(
			'name' => array('title' => 'label_table_name', 'decorator' => $decor),
			'queuecount' => array('title' => 'label_table_queuecount', 'decorator' => $decor),
			'clear' => array('title' => 'label_table_clear', 'decorator' => $decor),
			'resetG' => array('title' => 'label_table_resetG', 'decorator' => $decor),
			'reset' => array('title' => 'label_table_reset', 'decorator' => $decor),
		);

		if($count = count($aDefinedTables)) {
			tx_rnbase::load('tx_rnbase_mod_Tables');
			$arr = tx_rnbase_mod_Tables::prepareTable($aDefinedTables, $columns, $this->formTool, $this->options);
			$content = $this->mod->doc->table($arr[0]);
		}
		else {
	  		$content = '<p><strong>###LABEL_NO_INDEXERS_FOUND###</strong></p><br/>';
	  		$count = '';
		}
		
		$markerArray['###TABLES_TABLE###'] = $content;
		$markerArray['###TABLES_COUNT###'] = count($aDefinedTables);
	}

	private function getPidList() {
		// wir holen uns eine liste von page ids
		// nur elemente dieser page id dürfen in der Queue landen
		tx_rnbase::load('tx_mksearch_service_indexer_core_Config');
		$pidList = tx_mksearch_service_indexer_core_Config::getPidListFromSiteRootPage($this->getPageId(), 999);

		if(empty($pidList)) return $pidList;
		
		// ausnahmen
		
		// pageids für dam
		if (t3lib_extMgm::isLoaded('dam')) {
			$pages = tx_rnbase_util_DB::doSelect('uid', 'pages', array('where' => 'pages.module=\'dam\''));
			$pidList .= (empty($pidList) ? '' : ',').implode(',', array_values($pages[0]));
		}
		return $pidList;
	}
	
	/**
	 * Handle clear command from request. This means all record of selected tables have to be removed from 
	 * indexing queue.
	 * @param tx_mksearch_service_internal_Index $oIntIndexSrv
	 * @param 	tx_rnbase_configurations $configurations
	 * @return string
	 */
	private function handleClear($oIntIndexSrv, &$configurations) {
		$aTables = t3lib_div::_GP('clearTables');
		if (!(is_array($aTables) && (!empty($aTables)))) return '';
		
		$status = $configurations->getLL('label_queue_cleared');
		foreach ($aTables as $sTable) {
			$oIntIndexSrv->clearIndexingQueueForTable($sTable);
		}

		$status .= '<ul><li>'.implode('</li><li/>', $aTables).'</li></ul>';
		return $status;
	}

	/**
	 * Handle reset command from request
	 * @param tx_mksearch_service_internal_Index $oIntIndexSrv
	 * @param 	tx_rnbase_configurations $configurations
	 * @return string
	 */
	private function handleReset($oIntIndexSrv, &$configurations) {
		// reset aller elemente im siteroot
		$aTables['pid'] = t3lib_div::_GP('resetTables');
		// global, alle elemente
		$aTables['global'] = t3lib_div::_GP('resetTablesG');
		if (
			(!is_array($aTables['pid']) && empty($aTables['pid']))
			&& (!is_array($aTables['global']) && empty($aTables['global']))
		) return '';

		$status = $configurations->getLL('label_queue_reseted');
		foreach ($aTables as $key => $aResets) {
			if (is_array($aResets)) {
				foreach ($aResets as $sTable) {
					$options = array();
					
					if ($key == 'pid') {
						// wir holen uns eine liste von page ids
						// nur elemente dieser page id dürfen in der Queue landen
						$pidList = $this->getPidList();
						if (!empty($pidList)) $options['where'] = 'pid IN ('.$pidList.')';
					}
					$oIntIndexSrv->resetIndexingQueueForTable($sTable, $options );
				}
			}
		}
		
		$status .= 	'<ul><li>'.
					implode('</li><li/>',
						array_unique(
							array_merge(
								is_array($aTables['pid']) ? $aTables['pid'] : array(),
								is_array($aTables['global']) ? $aTables['global'] : array()
							)
						)
					).
					'</li></ul>';
		return $status;
	}
	
	/**
	 * Handle trigger command from request
	 * @param tx_mksearch_service_internal_Index $oIntIndexSrv
	 * @param 	tx_rnbase_configurations $configurations
	 * @return string
	 */
	private function handleTrigger($oIntIndexSrv, &$configurations) {
		$triggerIndexingQueue = t3lib_div::_GP('triggerIndexingQueue');
		if (!$triggerIndexingQueue) return '';

		$status = '';
		$indexItems = intval(t3lib_div::_GP('triggerIndexingQueueCount'));
		$options['limit'] = $indexItems > 0 ? $indexItems : 100;
		$options['pid'] = $this->getPageId();
		$rows = $oIntIndexSrv->triggerQueueIndexing($options);
		if ($rows) {
			$status .= $configurations->getLL('label_queue_indexed');
			// Komplette Anzahl ausgeben.
			$status .= ' '.count(call_user_func_array('array_merge', array_values($rows)));
			// Anzahl einzelner Tabellen ausgeben
			foreach($rows as $table => $row) {
				$rows[$table] = $table.': '.count($row);
			}
			$status .= '<ul><li>'.implode('</li><li/>', array_values($rows)).'</li></ul>';
		} else {
			$status .= $configurations->getLL('label_queue_indexed_empty');
		}
		return $status;
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/class.tx_mksearch_mod1_IndizeIndizes.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/class.tx_mksearch_mod1_IndizeIndizes.php']);
}