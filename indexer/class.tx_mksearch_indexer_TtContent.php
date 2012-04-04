<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_indexer
 *  @author Hannes Bochmann <hannes.bochmann@das-medienkombinat.de>
 *
 *  Copyright notice
 *
 *  (c) 2011 Hannes Bochmann <hannes.bochmann@das-medienkombinat.de>
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
tx_rnbase::load('tx_mksearch_indexer_Base');
tx_rnbase::load('tx_mksearch_service_indexer_core_Config');
tx_rnbase::load('tx_rnbase_util_Misc');
tx_rnbase::load('tx_mksearch_util_Misc');

/**
 * Just a wrapper for the different tt_content indexers.
 * it's a facade.
 * @author Hannes Bochmann <hannes.bochmann@das-medienkombinat.de>
 */
class tx_mksearch_indexer_TtContent {

	/**
	 * the appropriate indexer depending on templavoila
	 * @var tx_mksearch_indexer_Base
	 */
	protected $oIndexer;
	
	/**
	 * load the appropriate indexer depending on templavoila
	 */
	public function __construct() {
		if(t3lib_extMgm::isLoaded('templavoila')){
			$this->oIndexer = tx_rnbase::makeInstance('tx_mksearch_indexer_ttcontent_Templavoila');
		}else{
			$this->oIndexer = tx_rnbase::makeInstance('tx_mksearch_indexer_ttcontent_Normal');
		}
	}
	
	/**
	 * routes all method calls directly to the appropriate indexer.
	 * we dont do anything here!
	 * @param string $sMethod
	 * @param array $aArguments
	 * @return mixed
	 */
	public function __call($sMethod, $aArguments) {
		return call_user_func_array(array($this->oIndexer, $sMethod), $aArguments);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_TtContent.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/class.tx_mksearch_indexer_TtContent.php']);
}