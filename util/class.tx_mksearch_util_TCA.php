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

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_mksearch_util_Config');
/**
 * Tools for use in TCA
 */
class tx_mksearch_util_TCA {
	
	/**
	 * Convert HTML to plain text
	 * 
	 * Removes HTML tags and HTML comments and converts HTML entities  
	 * to their applicable characters.
	 * 
	 * @param string	$t
	 * @return string	Converted string (utf8-encoded)
	 */
	public static function html2plain($t) {
		return html_entity_decode(
					preg_replace(
									array('/(\s+|(<.*?>)+)/', '/<!--.*?-->/'), 
									array(' ', ''),
									$t
								),
								ENT_QUOTES,
								'UTF-8'
					);
	}

	/**
	 * Get extension keys of all registered indexers
	 * 
	 * @param array &$params
	 * @return array
	 */
	public static function getIndexerExtKeys(array &$params) {
		$config = tx_mksearch_util_Config::getIndexerConfigs();
		// wir sortieren vorher, damit bestehende items nicht mit sortiert werden!
		$keys = array_keys($config); sort($keys);
		foreach ($keys as $k) {
			$params['items'][] = array($k,$k);
		}
	}
	
	/**
	 * Get content types keys of the given indexer extension
	 * 
	 * @param array &$params
	 * @return array
	 */
	public static function getIndexerContentTypes(array &$params) {
		if (!$params['row']['extkey']) return;

		$config = tx_mksearch_util_Config::getIndexerConfigs($params['row']['extkey']);
		// wir sortieren vorher, damit bestehende items nicht mit sortiert werden!
		sort($config);
		foreach ($config as $k) {
			$params['items'][] = array($k,$k);
		}
	}
	
	/**
	 * Insert default TS configuration of the given indexer
	 *
	 * @param array $params
	 */
	public static function insertIndexerDefaultTSConfig(array &$params) {
		if (!(isset($params['params']['insertBetween']) && is_array($params['params']['insertBetween']) && 
					!empty($params['row']['extkey']) && !empty($params['row']['contenttype'])))
			return;
		
		try {
			$ts = tx_mksearch_util_Config::getIndexerDefaultTSConfig($params['row']['extkey'], $params['row']['contenttype']);
		}
		catch (Exception $e) {
			// "Service not found" exception is thrown for invalid $extkey/$contenttype combinations
			// which may temporarily occur on changing the $extkey!
			return '';
		}
		if (!$ts) return '';

		$lpos = strpos($params['item'], $params['params']['insertBetween'][0]);
		if ($lpos === false) return;

		$lpos += strlen($params['params']['insertBetween'][0]);
		$rpos = strrpos($params['item'], $params['params']['insertBetween'][1]);
		
		if ($rpos === false or $lpos > $rpos) return;

		$between = substr($params['item'], $lpos, $rpos-$lpos);
		if (!isset($params['params']['onMatchOnly']) ||preg_match($params['params']['onMatchOnly'], $between)) {
			$params['item'] = substr($params['item'], 0, $lpos) . $ts . substr($params['item'], $rpos, strlen($params['item'])-$rpos);
		}
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_TCA.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_TCA.php']);
}