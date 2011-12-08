<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 das Medienkombinat
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
tx_rnbase::load('tx_mksearch_service_internal_Base');

/**
 * Service for accessing indexer configuration composite models from database
 */
class tx_mksearch_service_internal_Composite extends tx_mksearch_service_internal_Base {
	
	/**
	 * Search class of this service
	 *
	 * @var string
	 */
	protected $searchClass = 'tx_mksearch_search_Composite';
	
	/**
	 * Search database for all configurated Indices
	 *
	 * @param 	tx_mksearch_model_internal_Config 	$indexerconfig
	 * @return 	array[tx_mksearch_model_internal_Index]
	 */
	public function getByConfiguration(tx_mksearch_model_internal_Config $indexerconfig) {
		$fields['CMPCFGMM.uid_foreign'][OP_EQ_INT] = $indexerconfig->getUid();
// 		$options['debug']=1;
		return $this->search($fields, $options);
	}
	/**
	 * Search database for all configurated Indices
	 *
	 * @param 	tx_mksearch_model_internal_Index 	$indexerconfig
	 * @return 	array[tx_mksearch_model_internal_Index]
	 */
	public function getByIndex(tx_mksearch_model_internal_Index $index) {
		$fields['INDXCMPMM.uid_local'][OP_EQ_INT] = $index->getUid();
// 		$options['debug']=1;
		return $this->search($fields, $options);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/service/internal/class.tx_mksearch_service_internal_Composite.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/service/internal/class.tx_mksearch_service_internal_Composite.php']);
}