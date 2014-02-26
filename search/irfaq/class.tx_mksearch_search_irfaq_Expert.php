<?php
/**
 *	@package tx_mksearch
 *  @subpackage tx_mksearch_search_irfaq
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

/**
 * benötigte Klassen einbinden
 */
require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_rnbase_util_SearchBase');

/**
 * 
 * @package tx_mksearch
 * @subpackage tx_mksearch_search_irfaq
 * @author Hannes Bochmann
 */
class tx_mksearch_search_irfaq_Expert extends tx_rnbase_util_SearchBase {
	
	/**
	 * getTableMappings()
	 */
	protected function getTableMappings() {
		$tableMapping = array();

		// Hook to append other tables
		tx_rnbase_util_Misc::callHook('mksearch','search_irfaq_Expert_getTableMapping_hook',
			array('tableMapping' => &$tableMapping), $this);

		return $tableMapping;
 	}

	/**
	 * useAlias()
	 */
	protected function useAlias() {
		return true;
	}

	/**
	 * getBaseTableAlias()
	 */
	protected function getBaseTableAlias() {
		return 'IRFAQ_EXPERT';
	}

	/**
	 * getBaseTable()
	 */
	protected function getBaseTable() {
		return 'tx_irfaq_expert';
	}

	/**
	 * getWrapperClass()
	 */
	function getWrapperClass() {
		return 'tx_mksearch_model_irfaq_Expert';
	}

	/**
	 * Liefert alle JOINS zurück
	 *
	 * @param array $tableAliases
	 * @return string
	 */
	protected function getJoins($tableAliases) {
		$join = '';

		// Hook to append other tables
		tx_rnbase_util_Misc::callHook('mksearch','search_irfaq_Expert_getJoins_hook',
			array('join' => &$join, 'tableAliases' => $tableAliases), $this);
		return $join;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/search/irfaq/class.tx_mksearch_search_irfaq_Expert.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/search/irfaq/class.tx_mksearch_search_irfaq_Expert.php']);
}

?>