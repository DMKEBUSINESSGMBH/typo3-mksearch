<?php
/**
 *
 *  @package tx_mksearch
 *  @subpackage tx_mksearch_mod1
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
 */
require_once(t3lib_extMgm::extPath('rn_base', 'class.tx_rnbase.php'));
tx_rnbase::load('tx_rnbase_util_Templates');

/**
 * Die Klasse stellt Auswahlmenus zur Verfügung
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_mod1
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_util_Template {

	public static function parseBasics($template, tx_rnbase_mod_IModFunc $module) {
		$content = $template;
		$content = self::parseRootPage($content, $module);
		$content = self::handleAllowUrlFopenDeactivatedHint($content);

		// render commons
		$out = '';
		$out .= tx_rnbase_util_Templates::getSubpart($content, '###COMMON_START###');
		$out .= $content;
		$out .= tx_rnbase_util_Templates::getSubpart($content, '###COMMON_END###');

		// remove commons
		$out = tx_rnbase_util_Templates::substituteSubpart($out, '###COMMON_START###', '');
		$out = tx_rnbase_util_Templates::substituteSubpart($out, '###COMMON_END###', '');

		return $out;
	}
	private static function parseRootPage($template, tx_rnbase_mod_IModFunc $module) {
		$out = $template;

		// rootpage marker hinzufügen
		if(!tx_rnbase_util_BaseMarker::containsMarker($out, 'ROOTPAGE_'))
			return $out;

		// Marker für Rootpage integrieren
		tx_rnbase::load('tx_mksearch_service_indexer_core_Config');
		$rootPage = tx_mksearch_service_indexer_core_Config::getSiteRootPage($module->getPid());

		// keine rootpage, dann die erste seite im baum
		if(empty($rootPage))
			$rootPage = array_pop(tx_mksearch_service_indexer_core_Config::getRootLine($module->getPid() ? $module->getPid() : 0));

		$rootPage = is_array($rootPage) ? t3lib_BEfunc::readPageAccess($rootPage['uid'], $GLOBALS['BE_USER']->getPagePermsClause(1)) : false;

		if (is_array($rootPage)) {

			// felder erzeugen
			foreach($rootPage as $field => $value)
				$markerArr['###ROOTPAGE_'.strtoupper($field).'###'] = $value;

			$out = tx_rnbase_util_Templates::substituteMarkerArrayCached($out, $markerArr);
		} else {
			$out = tx_rnbase_util_Templates::substituteSubpart($out, '###ROOTPAGE###', '<pre>No page selected.</pre>');
		}
		return $out;
	}

	/**
	 * @param string $template
	 *
	 * @return string
	 */
	private static function handleAllowUrlFopenDeactivatedHint($template) {
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

	/**
	 * Setzt das Table Layout.
	 * Im moment wird nur width bearbeidet
	 *
	 * @param array 					$columns
	 * @param tx_rnbase_mod_IModule 	$mod
	 * @return columns
	 */
	public static function getTableLayout(array $columns, tx_rnbase_mod_IModule $mod){
		$aAllowed = array('width');
		// default tablelayout of doc
		$aTableLayout = $mod->getDoc()->tableLayout; // typo3/template.php
		$iCol = 0;
		foreach($columns as $column) {
			$aAddParams = array();
			foreach($aAllowed as $sAllowed) {
				if(isset($column[$sAllowed])){
					$aAddParams[] = $sAllowed.'="'.intval($column[$sAllowed]).'%"';
				}
			}
			$aTableLayout[0][$iCol] = array('<td '. implode(' ', $aAddParams).'>','</td>');
			$iCol++;
		}
		return $aTableLayout;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/util/class.tx_mksearch_mod1_util_Template.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/util/class.tx_mksearch_mod1_util_Template.php']);
}
