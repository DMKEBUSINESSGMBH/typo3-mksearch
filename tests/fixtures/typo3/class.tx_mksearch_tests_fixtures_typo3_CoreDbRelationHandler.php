<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2014 DMK E-BUSINESS GmbH <kontakt@dmk-ebusiness.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


/**
 * XCLASS des RelationHandlers,
 * um diesen für noch verwendeten die DB-Tests zu deaktivieren.
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_tests
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_fixtures_typo3_CoreDbRelationHandler
	extends TYPO3\CMS\Core\Database\RelationHandler {

	/**
	 * Initialization of the class.
	 *
	 * @param string $itemlist List of group/select items
	 * @param string $tablelist Comma list of tables, first table takes priority if no table is set for an entry in the list.
	 * @param string $MMtable Name of a MM table.
	 * @param integer $MMuid Local UID for MM lookup
	 * @param string $currentTable Current table name
	 * @param integer $conf TCA configuration for current field
	 * @return void
	 * @todo Define visibility
	 */
	public function start($itemlist, $tablelist, $MMtable = '', $MMuid = 0, $currentTable = '', $conf = array()) {
		$this->tableArray[$tablelist] = array();
	}

}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/fixtures/typo3/class.tx_mksearch_tests_fixtures_typo3_CoreDbRelationHandler.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/fixtures/typo3/class.tx_mksearch_tests_fixtures_typo3_CoreDbRelationHandler.php']);
}