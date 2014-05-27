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
 */
require_once(t3lib_extMgm::extPath('rn_base', 'class.tx_rnbase.php'));

/**
 * Hilfsklasse für Suchen im BE
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_mod1
 * @author Michael Wagner <michael.wagner@das-medienkombinat.de>
 */
class tx_mksearch_mod1_util_SearchBuilder {

	/**
	 * Suche nach einem Freitext. Wird ein leerer String
	 * übergeben, dann wird nicht gesucht.
	 *
	 * @param array $fields
	 * @param string $searchword
	 * @param array $cols
	 */
	public static function buildFreeText(&$fields, $searchword, array $cols = array()) {
		$result = false;
	  	if(strlen(trim($searchword))) {
	   		$joined['value'] = trim($searchword);
	   		$joined['cols'] = $cols;
	   		$joined['operator'] = OP_LIKE;
	   		$fields[SEARCH_FIELD_JOINED][] = $joined;
	   		$result = true;
	  	}
	  	return $result;
	}
	
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_mod1_util_SearchBuilder.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_mod1_util_SearchBuilder.php']);
}
