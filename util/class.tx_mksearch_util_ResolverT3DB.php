<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 René Nitzsche (dev@dmk-ebusiness.de)
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



tx_rnbase::load('tx_rnbase_util_Logger');

/**
 * Default resolver to load database records from standard TYPO3 database tables.
 */
class tx_mksearch_util_ResolverT3DB {
	/**
	 * Der Resolver lädt den zu indizierenden Datensatz auf der Datenbank. D 
	 * @param array $queueData
	 */
	public function getRecords($queueData) {
		$tableName = $queueData['tablename'];
		$recId = intval($queueData['recid']);
		if(!$tableName || !$recId) {
			tx_rnbase_util_Logger::warn('[ResolverT3DB] Queue item ignored due to missing data.', 'mksearch', $queueData);
			throw new Exception('Queue item '.$queueData['uid'].' ignored due to missing data.');
		}
		$options['where'] = 'uid='.$recId;
		$options['enablefieldsoff'] = 1;
		$rows = tx_rnbase_util_DB::doSelect('*', $tableName, $options);
		if(!count($rows)) {
			// Datensatz vermutlich komplett gelöscht. Wir bereiten einen Minimal-Record für die Löschung vor
			$rows[] = array('uid' => $recId, 'deleted' => 1);
		}
		return $rows;
	}

}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_ResolverT3DB.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_ResolverT3DB.php']);
}