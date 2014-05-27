<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 René Nitzsche <nitzsche@das-medienkombinat.de>
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
tx_rnbase::load('tx_mksearch_indexer_BaseMedia');
tx_rnbase::load('tx_mksearch_service_indexer_core_Config');
tx_rnbase::load('tx_mksearch_util_Misc');
tx_rnbase::load('tx_rnbase_util_Misc');
tx_rnbase::load('tx_rnbase_util_Logger');


/**
 * Indexer service for dam.media called by the "mksearch" extension.
 */
class tx_mksearch_indexer_DamMedia
	extends tx_mksearch_indexer_BaseMedia {

	/**
	 * Return content type identification.
	 * This identification is part of the indexed data
	 * and is used on later searches to identify the search results.
	 * You're completely free in the range of values, but take care
	 * as you at the same time are responsible for
	 * uniqueness (i.e. no overlapping with other content types) and
	 * consistency (i.e. recognition) on indexing and searching data.
	 *
	 * @return array([extension key], [key of content type])
	 */
	public static function getContentType() {
		return array('dam', 'media');
	}

	/**
	 * Liefert den namen zur Basistabelle
	 *
	 * @return string
	 */
	protected function getBaseTableName() {
		return 'tx_dam';
	}

	/**
	 * Den Absoluten Server-Pfad zur Datei.
	 *
	 * @param string $tableName
	 * @param array $sourceRecord
	 * @return string
	 */
	protected function getRelFileName($tableName, $sourceRecord) {
		return $sourceRecord['file_path'] . $sourceRecord['file_name'];
	}

	/**
	 * Liefert die Dateiendung.
	 *
	 * @param string $tableName
	 * @param array $sourceRecord
	 * @return string
	 */
	protected function getFileExtension($tableName, $sourceRecord) {
		return $sourceRecord['file_type'];
	}

	/**
	 * Liefert den Pfad zur Datei (ohne Dateinamen).
	 *
	 * @param string $tableName
	 * @param array $sourceRecord
	 * @return string
	 */
	protected function getFilePath($tableName, $sourceRecord) {
		return $sourceRecord['file_path'];
	}

}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_DamMedia.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_DamMedia.php']);
}