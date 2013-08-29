<?php
/**
 *
 *  @package tx_mksearch
 *  @subpackage tx_mksearch_mod1
 *
 *  Copyright notice
 *
 *  (c) 2011-2013 das MedienKombinat GmbH <kontakt@das-medienkombinat.de>
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

require_once t3lib_extMgm::extPath('rn_base', 'class.tx_rnbase.php');
tx_rnbase::load('tx_rnbase_mod_ExtendedModFunc');
tx_rnbase::load('tx_mksearch_mod1_util_Template');

/**
 * Mksearch backend module
 *
 * Hat nchts mehr speziell mit Solr zu tun,
 * wurde von der Namensgebung allerdings so beibehalten.
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_mod1
 * @author René Nitzsche <nitzsche@das-medienkombinat.de>
 */
class tx_mksearch_mod1_SolrAdmin extends tx_rnbase_mod_ExtendedModFunc {

	/**
	 * Return function id (used in page typoscript etc.)
	 *
	 * @return 	string
	 */
	protected function getFuncId() {
		return 'admin';
	}

	public function getPid(){
		return $this->getModule()->getPid();
	}

	public function main() {
		$out = parent::main();
		$out = tx_mksearch_mod1_util_Template::parseBasics($out, $this);
		return $out;
	}

	/**
	 * Liefert die Einträge für das Tab-Menü.
	 * @return 	array
	 */
	protected function getSubMenuItems() {
		return array(
			tx_rnbase::makeInstance('tx_mksearch_mod1_handler_admin_Solr'),
		);
	}

	/**
	 *
	 */
	protected function makeSubSelectors(&$selStr) {
		return false;
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/class.tx_mksearch_mod1_SolrAdmin.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/class.tx_mksearch_mod1_SolrAdmin.php']);
}