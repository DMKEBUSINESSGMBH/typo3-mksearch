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


tx_rnbase::load('tx_mksearch_util_ServiceRegistry');
tx_rnbase::load('tx_mksearch_util_Config');
tx_rnbase::load('tx_mksearch_util_Tree');

/**
 * Hooks for auto-updating search indices
 */
class tx_mksearch_hooks_IndexerAutoUpdate {

	/**
	 * Hook after saving a record in Typo3 backend:
	 * Perform index update depending on given data
	 *
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
	 * @return void
	 */
	public function processDatamap_afterAllOperations($dataHandler) {
		// Nothing to do?
		if (empty($dataHandler->datamap)) return;

		// daten sammeln
		$records = array();
		foreach ($dataHandler->datamap as $table => $uids) {
			$records[$table] = array();
			foreach (array_keys($uids) as $uid) {
				// New element?
				if (!is_numeric($uid)) $uid = $dataHandler->substNEWwithIDs[$uid];
				$records[$table][] = (int) $uid;
			}
		}

		return $this->processAutoUpdate($records);
	}

	/**
	 * Hook after performing different record actions in Typo3 backend:
	 * Update indexes according to the just performed action
	 *
	 * @param string $command
	 * @param string $table
	 * @param int $id
	 * @param int $value
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
	 * @return void
	 * @todo Treatment of any additional actions necessary?
	 */
	public function processCmdmap_postProcess($command, $table, $id, $value, $dataHandler) {
		$indexer = tx_mksearch_util_Config::getIndexersForDatabaseTable($table);
		if (!count($indexer)) return;

		$srv = tx_mksearch_util_ServiceRegistry::getIntIndexService();
		switch ($command) {
			case 'delete':
			case 'undelete':
			case 'move':
			case 'version':
				$srv->addRecordToIndex($table, $id, true);
				break;
			default:
				break;
		}
	}

	/**
	 * rn_base hook nachdem ein insert durchgeführt wurde.
	 * Requires rn_base 0.14.6
	 *
	 * @param array &$params
	 * @return NULL
	 */
	public function rnBaseDoInsertPost(&$params) {
		if (!$this->isRnBaseUtilDbHookActivated()) {
			return NULL;
		}

		return $this->processAutoUpdate(
			array(
				$params['tablename'] => array(
					$params['uid']
				)
			)
		);
	}

	/**
	 * rn_base hook nachdem ein Update durchgeführt wurde.
	 * Requires rn_base 0.14.6
	 *
	 * @param array &$params
	 * @return NULL
	 */
	public function rnBaseDoUpdatePost(&$params) {
		if (!$this->isRnBaseUtilDbHookActivated()) {
			return NULL;
		}

		$table = $params['tablename'];
		// wür müssen einen select machen, um die uid zu erfahren
		if (empty($params['values']['uid'])) {
			$data = array(
				'type' => 'select',
				'from' => $table,
				'where' => $params['where'],
				'options' => $params['options'],
			);
		}
		// wir haben eine uid, die übergeben wir!
		else {
			$data = $params['values']['uid'];
		}

		return $this->processAutoUpdate(array($table => array($data)));
	}

	/**
	 * Wir prüfen das nochmal für den Fall dass die Hooks zur Laufzeit deaktiviert wurden.
	 * @return boolean
	 */
	protected function isRnBaseUtilDbHookActivated() {
		return tx_rnbase_configurations::getExtensionCfgValue('mksearch', 'enableRnBaseUtilDbHook');
	}

	/**
	 * rn_base hook nachdem ein Update durchgeführt wurde.
	 * Requires rn_base 0.14.6
	 *
	 * @param array &$params
	 * @return NULL
	 */
	public function rnBaseDoDeletePre(&$params) {
		// use the same method as doUpdate
		return $this->rnBaseDoUpdatePost($params);
	}

	/**
	 * check for updatable records and fill queue
	 *
	 * @param array $records
	 *     array(
	 *         'pages' => array('1', '2', '3'. '5', '8', '13'),
	 *         'tt_content' => array('1', '2', '3'. '5', '8', '13'),
	 *     );
	 * @return NULL
	 */
	public function processAutoUpdate(array $records) {

		if (empty($records)) {
			return NULL;
		}

		// Cores suchen:
		$intIndexSrv = $this->getIntIndexService();
		$indices = $intIndexSrv->findAll();

		// Es ist nichts zu tun, da keine Cores definiert sind!
		if(empty($indices)) {
			return NULL;
		}

		foreach ($records as $table => $uidList) {
			if(strstr($table, 'tx_mksearch_') !== FALSE) {
				// Ignore internal tables
				continue;
			}

			// indexer besorgen
			$indexers = $this->getIndexersForTable($table);
			// Keine Indexer für diese Tabelle vorhanden.
			if(empty($indexers)) {
				continue;
			}

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
			if($isDefined) {
				foreach ($uidList as $uid) {
					$this->addRecordToIndex($table, $uid);
				}
			}
		}
		return NULL;
	}

	/**
	 * fügt einen datensatz für die indizierung in die queu.
	 *
	 * @param string $table
	 * @param mixed $data uid or where clause
	 * @return boolean
	 */
	protected function addRecordToIndex($table, $data) {
		$rows = $this->getUidsToIndex($table, $data);

		if (empty($rows)) {
			return FALSE;
		}

		$intIndexSrv = $this->getIntIndexService();
		foreach ($rows as $uid) {
			$intIndexSrv->addRecordToIndex($table, $uid);
		}

		return TRUE;
	}

	/**
	 * in $data kann eine uid oder ein array
	 * mit beispielsweise einem notwendigen select stecken,
	 * um die uids zu erfahren.
	 *
	 *
	 * @param mixed $data
	 * @return array
	 */
	protected function getUidsToIndex($table, $data) {

		if (is_numeric($data)) {
			return array((int) $data);
		}

		//
		if (is_array($data) && isset($data['type'])) {
			if ($data['type'] === 'select') {
				$from = empty($data['from']) ? $table : $data['from'];
				$options = empty($data['options']) || !is_array($data['options']) ? array() : $data['options'];
				$options['where'] = empty($options['where']) ? $data['where'] : $options['where'];
				$options['enablefieldsoff'] = TRUE;
				$databaseUtility = $this->getRnbaseDatabaseUtility();
				if (($rows = $databaseUtility->doSelect('uid', $from, $options))) {
					$rows = call_user_func_array('array_merge_recursive', $rows);
				}
				if (empty($rows['uid'])) {
					return array();
				}

				return is_array($rows['uid']) ? $rows['uid'] : array($rows['uid']);
			}
		}

		return array();
	}

	/**
	 * @return Tx_Rnbase_Database_Connection
	 */
	protected function getRnbaseDatabaseUtility() {
		return tx_rnbase::makeInstance('Tx_Rnbase_Database_Connection');
	}

	/**
	 * @param string $table
	 * @return array
	 */
	protected function getIndexersForTable($table) {
		return tx_mksearch_util_Config::getIndexersForTable($table);
	}
	/**
	 *
	 * @return tx_mksearch_service_internal_Index
	 */
	protected function getIntIndexService() {
		return tx_mksearch_util_ServiceRegistry::getIntIndexService();
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/hooks/class.tx_mksearch_hooks_IndexerAutoUpdate.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/hooks/class.tx_mksearch_hooks_IndexerAutoUpdate.php']);
}