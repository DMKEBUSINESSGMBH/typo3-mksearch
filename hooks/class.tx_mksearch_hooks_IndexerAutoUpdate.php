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

/**
 * Hooks for auto-updating search indices
 */
class tx_mksearch_hooks_IndexerAutoUpdate {

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

		$srv = tx_mksearch_util_ServiceRegistry::getIntIndexService();
		switch ($command) {
			case 'delete':
			case 'undelete':
			case 'move':
				$srv->addRecordToIndex($table, $id, true);
				break;
			case 'copy':
				// This task is still done by $this->processDatamap_afterAllOperations().
				break;
			// workspace action
			case 'version':
				if ($this->isPublishedToLiveWorkspace($value)) {
					$srv->addRecordToIndex($table, $id);
				}
				break;
			default:
				break;
		}
	}

	/**
	 * @return boolean
	 */
	private function isPublishedToLiveWorkspace($commandValues) {
		return ($commandValues['action'] === 'swap' && $commandValues['swapWith'] > 0);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/hooks/class.tx_mksearch_hooks_IndexerAutoUpdate.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/hooks/class.tx_mksearch_hooks_IndexerAutoUpdate.php']);
}