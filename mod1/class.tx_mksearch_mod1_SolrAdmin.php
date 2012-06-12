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
tx_rnbase::load('tx_rnbase_util_SearchBase');

/**
 * Mksearch backend module
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_mod1
 * @author René Nitzsche <nitzsche@das-medienkombinat.de>
 */
class tx_mksearch_mod1_SolrAdmin extends tx_rnbase_mod_BaseModFunc {

	/**
	 * Return function id (used in page typoscript etc.)
	 *
	 * @return 	string
	 */
	protected function getFuncId() {
		return 'solradmin';
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

		$this->handleRequest();

		$cores = $this->findSolrCores();
		if(empty($cores))
			return '###LABEL_SOLR_NOCORES_FOUND### Hab nix';
		
		$templateMod = tx_rnbase_util_Templates::getSubpart($template,'###ADMINPANEL###');
		$out = $this->showAdminPanel($templateMod, $cores, $configurations, $formTool, $markerArray);
		return $out;
	}
	protected function handleRequest() {
		$submitted = t3lib_div::_GP('doDelete') || t3lib_div::_GP('doQuery');
		if(!$submitted) return '';

		$this->data = t3lib_div::_GP('data');
		$deleteQuery = trim($this->data['deletequery']);
		$SET = t3lib_div::_GP('SET');
		$core = intval($SET['solr_core']);
		if(!$core) {
			$this->getModule()->addMessage('###LABEL_SOLR_NOCORE_FOUND###','###LABEL_COMMON_WARNING###');
			return;
		}

		try {
			$core = tx_rnbase::makeInstance('tx_mksearch_model_internal_Index', $core);
			$searchEngine = tx_mksearch_util_ServiceRegistry::getSearchEngine($core);
			$result = $searchEngine->indexDeleteByQuery($deleteQuery);
			$searchEngine->commitIndex();
			$this->getModule()->addMessage('###LABEL_SOLR_DELETE_SUCCESSFUL###','###LABEL_COMMON_INFO###');
		}
		catch (Exception $e) {
			tx_rnbase::load('tx_rnbase_util_Logger');
			$this->getModule()->addMessage(htmlspecialchars($e->getMessage()),'###LABEL_COMMON_ERROR###');
			tx_rnbase_util_Logger::warn('[SolrAdmin] Exception for delete query.', 'mksearch', array('Exception'=>$e->getMessage()));
		}
	}

	/**
	 * Returns search form
	 * @param 	string 						$template
	 * @param array $cores
	 * @param 	tx_rnbase_configurations 	$configurations
	 * @param 	tx_rnbase_util_FormTool 	$formTool
	 * @return 	string
	 */
	protected function showAdminPanel($template, $cores, $configurations, $formTool, &$markerArray) {
		tx_rnbase::load('tx_mksearch_mod1_util_Template');

		$markerArray['###SEL_CORES###'] = $this->getCoreSelector($cores, $formTool);
		$markerArray['###INPUT_DELETEQUERY###'] = $formTool->createTextArea('data[deletequery]', $this->data['deletequery']);
		$markerArray['###BTN_SEND###'] = $formTool->createSubmit('doDelete', '###LABEL_SOLR_SUBMIT_DELETE###', 'Do you really want to submit this DELETE query?');

		$out = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray);
		return $out;
	}
	protected function findSolrCores() {
		// Solr-Core auf der aktuellen Seite suchen
		$fields['INDX.PID'][OP_EQ_INT] = $this->getModule()->getPid();
		$fields['INDX.ENGINE'][OP_EQ] = 'solr';
		$cores = tx_mksearch_util_ServiceRegistry::getIntIndexService()->search($fields, array());
		return $cores;
	}
	/**
	 * 
	 * @param tx_rnbase_util_FormTool $formTool
	 */
	protected function getCoreSelector($cores, $formTool) {
		$entries = array();
		foreach ($cores As $core) {
			$entries[$core->getUid()] = $core->getTitle() .' ('. $core->getName().')';
		}
		$menu = $formTool->showMenu($this->getModule()->getPid(), 'solr_core', $this->getModule()->getName(), $entries);
		return $menu['menu'];
	}

}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/class.tx_mksearch_mod1_SolrAdmin.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/class.tx_mksearch_mod1_SolrAdmin.php']);
}