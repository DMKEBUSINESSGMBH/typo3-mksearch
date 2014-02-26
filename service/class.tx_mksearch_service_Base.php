<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_service
 *  @author Hannes Bochmann
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
tx_rnbase::load('tx_rnbase_util_SearchBase');

/**
 * Base service class
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_service
 */
abstract class tx_mksearch_service_Base extends t3lib_svbase {

	/**
	 * Return name of search class
	 *
	 * @return string
	 */
	abstract public function getSearchClass();

	/**
	 * @return tx_rnbase_util_SearchBase
	 */
	public function getSearcher(){
		return tx_rnbase_util_SearchBase::getInstance($this->getSearchClass());
	}
	
	/**
	 * Search database
	 *
	 * @param array $fields
	 * @param array $options
	 * @return array[tx_rnbase_model_base]
	 */
	public function search($fields, $options) {
		$searcher = $this->getSearcher();

		// On default, return hidden and deleted fields in backend
		// @TODO: realy return deleted fields? make Konfigurable!
		if (
		TYPO3_MODE == 'BE' &&
		!isset($options['enablefieldsoff']) &&
		!isset($options['enablefieldsbe']) &&
		!isset($options['enablefieldsfe'])
		) {
			$options['enablefieldsoff'] = true;
		}

		return $searcher->search($fields, $options);
	}

	/**
	 * Search the item for the given uid
	 *
	 * @TODO: 	Achtung,
	 * 			tx_rnbase_util_SearchBase::getWrapperClass() ist eigentlich protected!
	 *
	 * @param int $ct
	 * @return tx_rnbase_model_base
	 */
	public function get($uid) {
		$searcher = tx_rnbase_util_SearchBase::getInstance($this->getSearchClass());
		return tx_rnbase::makeInstance($searcher->getWrapperClass(), $uid);
	}

	/**
	 * Find all records
	 *
	 * @return array[tx_rnbase_model_base]
	 */
	public function findAll(){
		return $this->search(array(), array());
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/service/class.tx_mksearch_service_Base.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/service/class.tx_mksearch_service_Base.php']);
}