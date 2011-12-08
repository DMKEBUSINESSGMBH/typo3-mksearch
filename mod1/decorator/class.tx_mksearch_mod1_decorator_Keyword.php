<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Michael Wagner <michael.wagner@das-medienkombinat.de>
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


/**
 * Diese Klasse ist für die Darstellung von Indexer tabellen im Backend verantwortlich
 */
class tx_mksearch_mod1_decorator_Keyword {
	function __construct($mod) {
		$this->mod = $mod;
	}

	/**
	 * Returns the module
	 * @return tx_rnbase_mod_IModule
	 */
	private function getModule() {
		return $this->mod;
	}
	/**
	 *
	 * @param string $value
	 * @param string $colName
	 * @param array $record
	 * @param array $item
	 */
	public function format($value, $colName, $record, $item) {
		
		switch($colName){
			case 'link':
				// dazu müssten wir erst fe virtualisieren, ist glaub ich nicht nötig!
// 				/* @var $link tx_rnbase_util_Link */
// 				$link = tx_rnbase::makeInstance('tx_rnbase_util_Link');
// 				$link->destination($value);
// 				$ret = $link->makeUrl();
				$ret = $value;
				break;
			case 'actions':
				$formtool = $this->getModule()->getFormTool();
				$ret  = '';
				// bearbeiten link
				$ret .= $formtool->createEditLink($item->getTableName(), $item->getUid(), '');
				// hide undhide link
				$ret .= $formtool->createHideLink($item->getTableName(), $item->getUid(), $item->record['hidden']);
				// remove link
				$ret .= $formtool->createDeleteLink($item->getTableName(), $item->getUid(), '', array('confirm' => $GLOBALS['LANG']->getLL('confirmation_deletion')));
				break;
			default:
				$ret = $value;
		}
		
		return $ret;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/decorator/class.tx_mksearch_mod1_decorator_Keyword.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/decorator/class.tx_mksearch_mod1_decorator_Keyword.php']);
}
?>