<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Lars Heber <lars.heber@das-medienkombinat.de>
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

$MCONF = array();

require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');

$LANG->includeLLFile('EXT:mksearch/mod1/locallang.xml');
$BE_USER->modAccess($MCONF,1);

tx_rnbase::load('tx_rnbase_mod_BaseModule');

/**
 * Backend module for the 'mksearch' extension.
 *
 * @author	Lars Heber <lars.heber@das-medienkombinat.de>
 * @package	TYPO3
 * @subpackage	tx_mksearch
 */
class tx_mksearch_module extends tx_rnbase_mod_BaseModule {
	public $pageinfo;
    public $tabs;

	public function getExtensionKey() {
		return 'mksearch';
	}
	
	/**
	 * Method to set the tabs for the mainmenu
	 */
	function setMainMenuTabs() {
		$formTool = $this->getFormTool();
		$mainmenu = $formTool->showTabMenu($this->id, 'function', $this->MCONF['name'], $this->MOD_MENU['function']);
		$this->tabs = $mainmenu['menu'];
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/index.php']);
}

// Make instance:
$SOBE = t3lib_div::makeInstance('tx_mksearch_module');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->setMainMenuTabs();
$SOBE->main();
$SOBE->printContent();