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
			case 'undelete':
			case 'move':
				$srv = tx_mksearch_util_ServiceRegistry::getIntIndexService();
				$srv->addRecordToIndex($table, $id, true);
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