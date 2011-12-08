<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 das Medienkombinat
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
 * Class to search index configurations from database
 */
class tx_mksearch_search_Config extends tx_rnbase_util_SearchBase {

	/**
	 * Return table mappings
	 *
	 * MUST be public as we need these data from external!
	 */
	public function getTableMappings() {
		$tableMapping = array();
		$tableMapping['CFG'] = self::getBaseTable();
		$tableMapping['CMP'] = 'tx_mksearch_configcomposites';
		$tableMapping['CMPCFGMM'] = 'tx_mksearch_configcomposites_indexerconfigs_mm';
		$tableMapping['INDX'] = 'tx_mksearch_indices';
		$tableMapping['INDXCMPMM'] = 'tx_mksearch_indices_configcomposites_mm';
		return $tableMapping;
	}

	/**
	 * return name of base table
	 * MUST be public as we need these data from external!
	 * @see tx_rnbase_util_SearchBase::getBaseTable()
	 */
	public function getBaseTable() {
		return 'tx_mksearch_indexerconfigs';
	}
	/**
	 * return name of base table
	 * MUST be public as we need these data from external!
	 * @see util/tx_rnbase_util_SearchBase#getBaseTable()
	 */
	public function getBaseTableAlias() {
		return 'CFG';
	}
	
	public function getWrapperClass() {
		return 'tx_mksearch_model_internal_Config';
	}
	
	protected function getJoins($tableAliases) {
	  	$join = '';
	  	$tableMapping = $this->getTableMappings();
	  	
		 // Additional table "composites" or its MM table?
		if(isset($tableAliases['CMPCFGMM']) or isset($tableAliases['CMP']))
			$join .=
	    		' JOIN ' . $tableMapping['CMPCFGMM'] .
	    			' ON ' . $tableMapping['CFG'] . '.uid = ' . $tableMapping['CMPCFGMM'] . '.uid_foreign';
	  	
		// Additional table "composites"?
	  	if(isset($tableAliases['CMP'])) {
	    	$join .= ' JOIN ' . $tableMapping['CMP'] . ' ON ' . $tableMapping['CMPCFGMM'] . '.uid_local = ' . $tableMapping['CMP'] . '.uid';
	    }
	    
	    // Additional table "indices" or its MM table?
		if(isset($tableAliases['INDXCMPMM']) or isset($tableAliases['INDX']))
			$join .=
	    		' JOIN ' . $tableMapping['INDXCMPMM'] .
	    			' ON ' . $tableMapping['CMP'] . '.uid = ' . $tableMapping['INDXCMPMM'] . '.uid_foreign';
	  	
		// Additional table "indice"?
	  	if(isset($tableAliases['INDX'])) {
	    	$join .= ' JOIN ' . $tableMapping['INDX'] . ' ON ' . $tableMapping['INDXCMPMM'] . '.uid_local = ' . $tableMapping['INDX'] . '.uid';
	    }
			
		return $join;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/search/class.tx_mksearch_search_Config.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/search/class.tx_mksearch_search_Config.php']);
}