<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_model
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
***************************************************************/
require_once(t3lib_extMgm::extPath('rn_base', 'class.tx_rnbase.php'));
tx_rnbase::load('tx_rnbase_model_base');

/**
 * Model for indices
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_model
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_model_internal_Keyword extends tx_rnbase_model_base {

	/**
	 * Return this model's table name
	 *
	 * @return string
	 */
	public function getTableName() {
		return 'tx_mksearch_keywords';
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/model/internal/class.tx_mksearch_model_internal_Keyword.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/model/internal/class.tx_mksearch_model_internal_Keyword.php']);
}