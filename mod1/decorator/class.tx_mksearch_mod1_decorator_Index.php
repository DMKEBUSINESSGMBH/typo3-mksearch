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


tx_rnbase::load('tx_mksearch_mod1_util_IndexStatusHandler');

/**
 * Diese Klasse ist für die Darstellung von Indexer tabellen im Backend verantwortlich
 */
class tx_mksearch_mod1_decorator_Index {
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
	 * @param string $value
	 * @param string $colName
	 * @param array $record
	 * @param tx_mksearch_model_internal_Index $item
	 */
	public function format($value, $colName, $record, $item) {
		$ret = '';
		switch ($colName) {
			case 'core':
				$ret  = '';
				$ret .= tx_mksearch_mod1_util_IndexStatusHandler::getInstance()->handleRequest4Index($item);
				if(!empty($record->record['description']))
					$ret .= '<br /><pre>'.$record->record['description'].'</pre>';
				break;
			case 'engine':
				switch($value) {
					case 'zend_lucene':
						$ret = $GLOBALS['LANG']->sL('LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices_engine_zendlucene');
						break;
					case 'solr':
						$ret = $GLOBALS['LANG']->sL('LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices_engine_solr');
						break;
					case 'elasticsearch':
						$ret = $GLOBALS['LANG']->sL('LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices_engine_elasticsearch');
						break;
					default:
						$ret = $value;
				}
				break;
			case 'composites':
				$composites = tx_mksearch_util_ServiceRegistry::getIntCompositeService()->getByIndex($item);
				/* @var $compositeDecorator tx_mksearch_mod1_decorator_Composite */
				$compositeDecorator = tx_rnbase::makeInstance('tx_mksearch_mod1_decorator_Composite', $this->getModule());
				$ret = $compositeDecorator->getCompositeInfos($composites, array('includeConfig' => 1));
				break;
			case 'actions':
				$formtool = $this->getModule()->getFormTool();
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
	public function getIndexInfos($items, $options=array()){
		foreach($items as $item) {
			$ret[] = $this->getIndexInfo($item, $options);
		}
		$ret = empty($ret) ? '###LABEL_NO_INDIZES###' : implode('</li><li class="hr"></li><li>',$ret);
		return '<ul><li>'.$ret.'</li></ul>';
	}
	/**
	 *
	 * @param 	tx_mksearch_model_internal_Composite 	$item
	 * @param 	array 									$options
	 * @return 	string
	 */
	public function getIndexInfo(tx_mksearch_model_internal_Index $item, $options=array()){
		$formtool = $this->getModule()->getFormTool();

		$out  = '';
		$out .= $formtool->createEditLink($item->getTableName(), $item->getUid(), '');
		$out .= $item->getTitle();
// 		$out .= '<br />'; // @TODO: verbundene tabellen anhand von options ausgeben
		return '<div>'.$out.'</div>';
	}

}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/decorator/class.tx_mksearch_mod1_decorator_Index.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/decorator/class.tx_mksearch_mod1_decorator_Index.php']);
}
