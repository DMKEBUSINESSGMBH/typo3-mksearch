<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Michael Wagner <dev@dmk-ebusiness.de>
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


/**
 * Diese Klasse ist für die Darstellung von Indexer tabellen im Backend verantwortlich
 */
class tx_mksearch_mod1_decorator_IndexerConfig {
	function __construct($mod) {
		$this->mod = $mod;
	}

	/**
	 * Returns the module
	 * @return tx_rnbase_mod_IModule
	 */
	private function getModule() {
		return $this->mod;
	}
	/**
	 *
	 * @param 	string 								$value
	 * @param 	string 								$colName
	 * @param 	array 								$record
	 * @param 	tx_mksearch_model_internal_Config 	$item
	 */
	public function format($value, $colName, $record, $item) {

		switch ($colName) {
			case 'title':
				$ret  = '';
				$ret .= $value;
				if(!empty($record->record['description']))
					$ret .= '<br /><pre>'.$record->record['description'].'</pre>';
				break;
			case 'contenttype':
				$ret = $item->getExtkey().'.'.$item->getContenttype();
				break;
			case 'composites':
				$composites = tx_mksearch_util_ServiceRegistry::getIntCompositeService()->getByConfiguration($item);
				/* @var $compositeDecorator tx_mksearch_mod1_decorator_Composite */
				$compositeDecorator = tx_rnbase::makeInstance('tx_mksearch_mod1_decorator_Composite', $this->getModule());
				$ret = $compositeDecorator->getCompositeInfos($composites, array('includeIndex'=>1));
				break;
			case 'actions':
				$formtool = $this->getModule()->getFormTool();
				$ret  = '';
				// bearbeiten link
				$ret .= $formtool->createEditLink($item->getTableName(), $item->getUid(), '');
				// hide undhide link
				$ret .= $formtool->createHideLink($item->getTableName(), $item->getUid(), $item->record['hidden']);
				// remove link
				$ret .= $formtool->createDeleteLink($item->getTableName(), $item->getUid(), '', array('confirm' => $GLOBALS['LANG']->getLL('confirmation_deletion')));
				break;
			default:
				$ret = $value;
		}

		return $ret;
	}

	/**
	 *
	 * @param 	array 		$items
	 * @param 	array 		$options
	 * @return 	string
	 */
	public function getConfigInfos($items, $options=array()){
		foreach($items as $item) {
			$ret[] = $this->getConfigInfo($item, $options);
		}
		$ret = empty($ret) ? '###LABEL_NO_INDEXERCONFIGURATIONS###' : implode('</li><li class="hr"></li><li>',$ret);
		return '<ul><li>'.$ret.'</li></ul>';
	}
	/**
	 *
	 * @param 	tx_mksearch_model_internal_Composite 	$item
	 * @param 	array 									$options
	 * @return 	string
	 */
	public function getConfigInfo(tx_mksearch_model_internal_Config $item, $options=array()){
		$formtool = $this->getModule()->getFormTool();

		$out  = '';
		$out .= $formtool->createEditLink($item->getTableName(), $item->getUid(), '');
		$out .= $item->getTitle();
// 		$out .= '<br />'; // @TODO: in indices und configs wahlweise mit ausgeben
		return '<div>'.$out.'</div>';
	}

}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/decorator/class.tx_mksearch_mod1_decorator_IndexerConfig.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/decorator/class.tx_mksearch_mod1_decorator_IndexerConfig.php']);
}
