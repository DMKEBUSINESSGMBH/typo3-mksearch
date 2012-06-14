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


require_once(PATH_t3lib.'class.t3lib_svbase.php');
require_once(PATH_t3lib.'class.t3lib_page.php');
require_once t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php';
tx_rnbase::load('tx_mksearch_interface_DataProvider');

/**
 * DataProvider class to lookup data from database
 *
 * @author	Lars Heber <lars.heber@das-medienkombinat.de>
 * @package	TYPO3
 * @subpackage	tx_mksearch
 */
abstract class tx_mksearch_service_dp_RnBaseDB implements tx_mksearch_interface_DataProvider {

	/**
	 * Options used for indexing
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * Container for sql resource
	 *
	 * @var sql resource
	 */
	protected $sqlRes;

	/**
	 * Container for storing $GLOBALS['TYPO3_CONF_VARS']['FE']['pageOverlayFields']
	 *
	 * @var string
	 */
	private $pof;

	/**
	 * List of records to be deleted
	 *
	 * @var array
	 */
	private $deleteList = array();

	/**
	 * Prepare indexer
	 *
	 * This method prepares things for indexing,
	 * i. e. evaluate options, prepare db query etc.
	 * It must be called between instatiating the class
	 * and calling nextItem() for the first time.
	 *
	 * @param array $options	Indexer options
	 * @param array $data		Tablename <-> uids matrix of records to be indexed (array('tab1' => array(2,5,6), 'tab2' => array(4,5,8))
	 * @return void
	 */
	public function prepareData(array $options=array(), array $data=array()) {
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
	 * @return array
	 */
	public function getNextItem() {
		// No valid DB resource?
		if (!is_resource($this->sqlRes)) return null;

		$record = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($this->sqlRes);
		return $record;
	}

	/**
	 * Quasi-destructor
	 *
	 * Clean up things, e.g. free db resources,
	 * and return a list of uids of records which are
	 * to be deleted from the index.
	 *
	 * Note: Use of an ordinary __destruct() function is not productive here
	 * as a guaranteed invoking of the destructor is not trivial to implement.
	 * Additionally, as an indexer is mostly used as a service which may be
	 * re-used over and over again
	 * (@see t3lib_div::makeInstanceService() -> persistence of service),
	 * take care to restore the instance to a clean, initial state!
	 *
	 * @return array	Matrix of records to be deleted
	 */
	public function cleanupData() {
		// Free sql query space
		if (is_resource($this->sqlRes))
			$GLOBALS['TYPO3_DB']->sql_free_result($this->sqlRes);

		// Reset things
		$this->options = null;
		$deleteList = $this->deleteList;
		$this->deleteList = array();
		return $deleteList;
	}

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

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/service/dp/class.tx_mksearch_service_dp_RnBaseDB.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/service/dp/class.tx_mksearch_service_dp_RnBaseDB.php']);
}
