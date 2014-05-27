<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2014 DMK-EBUSINESS GmbH <rene.nitzsche@dmk-ebusiness.de>
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

require_once t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php';

/**
 * The datamapper will find a lucene field type for a given fieldname.
 *
 * @author	René Nitzsche <rene.nitzsche@dmk-ebusiness.de>
 * @package	TYPO3
 * @subpackage	tx_mksearch
 */
class tx_mksearch_service_engine_lucene_DataTypeMapper {

	private static $defaultKeywordFields = array('uid', 'extKey', 'contentType', 'tstamp', 'pid');
	private $cfg;

	public function __construct($cfg = array()) {
		$this->cfg = $cfg;
	}
	/**
	 * Find a good datatype for this fieldname.
	 *
	 * @param string $fieldName
	 * @return string one of 'text', 'keyword', 'unindexed', 'unstored', 'binary'
	 */
	public function getDataType($fieldName) {
		// Wurde was spezielles konfiguriert?
		$ret = $this->findFromCfg($fieldName);
		if(!$ret) {
			// Als default mal alles indexieren...
			$ret = 'text';
			if(in_array($fieldName, self::$defaultKeywordFields))
				$ret = 'keyword';
			elseif(self::endsWith($fieldName, '_i'))
			$ret = 'keyword';
		}
		return $ret;
	}

	/**
	 * Try to find a specific type set by index config in lucene.schema.
	 * @param string $fieldName
	 * @return string or null
	 */
	protected function findFromCfg($fieldName) {
		if(isset($this->cfg['fields.'][$fieldName.'.']['type']))
			return $this->cfg['fields.'][$fieldName.'.']['type'];
		return null;
	}
	/**
	 * test last part of a string
	 * @param string $haystack
	 * @param string $needle
	 */
	private static function endsWith($haystack, $needle) {
		return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/service/engine/class.tx_mksearch_service_engine_lucene_DataTypeMapper.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/service/engine/class.tx_mksearch_service_engine_lucene_DataTypeMapper.php']);
}
