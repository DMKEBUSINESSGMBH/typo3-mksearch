<?php
/**
 *
 *  Copyright notice
 *
 *  (c) 2011 René Nitzsche <dev@dmk-ebusiness.de>
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

/**
 * benötigte Klassen einbinden
 */


$GLOBALS['LANG']->includeLLFile('EXT:mksearch/mod1/locallang.xml');
$GLOBALS['BE_USER']->modAccess($GLOBALS['MCONF'], 1);

tx_rnbase::load('tx_rnbase_mod_BaseModule');
tx_rnbase::load('tx_mksearch_mod1_util_Misc');
tx_rnbase::load('Tx_Rnbase_Backend_Utility');

/**
 * Backend Modul für mksearch
 *
 * @author René Nitzsche
 * @package tx_mksearch
 * @subpackage tx_mksearch_mod1
 */
class  tx_mksearch_module1 extends tx_rnbase_mod_BaseModule {

	/**
	 * Method to get the extension key
	 *
	 * @return	string Extension key
	 */
	public function getExtensionKey() {
		return 'mksearch';
	}

	/**
	 * Method to set the tabs for the mainmenu
	 * Umstellung von SelectBox auf Menu
	 */
	protected function getFuncMenu() {
		$mainmenu = $this->getFormTool()->showTabMenu($this->getPid(), 'function', $this->getName(), $this->MOD_MENU['function']);
		return $mainmenu['menu'];
	}
	protected function getFormTag() {
		$modUrl = Tx_Rnbase_Backend_Utility::getModuleUrl('web_txmksearchM1', array('id' => $this->getPid()), '');
		return '<form action="' . $modUrl . '" method="POST" name="editform" id="editform">';
	}


	function moduleContent(){
		$ret = tx_mksearch_mod1_util_Misc::checkPid($this);
		if ($ret) {
			return $ret;
		}
		return parent::moduleContent();
	}

}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/index.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/index.php']);
}

/* @var $SOBE tx_mksearch_module1 */
$SOBE = tx_rnbase::makeInstance('tx_mksearch_module1');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();
