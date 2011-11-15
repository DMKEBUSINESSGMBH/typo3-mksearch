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

require_once(PATH_t3lib.'class.t3lib_svbase.php');
require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_mksearch_service_Base');

/**
 * Service for accessing models from database
 */
class tx_mksearch_service_irfaq_Category extends tx_mksearch_service_Base {
	
	/**
	 * returns all categories of the given question
	 *
	 * @param tx_mksearch_model_irfaq_Question $oQuestion
	 * @return array[tx_mksearch_model_irfaq_Category]
	 */
	public function getByQuestion($oQuestion){
	    $fields = array();
	    $options = array();
	    $fields['IRFAQ_QUESTION_CATEGORY_MM.uid_local'][OP_EQ_INT] = $oQuestion->getUid();
	    return $this->search($fields, $options);
	}
	
	/**
	 * Liefert die zugehörige Search-Klasse zurück
	 *
	 * @return string
	 */
	public function getSearchClass(){return 'tx_mksearch_search_irfaq_Category';}	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/service/internal/class.tx_mksearch_service_internal_Base.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/service/internal/class.tx_mksearch_service_internal_Base.php']);
}