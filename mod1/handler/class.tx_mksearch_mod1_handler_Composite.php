<?php
/**
 * 	@package tx_mksearch
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
tx_rnbase::load('tx_mksearch_mod1_handler_Base');
tx_rnbase::load('tx_rnbase_mod_IModHandler');

/**
 * Backend Modul Index
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_mod1
 * @author Michael Wagner <michael.wagner@das-medienkombinat.de>
 */
class tx_mksearch_mod1_handler_Composite extends tx_mksearch_mod1_handler_Base implements tx_rnbase_mod_IModHandler {

	/**
	 * Method to get a company searcher
	 *
	 * @param 	tx_rnbase_mod_IModule 	$mod
	 * @param 	array 					$options
	 * @return 	tx_mksearch_mod1_searcher_abstractBase
	 */
	protected function getSearcher(tx_rnbase_mod_IModule $mod, &$options) {
		if(!isset($options['pid'])) $options['pid'] =  $mod->id;
		return tx_rnbase::makeInstance('tx_mksearch_mod1_searcher_Composite', $mod, $options);
	}

	/**
	 * Returns a unique ID for this handler. This is used to created the subpart in template.
	 * @return string
	 */
	public function getSubID() {
		return 'Composite';
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/handler/class.tx_mksearch_mod1_handler_Composite.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/handler/class.tx_mksearch_mod1_handler_Composite.php']);
}