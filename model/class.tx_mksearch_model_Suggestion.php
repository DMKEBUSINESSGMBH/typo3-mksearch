<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_model
 *  @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2010 Hannes Bochmann <hannes.bochmann@das-medienkombinat.de>
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
tx_rnbase::load('tx_rnbase_model_base');

/**
 * Model für eine Suggestion
 * @package tx_mksearch
 * @subpackage tx_mksearch_model
 */
class tx_mksearch_model_Suggestion extends tx_rnbase_model_base  {
	/**
	 * Gibt ein Suggestion Model zurück
	 * @param string $field 
	 * @param string $id
	 * @param mixed $label Das Label kann ein String, oder ein Array sein 
	 * @return void
	 */
	public function __construct($aSuggestion) {
		$this->record['value'] = $this->uid = $aSuggestion['value'];
		$this->record['searchWord'] = $aSuggestion['searchWord'];
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/model/class.tx_mksearch_model_Suggestion.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/model/class.tx_mksearch_model_Suggestion.php']);
}