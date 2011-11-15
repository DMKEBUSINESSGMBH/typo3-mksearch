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
tx_rnbase::load('tx_mksearch_util_ServiceRegistry');
tx_rnbase::load('tx_mksearch_util_Config');
tx_rnbase::load('tx_mksearch_util_Tree');
tx_rnbase::load('tx_rnbase_util_Misc');
require_once(PATH_t3lib.'class.t3lib_tsparser.php');

/**
 * Hooks for auto-updating search indices
 */
class tx_mksearch_hooks_IndexerAutoUpdate {

	/**
	 * (Try to) Render Typoscript recursively
	 *
	 * tslib_cObj::cObjGetSingle() renders a TS array
	 * only if the passed array structure is directly
	 * defined renderable Typoscript - it does however
	 * not care for deep array structures.
	 * This method heals this lack by traversing the
	 * given TS array recursively and calling
	 * tslib_cObj::cObjGetSingle() on each sub-array
	 * which looks like being renderable.
	 *
	 * @param array			$data	Deep data array parsed from Typoscript text
	 * @param tslib_cObj	$cObj
	 * @return array				Data array with Typoscript rendered
	 * @todo remove when this function is integrated in tx_rnbase_configurations::get()
	 */
	private function renderTS($data, tslib_cObj &$cObj) {
		foreach ($data as $key=>$value) {
			// Array key with trailing '.'?
			if (substr($key, strlen($key)-1, 1) == '.') {
				// Remove last character
				$key_1 = substr($key, 0, strlen($key)-1);
				// Same key WITHOUT '.' exists as well? Treat as renderable Typoscript!
				if (isset($data[$key_1])) {
					$data[$key_1] = $cObj->cObjGetSingle($data[$key_1], $data[$key]);
					unset($data[$key]);
				}
				// Traverse recursively
				else $data[$key] = $this->renderTS($data[$key], $cObj);
			}
		}
		return $data;
	}

	/**
	 * Update all indices, passing the given uids of records to update to the responsible indexers
	 *
	 * @param array $uids: array['table1' => array[uids], 'table2' => ...]
	 * @param bool $findRecursivePages
	 * @return void
	 */
	private function updateAllIndices(array $uids, $findRecursivePages=false) {
		global $GLOBALS;
		if(empty($uids)) return; // Nothing to do here
		tx_rnbase::load('tx_rnbase_util_Logger');
		tx_rnbase_util_Logger::debug('Start update index process', 'mksearch', array('Data'=>$uids));
		// Find subpages recursively?
		if ($findRecursivePages && isset($uids['pages']) && count($uids['pages'])) {
			$uids['pages'] = array_merge(
										$uids['pages'],
										tx_mksearch_util_Tree::getTreeUids($uids['pages'], 'pages')
									);
		}

		// Search active indices
		$srv = tx_mksearch_util_ServiceRegistry::getIntIndexService();
		$indices = $srv->findAll();
		tx_rnbase_util_Logger::debug('Found '.count($indices) . ' indices for update', 'mksearch');
		
		// Get a cObj
		$tmpTSFE = false;
		if (!isset($GLOBALS['TSFE'])) {
			tx_rnbase_util_Misc::prepareTSFE();
			$tmpTSFE = true;
		}
		$cObj = t3lib_div::makeInstance('tslib_cObj');


		try {
			// Loop through all active indices, collecting all configurations
			foreach ($indices as $index) {
				tx_rnbase_util_Logger::debug('Next index is '.$index->getTitle(), 'mksearch');
				// Container for all documents to be indexed / deleted
				$indexDocs = array(); 
				$deleteDocs = array();
				$searchEngine = tx_mksearch_util_ServiceRegistry::getSearchEngine($index);
	
				$indexConfig = $index->getIndexerOptions();
				// Ohne indexConfig kann nichts indiziert werden
				if(!$indexConfig) {
					tx_rnbase_util_Logger::notice('No indexer config found! Recheck your settings in mksearch BE-Module!', 'mksearch',
						array('Index'=> $index->getTitle(), 'indexerClass' => get_class($index), 'indexdata' => $uids));
					$this->cleanupTSFE($tmpTSFE);
					return;
				}
				if(tx_rnbase_util_Logger::isDebugEnabled())
					tx_rnbase_util_Logger::debug('Config for index '.$index->getTitle().' found.', 'mksearch', array($indexConfig));
				
				// Have to fill up $uids recursively?
				// This means to search all records
				// in all relevant tables with a matching PID.
				// Ist das wirklich notwendig?? Falls ja müssten die Datensätze mit in die Index-Queue.
				if ($findRecursivePages) {
					foreach ($indexConfig as $extKey=>$cfg) {
						foreach (array_keys($cfg) as $contentType) {
							$tables = tx_mksearch_util_Config::getDatabaseTablesForIndexer(
								// Remove trailing '.'
								substr($extKey, 0, strlen($extKey)-1),
								substr($contentType, 0, strlen($contentType)-1)
							);
							foreach ($tables as $table) {
								$sqlRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
													'uid', 	// @todo: change hard coded 'uid'?
								 					$table,
													// Not every table has a TCA config
													isset($GLOBALS['TCA'][$table]['delete']) ?
														$GLOBALS['TCA'][$table]['delete'] . '=0' :
														''
												);
								// Fill $uids
								while ($record = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($sqlRes)) {
									$uids[$table][] = $record['uid'];
								}
							}
						}
					}
					// Cleanup arrays
					foreach ($uids as &$uuids) $uuids = array_unique($uuids);
				}
	
				// Loop through all records
				foreach (array_keys($uids) as $table) {
					// Loop through all responsible indexers
					foreach (tx_mksearch_util_Config::getIndexersForDatabaseTable($table) as $indexer) {
						// Is the found indexer active for the current index?
						// This is determined by checking for an indexer configuration
						// in the current index.
						if (isset($indexConfig[$indexer['extKey'].'.'][$indexer['contentType'].'.'])) {
							$configArray = $this->renderTS($indexConfig[$indexer['extKey'].'.'][$indexer['contentType'].'.'], $cObj);
							$i_srv = tx_mksearch_util_ServiceRegistry::getIndexerService($indexer['extKey'], $indexer['contentType']);
	
							try {
								$i_srv->prepare($configArray, $uids[$table]);
							}
							catch (Exception $e) {
								// @todo: Make this action configurable?
								throw new Exception(
									'Error on updating index ' . $index->record['name'] . ": \n" .
									$e->getMessage()
								);
							}
							// Collect all index documents
							list($extKey, $contentType) = $i_srv->getContentType();
							while ($doc = $i_srv->nextItem($searchEngine->makeIndexDocInstance($extKey, $contentType))) {
								$indexDocs[] = $doc;
							}
							// Prepare deletion list
							// @todo Re-factor according to new structure of cleanup()'s return value (tablename <-> uids matrix now!)
							// @todo Also think of possible collisions when indexer 1 returns a record to be deleted, while indexer 2 just updated this record! (is there a use case for this?)
							$delUids = $i_srv->cleanup();
							
							if (count($delUids))
								$deleteDocs[] = array(
									'uids' => $delUids,
									'extKey' => $indexer['extKey'],
									'contentType' => $indexer['contentType']
								);
		   					// Unsetting doesn't help as t3lib_div::makeInstanceService()
		   					// makes the instantiated services persistent and re-uses them...
	//   						unset($i_srv);
						}
					}
				}
	
				// Finally, actually do the index update, if there is sth. to do:
				if (count($indexDocs) || count($deleteDocs)) {
					$searchEngine->openIndex($index, true);
					foreach ($indexDocs as $doc) {
						$searchEngine->indexUpdate($doc);
					} 
					// @todo Re-factor this - see above
	//				foreach ($deleteDocs as $i)
	//					foreach ($i['uids'] as $uid)
	//						$searchEngine->indexDeleteByContentUid($uid, $i['extKey'], $i['contentType']);
					$searchEngine->commitIndex();
					$searchEngine->closeIndex();
				}
			}
		}
		catch(Exception $e) {
			tx_rnbase_util_Logger::warn('An error occurred on search index update in TCE.', 'mksearch', 
				array('Exception' => $e, 'Message' => $e->getMessage()));
		}

		// Cleanup!!!
		$this->cleanupTSFE($tmpTSFE);
	}

	private function cleanupTSFE($tmpTSFE) {
		if ($tmpTSFE && isset($GLOBALS['TSFE'])) 
			unset ($GLOBALS['TSFE']);
	}
	/**
	 * Hook after saving a record in Typo3 backend:
	 * Perform index update depending on given data
	 *
	 * @param t3lib_TCEmain $tce
	 * @return void
	 */
	public function processDatamap_afterAllOperations(t3lib_TCEmain $tce) {
		// Nothing to do?
		if (!count($tce->datamap)) return;

		// @todo Make sensible for workspaces!
		if ($tce->BE_USER->workspace !== 0) return;

		return $this->processDatamap_afterAllOperationsNew($tce);
//		return $this->processDatamap_afterAllOperationsOld($tce);
	}
	private function processDatamap_afterAllOperationsNew(t3lib_TCEmain $tce) {
		$intIndexSrv = tx_mksearch_util_ServiceRegistry::getIntIndexService();
		// Cores suchen:
		$indices = $intIndexSrv->findAll();
		// Es ist nichts zu tun, da keine Cores definiert sind!
		if(empty($indices))
			return;
			
		foreach ($tce->datamap as $table=>$records) {
			if(strstr($table, 'tx_mksearch_') != false)
				continue; // Ignore internal tables
			if (empty($records))
				continue;

			// indexer besorgen
			$indexers = tx_mksearch_util_Config::getIndexersForTable($table);
			// Keine Indexer für diese Tabelle vorhanden.
			if(empty($indexers))
				continue;
			
			// Prüfen, ob für diese Tabelle ein Indexer Definiert wurden.
			$isDefined = false;
			foreach($indices as $index) {
				foreach($indexers As $indexer) {
					if($intIndexSrv->isIndexerDefined($index, $indexer)) {
						$isDefined = true;
						break 2;
					}
				}
			}
			// In die Queue legen, wenn Indexer für die Tabelle existieren.
			if($isDefined)
				foreach (array_keys($records) as $uid) {
					// New element?
					if (!is_numeric($uid)) $uid = $tce->substNEWwithIDs[$uid];
					$intIndexSrv->addRecordToIndex($table, $uid);
				}
		}
	}
	
	private function processDatamap_afterAllOperationsOld(t3lib_TCEmain $tce) {
		// Collect uids of records to be updated
		$updateUids = array();
		foreach ($tce->datamap as $table=>$records) {
			if(strstr($table, 'tx_mksearch_') != false)
				continue; // Ignore internal tables
			if (count($records)) {
				if (!isset($updateUids[$table])) $updateUids[$table] = array();
				foreach (array_keys($records) as $uid) {
					// New element?
					if (!is_numeric($uid)) $uid = $tce->substNEWwithIDs[$uid];
					$updateUids[$table][] = $uid;
				}
			}
		}
		$this->updateAllIndices($updateUids);
	}


	/**
	 * Hook after performing different record actions in Typo3 backend:
	 * Update indexes according to the just performed action
	 *
	 * @param string $command
	 * @param string $table
	 * @param int $id
	 * @param int $value
	 * @param t3lib_TCEmain $tce
	 * @return void
	 * @todo Treatment of any additional actions necessary?
	 */
	public function processCmdmap_postProcess($command, $table, $id, $value, t3lib_TCEmain $tce) {
		$indexer = tx_mksearch_util_Config::getIndexersForDatabaseTable($table);
		if (!count($indexer)) return;

		switch ($command) {
			case 'delete':
				$srv = tx_mksearch_util_ServiceRegistry::getIntIndexService();
				$srv->addRecordToIndex($table, $id, true);
				break;
			case 'undelete':
			case 'move':
				$this->updateAllIndices(array($table=>array($id)), true);
				break;
			case 'copy':
				// This task is still done by $this->processDatamap_afterAllOperations().
				break;
			default:
//				t3lib_div::debug($command, 'tx_mksearch_hooks_IndexerAutoUpdate->processCmdmap_postProcess(): other (still unhandled) command:');
				break;
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/hooks/class.tx_mksearch_hooks_IndexerAutoUpdate.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/hooks/class.tx_mksearch_hooks_IndexerAutoUpdate.php']);
}