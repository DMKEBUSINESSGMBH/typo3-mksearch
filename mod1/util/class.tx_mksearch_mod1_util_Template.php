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
 */
require_once(t3lib_extMgm::extPath('rn_base', 'class.tx_rnbase.php'));
tx_rnbase::load('tx_rnbase_util_Templates');

/**
 * Die Klasse stellt Auswahlmenus zur Verfügung
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_mod1
 * @author Michael Wagner <michael.wagner@das-medienkombinat.de>
 */
class tx_mksearch_mod1_util_Template {

	/**
	 *
	 * @param 	string 									$template
	 * @param 	tx_rnbase_mod_IModule 					$mod
	 * @param 	array 									$markerArray
	 * @param 	tx_mksearch_mod1_searcher_abstractBase 	$searcher
	 * @param 	string 									$marker
	 * @return 	string
	 */
	public static function parseList($template, $mod, &$markerArray, $searcher, $marker) {
		$formTool = $mod->getFormTool();
		
		// die tabelle von der suchklasse besorgen (für die buttons)
		$table = $searcher->getService()->getSearcher()->getBaseTable();
		
		// Suchformular
		$markerArray['###'.$marker.'_SEARCHFORM###'] = $searcher->getSearchForm();
		// button für einen neuen Eintrag
		$markerArray['###BUTTON_'.$marker.'_NEW###'] = $formTool->createNewLink(
				$table,
				$mod->id,
				$GLOBALS['LANG']->getLL('label_add_'.strtolower($marker))
			);
		// ergebnisliste und pager
		$data = $searcher->getResultList();
		$markerArray['###'.$marker.'_LIST###'] = $data['table'];
		$markerArray['###'.$marker.'_SIZE###'] = $data['totalsize'];
		$markerArray['###'.$marker.'_PAGER###'] = $data['pager'];
		$out = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray);
		return $out;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mklib/mod1/util/class.tx_mksearch_mod1_util_Template.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mklib/mod1/util/class.tx_mksearch_mod1_util_Template.php']);
}
