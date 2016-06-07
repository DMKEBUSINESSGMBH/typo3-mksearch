<?php
/**
 *
 *  @package tx_mksearch
 *  @subpackage tx_mksearch_mod1
 *
 *  Copyright notice
 *
 *  (c) 2012 DMK E-Business GmbH <dev@dmk-ebusiness.de>
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

tx_rnbase::load('tx_rnbase_util_Templates');
tx_rnbase::load('tx_rnbase_util_DB');
tx_rnbase::load('Tx_Rnbase_Backend_Utility_Icons');

/**
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_mod1
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_util_Misc {

	/**
	 *
	 * @param tx_rnbase_mod_IModule $mod
	 * @return mixed null or string
	 */
	public static function checkPid(tx_rnbase_mod_IModule $mod) {
		$pages = self::getStorageFolders();
		if ($mod->getPid() && (empty($pages) || isset($pages[$mod->getPid()]))) {
			return null;
		}
		foreach($pages as $pid => &$page) {
			$pageinfo = Tx_Rnbase_Backend_Utility::readPageAccess($pid, $mod->perms_clause);
			$modUrl = Tx_Rnbase_Backend_Utility::getModuleUrl('web_MksearchM1', array('id' => $pid), '');
			$page  = '<a href="' . $modUrl . '">';
			$page .= Tx_Rnbase_Backend_Utility_Icons::getSpriteIconForRecord('pages', Tx_Rnbase_Backend_Utility::getRecord('pages', $pid));
			$page .= ' '.$pageinfo['title'];
			$page .= ' '.htmlspecialchars($pageinfo['_thePath']);
			$page .= '</a>';
		}
		$out  = '<div class="tables graybox">';
		$out .= '	<h2 class="bgColor2 t3-row-header">###LABEL_NO_PAGE_SELECTED###</h2>';
		if (!empty($pages)) {
			$out .= '	<ul><li>'.implode('</li><li>', $pages).'</li></ul>';
		}
		$out .= '</div>';
		return $out;
	}
	/**
	 * Liefert Page Ids zu seiten mit mksearch inhalten.
	 * @return array
	 */
	private static function getStorageFolders() {
		static $pids = false;
		if (is_array($pids)) {
			return $pids;
		}
		$pages = array_merge(
			// wir holen alle seiten auf denen indexer liegen
			tx_rnbase_util_DB::doSelect('pid as pageid', 'tx_mksearch_indices', array('enablefieldsoff' => 1)),
			// wir holen alle seiten auf denen configs liegen
			tx_rnbase_util_DB::doSelect('pid as pageid', 'tx_mksearch_indexerconfigs', array('enablefieldsoff' => 1)),
			// wir holen alle seiten auf denen composites liegen
			tx_rnbase_util_DB::doSelect('pid as pageid', 'tx_mksearch_configcomposites', array('enablefieldsoff' => 1)),
			// wir holen alle seiten auf denen keywords liegen
			tx_rnbase_util_DB::doSelect('pid as pageid', 'tx_mksearch_keywords', array('enablefieldsoff' => 1)),
			// wir holen alle seiten die mksearch beinhalten
			tx_rnbase_util_DB::doSelect('uid as pageid', 'pages', array('enablefieldsoff' => 1, 'where' => 'module=\'mksearch\''))
		);
		if (empty($pages)) {
			return array();
		}
		// wir mergen die seiten zusammen
		$pages = call_user_func_array('array_merge_recursive', array_values($pages));
		if (empty($pages['pageid'])) {
			return array();
		}
		// Wenn nur ein Eintrag existiert, haben wir hier einen String!
		if (!is_array($pages['pageid'])) {
			$pages['pageid'] = array($pages['pageid']);
		}
		// wir machen aus den pid keys
		$pages = array_flip($pages['pageid']);
		// pid 0 schließ0en wir aus
		if (isset($pages[0])) {
			unset($pages[0]);
		}
		$pids = $pages;
		return $pids;
	}

}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/util/class.tx_mksearch_mod1_util_Misc.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/util/class.tx_mksearch_mod1_util_Misc.php']);
}
