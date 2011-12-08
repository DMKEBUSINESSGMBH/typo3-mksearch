<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Lars Heber <lars.heber@das-medienkombinat.de>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_rnbase_mod_ExtendedModFunc');

/**
 * Mksearch backend module
 */
class tx_mksearch_mod1_ConfigIndizes extends tx_rnbase_mod_ExtendedModFunc {

	/**
	 * Return function id (used in page typoscript etc.)
	 *
	 * @return 	string
	 */
	protected function getFuncId() {
		return 'configindizes';
	}
	
	/**
	 * Liefert die Einträge für das Tab-Menü.
	 * @return 	array
	 */
	protected function getSubMenuItems() {
		return array(
				tx_rnbase::makeInstance('tx_mksearch_mod1_handler_Index'),
				tx_rnbase::makeInstance('tx_mksearch_mod1_handler_Composite'),
				tx_rnbase::makeInstance('tx_mksearch_mod1_handler_IndexerConfig'),
			);
	}
	
	/**
	 *
	 */
	protected function makeSubSelectors(&$selStr) {
		return false;
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/class.tx_mksearch_mod1_ConfigIndizes.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/class.tx_mksearch_mod1_ConfigIndizes.php']);
}