<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_tests
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
tx_rnbase::load('tx_rnbase_cache_Manager');
tx_rnbase::load('tx_rnbase_util_TYPO3');
tx_rnbase::load('tx_rnbase_util_Spyc');

/**
 * Statische Hilfsmethoden für Tests
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_tests
 */
class tx_mksearch_tests_Util {

	/**
	 * Liefert einen kompletten Dateipfad für eine Datei in einer Extension
	 * @param $filename
	 * @param $dir
	 * @param $extKey
	 * @return string
	 */
	public static function getFixturePath($filename, $dir = 'tests/fixtures/', $extKey = 'mksearch') {
		return t3lib_extMgm::extPath($extKey).$dir.$filename;
	}
	
	/**
   	 * Lädt ein COnfigurations Objekt nach mit der TS aus der Extension
   	 * Dabei wird alles geholt was in "plugin.tx_$extKey", "lib.$extKey." und
   	 * "lib.links." liegt
   	 * @return tx_rnbase_configurations
   	 */
  	public static function loadPageTS4BE() {
  		$extKeyTS = $extKey = 'mksearch';
  		
	    t3lib_extMgm::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:mksearch/static/static_extension_template/setup.txt">');
	
	    $pageTSconfig = t3lib_BEfunc::getPagesTSconfig(0);
	    $tempConfig = $pageTSconfig['plugin.']['tx_'.$extKeyTS.'.'];
	    $tempConfig['lib.'][$extKeyTS.'.'] = $pageTSconfig['lib.'][$extKeyTS.'.'];
	    $tempConfig['lib.']['links.'] = $pageTSconfig['lib.']['links.'];
	    $pageTSconfig = $tempConfig;

	    $qualifier = $pageTSconfig['qualifier'] ? $pageTSconfig['qualifier'] : $extKeyTS;
	
	  	return $pageTSconfig;
  	}
  	
/**
   	 * Lädt ein COnfigurations Objekt nach mit der TS aus der Extension
   	 * Dabei wird alles geholt was in "plugin.tx_$extKey", "lib.$extKey." und
   	 * "lib.links." liegt
   	 * @return tx_rnbase_configurations
   	 */
  	public static function loadConfig4BE($pageTSconfig) {
	    tx_rnbase::load('tx_rnbase_configurations');
	    tx_rnbase::load('tx_rnbase_util_Misc');
	
	    tx_rnbase_util_Misc::prepareTSFE(); // Ist bei Aufruf aus BE notwendig!
	    $GLOBALS['TSFE']->config = array();
	    $cObj = t3lib_div::makeInstance('tslib_cObj');
	    
	    $configurations = new tx_rnbase_configurations();
	    $configurations->init($pageTSconfig, $cObj, 'mksearch', 'mksearch');
	
	  	return $configurations;
  	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/class.tx_mksearch_tests_Util.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/class.tx_mksearch_tests_Util.php']);
}