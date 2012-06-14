<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Lars Heber <lars.heber@das-medienkombinat.de>
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

//require_once(PATH_t3lib.'class.t3lib_page.php');
require_once(PATH_t3lib.'class.t3lib_svbase.php');
require_once t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php';
tx_rnbase::load('tx_mksearch_interface_Indexer');

/**
 * Service "Indexer" base class for content types which can be found via rn_base search
 *
 * @author	Lars Heber <lars.heber@das-medienkombinat.de>
 * @package	TYPO3
 * @subpackage	tx_mksearch
 */
abstract class tx_mksearch_service_indexer_BaseRnBaseSearch
	extends t3lib_svbase
	implements tx_mksearch_interface_Indexer {
	
	/**
	 * Options used for indexing
	 *
	 * @var array
	 */
	protected $options = array();
	
	/**
	 * Container for sql resource
	 *
	 * @var sql resource
	 */
	protected $sqlRes;
	
	/**
	 * Prepare indexer
	 * 
	 * This method prepares things for indexing,
	 * i. e. evaluate options, prepare db query etc.
	 * It must be called between instatiating the class
	 * and calling nextItem() for the first time.
	 * 
	 * 
	 * Um bei mehrfachen Daten-Speicherungs- und damit Index-Update-Anforderungen
	 * innerhalb eines Requests mehrfaches Aktualisieren eines Records zu vermeiden,
	 * muss eine Queue eingerichtet werden!
	 * Use case: Person hat eine Kontakt-Adresse (Kontakt in eigener Tabelle).
	 * Werden Personen- und Kontakt-Daten innerhalb eines einzigen HTTP-Requests
	 * gespeichert, würde derselbe Datensatz zwei mal gespeichert!
	 * => Aktivierung des Index-Updates erst beim "Herunterfahren" des ganzen Typo3-
	 * Prozesses / Beendigung des HTTP-Requests (evtl. im Destructor?) 
	 * 
	 * @param array $options	Indexer options
	 * @param array $data		Tablename <-> uids matrix of records to be indexed (array('tab1' => array(2,5,6), 'tab2' => array(4,5,8))
	 * @return void
	 */
	public function prepare(array $options=array(), array $data=array()) {
		$this->options = $options;
		
		$searcher = tx_rnbase_util_SearchBase::getInstance($this->getSearchClass($this->options));
		
		
		list($f, $o) = $this->getFieldsOptions($this->options, $data);
		
		// If no "enablefields*" option is explicitely set, implicitely force FE mode
		$isSomeEnableFieldsOptionSet = false; 
		foreach ($o as $key=>$value)
			if (substr($key, 0, 12) == 'enablefields') {
				$isSomeEnableFieldsOptionSet = true;
				break;
			}
		if (!$isSomeEnableFieldsOptionSet) $o['enablefieldsfe'] = 1;
		
		// We need the plain sql here!
		$o['sqlonly'] = 1;

		$sql = $searcher->search($f, $o);

		$this->sqlRes = $GLOBALS['TYPO3_DB']->sql_query($sql);
	}
	
	/**
	 * Return next item which is to be indexed
	 * 
	 * @param tx_mksearch_interface_IndexerDocument		$indexDoc	Indexer document to be "filled", instantiated based on self::getContentType()
	 * @return null|tx_mksearch_interface_IndexerDocument
	 */
	public function nextItem(tx_mksearch_interface_IndexerDocument $indexDoc) {
		// Iterate until valid data is found or all records are consumed ;-) 
		do {
			$record = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($this->sqlRes);
			
		} while ($record and !($indexDocResult = $this->prepareData($record, $this->options, $indexDoc)));
		if (isset($indexDocResult)) return $indexDocResult;
		// else
		return null;
	}
		
	/**
	 * Quasi-destructor
	 * 
	 * Nothing to do here... @todo: REALLY???
	 * @return array	Matrix of records to be deleted 
	 */
	public function cleanup() {}
	
		
	/**
	 * Prepare / transform raw data from database for indexing
	 * 
	 * @param array									$rawData from SQL query, NOT the model!
	 * @param array 								$options from service configuration
	 * @param tx_mksearch_interface_IndexerDocument	$indexDoc Model to be filled
	 * @return tx_mksearch_interface_IndexerDocument or null, if record is not to be indexed
	 */
	abstract protected function prepareData(
		array $rawData,
		array $options,
		tx_mksearch_interface_IndexerDocument $indexDoc
		);
	
	/**
	 * Get name of rn_base based search class 
	 *
	 * @param array $options
	 * @return string
	 */
	abstract protected function getSearchClass(array $options);
	
	/**
	 * Get parameters $fields and $options for rn_base based search 
	 *
	 * @param array $options	from service configuration
	 * @param array $data		Tablename <-> uids matrix of records to be indexed (array('tab1' => array(2,5,6), 'tab2' => array(4,5,8))
	 * @return array: 
	 * 				* array	$fields  
	 * 				* array	$options
	 * 
	 */
	abstract protected function getFieldsOptions(array $options, array $data=array());
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/service/indexer/class.tx_mksearch_service_indexer_BaseDataBase.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/service/indexer/class.tx_mksearch_service_indexer_BaseDataBase.php']);
}
