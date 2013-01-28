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
tx_rnbase::load('tx_rnbase_mod_ExtendedModFunc');

/**
 * Mksearch backend module
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_mod1
 * @author Michael Wagner <michael.wagner@das-medienkombinat.de>
 */
class tx_mksearch_mod1_ConfigIndizes extends tx_rnbase_mod_ExtendedModFunc {

	/**
	 * Return function id (used in page typoscript etc.)
	 *
	 * @return 	string
	 */
	protected function getFuncId() {
		return 'configindizes';
	}
	
	protected function getPageId(){
		return $this->getModule()->id;
	}
	
	public function main() {
		$out = parent::main();

		// rootpage marker hinzufügen
		if(tx_rnbase_util_BaseMarker::containsMarker($out, 'ROOTPAGE_')) {
			// Marker für Rootpage integrieren
			tx_rnbase::load('tx_mksearch_service_indexer_core_Config');
			$rootPage = tx_mksearch_service_indexer_core_Config::getSiteRootPage($this->getPageId());
			
			// keine rootpage, dann die erste seite im baum
			if(empty($rootPage))
				$rootPage = array_pop(tx_mksearch_service_indexer_core_Config::getRootLine($this->getPageId() ? $this->getPageId() : 0));
			
			$rootPage = is_array($rootPage) ? t3lib_BEfunc::readPageAccess($rootPage['uid'], $GLOBALS['BE_USER']->getPagePermsClause(1)) : false;
			
			if (is_array($rootPage)) {
				
				// felder erzeugen
				foreach($rootPage as $field => $value)
					$markerArr['###ROOTPAGE_'.strtoupper($field).'###'] = $value;
				
				$out = tx_rnbase_util_Templates::substituteMarkerArrayCached($out, $markerArr);
			} else {
				$out = tx_rnbase_util_Templates::substituteSubpart($out, '###ROOTPAGE###', '<pre>No page selected.</pre>');
			}
		}
		
		$out = $this->handleAllowUrlFopenDeactivatedHint($out);
		
		return $out;
	}

	/**
	 * @param string $template
	 * 
	 * @return string
	 */
	private function handleAllowUrlFopenDeactivatedHint($template) {
		if(tx_rnbase_util_BaseMarker::containsMarker($template, 'ALLOW_URL_FOPEN_DEACTIVATED_HINT')) {
			$allowUrlFopen = ini_get('allow_url_fopen');
			$useCurlAsHttpTransport = 
				tx_rnbase_configurations::getExtensionCfgValue('mksearch', 'useCurlAsHttpTransport');
			
			$markerArray = array();
			if(!$allowUrlFopen && !$useCurlAsHttpTransport){
				$markerArray['###ALLOW_URL_FOPEN_DEACTIVATED_HINT###'] = $GLOBALS['LANG']->getLL('allow_url_fopen_deactivated_hint');
			} else {
				$markerArray['###ALLOW_URL_FOPEN_DEACTIVATED_HINT###'] = '';
			}
			
			$template = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray);
		}
		
		return $template;
	}
	
	/**
	 * Liefert die Einträge für das Tab-Menü.
	 * @return 	array
	 */
	protected function getSubMenuItems() {
		return array(
				tx_rnbase::makeInstance('tx_mksearch_mod1_handler_Index'),
				tx_rnbase::makeInstance('tx_mksearch_mod1_handler_Composite'),
				tx_rnbase::makeInstance('tx_mksearch_mod1_handler_IndexerConfig'),
			);
	}
	
	/**
	 *
	 */
	protected function makeSubSelectors(&$selStr) {
		return false;
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/class.tx_mksearch_mod1_ConfigIndizes.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/class.tx_mksearch_mod1_ConfigIndizes.php']);
}