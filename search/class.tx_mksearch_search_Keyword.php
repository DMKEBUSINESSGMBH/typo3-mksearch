<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_search
 *
 *  Copyright notice
 *
 *  (c) 2011 DMK E-Business GmbH <dev@dmk-ebusiness.de>
 *  All rights reserved
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 ***************************************************************/

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_rnbase_util_SearchBase');

/**
 * Class to search keywords from database
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_search
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_search_Keyword extends tx_rnbase_util_SearchBase {

	/**
	 * Return table mappings
	 *
	 * MUST be public as we need these data from external!
	 */
	public function getTableMappings() {
		$tableMapping = array();
		$tableMapping['KEYWORD'] = 'tx_mksearch_keywords';
		return $tableMapping;
	}

	/**
	 * return name of base table
	 * MUST be public as we need these data from external!
	 * @see util/tx_rnbase_util_SearchBase#getBaseTable()
	 */
	public function getBaseTable() {
		return 'tx_mksearch_keywords';
	}
	/**
	 * return name of base table
	 * MUST be public as we need these data from external!
	 * @see util/tx_rnbase_util_SearchBase#getBaseTable()
	 */
	public function getBaseTableAlias() {
		return 'KEYWORD';
	}
	
	public function getWrapperClass() {
		return 'tx_mksearch_model_internal_Keyword';
	}
	
	protected function getJoins($tableAliases) {
	  	$join = '';
		return $join;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/search/class.tx_mksearch_search_Keyword.php']) {
  include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/search/class.tx_mksearch_search_Keyword.php']);
}