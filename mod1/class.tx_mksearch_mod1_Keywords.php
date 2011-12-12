<?php
/**
 *
 *  @package tx_mksearch
 *  @subpackage tx_mksearch_mod1
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
 ***************************************************************/

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_rnbase_mod_BaseModFunc');

/**
 * Mksearch backend module
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_mod1
 * @author Michael Wagner <michael.wagner@das-medienkombinat.de>
 */
class tx_mksearch_mod1_Keywords extends tx_rnbase_mod_BaseModFunc {

	/**
	 * Return function id (used in page typoscript etc.)
	 *
	 * @return 	string
	 */
	protected function getFuncId() {
		return 'keywords';
	}
	
	/**
	* Kindklassen implementieren diese Methode um den Modulinhalt zu erzeugen
	* @param string $template
	* @param tx_rnbase_configurations $configurations
	* @param tx_rnbase_util_FormatUtil $formatter
	* @param tx_rnbase_util_FormTool $formTool
	* @return string
	*/
	protected function getContent($template, &$configurations, &$formatter, $formTool) {
		$markerArray = array();
		
		$markerArray['###COMMON_START###'] = $markerArray['###COMMON_END###'] = '';
		if($GLOBALS['BE_USER']->isAdmin()) {
			$markerArray['###COMMON_START###'] = tx_rnbase_util_Templates::getSubpart($template,'###COMMON_START###');
			$markerArray['###COMMON_END###'] = tx_rnbase_util_Templates::getSubpart($template,'###COMMON_END###');
		}
		
		
		$templateMod = tx_rnbase_util_Templates::getSubpart($template,'###SEARCHPART###');
		$out = $this->showSearch($templateMod, $configurations, $formTool, $markerArray);
		return $out;
	}

	/**
	 * Returns search form
	 * @param 	string 						$template
	 * @param 	tx_rnbase_configurations 	$configurations
	 * @param 	tx_rnbase_util_FormTool 	$formTool
	 * @return 	string
	 */
	protected function showSearch($template, $configurations, $formTool, &$markerArray) {
		tx_rnbase::load('tx_mksearch_mod1_util_Template');
		return tx_mksearch_mod1_util_Template::parseList(
					$template,
					$this->getModule(),
					$markerArray,
					$this->getSearcher($options = array()),
					'KEYWORD'
				);
	}
	
	/**
	 * Method to get a company searcher
	 *
	 * @param 	array $options
	 * @return 	tx_mksearch_mod1_searcher_Keywords
	 */
	private function getSearcher(&$options) {
		if(!isset($options['pid'])) $options['pid'] =  $this->getModule()->id;
		return tx_rnbase::makeInstance('tx_mksearch_mod1_searcher_Keywords', $this->getModule(), $options);
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/class.tx_mksearch_mod1_Keywords.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/class.tx_mksearch_mod1_Keywords.php']);
}