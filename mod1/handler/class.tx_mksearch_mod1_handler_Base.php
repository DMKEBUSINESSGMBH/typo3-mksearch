<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_mod1
 *
 *  Copyright notice
 *
 *  (c) 2011 DMK E-Business GmbH <dev@dmk-ebusiness.de>
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
 * Backend Modul Index
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_mod1
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
abstract class tx_mksearch_mod1_handler_Base {

	/**
	 *
	 * Enter description here ...
	 * @param string $template
	 * @param tx_rnbase_mod_IModule $mod
	 * @param array $options
	 *
	 * @return string
	 */
	public function showScreen($template, tx_rnbase_mod_IModule $mod, $options) {
		tx_rnbase::load('tx_mksearch_mod1_util_Template');
		return tx_mksearch_mod1_util_Template::parseList(
					$template,
					$mod,
					$markerArray = array(),
					$this->getSearcher($mod, $options = array()),
					strtoupper($this->getSubID())
				);
	}

	/**
	 * Datenverarbeitung.
	 *
	 * @param tx_rnbase_mod_IModule $mod
	 */
	public function handleRequest(tx_rnbase_mod_IModule $mod) {
		return '';
	}

	/**
	 *
	 * @param 	tx_rnbase_mod_IModule 	$mod
	 * @param 	array 					$options
	 * @return 	tx_mksearch_mod1_searcher_abstractBase
	 */
	abstract protected function getSearcher(tx_rnbase_mod_IModule $mod, &$options);
	
	/**
	 * Returns the label for Handler in SubMenu. You can use a label-Marker.
	 * @return string
	 */
	public function getSubLabel() {
		return '###LABEL_HANDLER_'.strtoupper($this->getSubID()).'###';
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/handler/class.tx_mksearch_mod1_handler_Base.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/handler/class.tx_mksearch_mod1_handler_Base.php']);
}