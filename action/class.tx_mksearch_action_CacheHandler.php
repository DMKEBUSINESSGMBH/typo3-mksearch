<?php
/**
 * @package tx_mksearch
 * @subpackage tx_mksearch_action
 *
 * Copyright notice
 *
 * (c) 2013 das MedienKombinat GmbH <kontakt@das-medienkombinat.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

require_once t3lib_extMgm::extPath('rn_base', 'class.tx_rnbase.php');
tx_rnbase::load('tx_rnbase_action_CacheHandlerDefault');
tx_rnbase::load('tx_rnbase_util_Strings');

/**
 * Detailseite eines beliebigen Datensatzes aus Momentan Lucene oder Solr.
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_action
 * @author Michael Wagner <michael.wagner@das-medienkombinat.de>
 */
class tx_mksearch_action_CacheHandler
	extends tx_rnbase_action_CacheHandlerDefault {
	/**
	 * Generate a key used to store data to cache.
	 * @return string
	 */
	protected function generateKey(/*$plugin*/) {
		$key = parent::generateKey(NULL);
		// Parameter cHash anhängen
		$key .= '_'.md5(serialize($this->getAllowedParameters()));
		return $key;
	}

	/**
	 * Liefert alle erlaubten parameter,
	 * welche zum erzeugen des CacheKeys verwendet werden.
	 *
	 * @return array
	 */
	private function getAllowedParameters() {
		$parameters = $this->getConfigurations()->getParameters();
		$params = array();
		$allowed = tx_rnbase_util_Strings::trimExplode(
			',',
			$this->getConfigValue('params.allowed', ''),
			1
		);
		foreach ($allowed as $p) {
			$params[$p] = $parameters->get($p);
		}
		return $params;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/action/class.tx_mksearch_action_CacheHandler.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/action/class.tx_mksearch_action_CacheHandler.php']);
}
