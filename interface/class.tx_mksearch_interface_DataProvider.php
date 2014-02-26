<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 René Nitzsche <nitzsche@das-medienkombinat.de>
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

/**
 * Interface for DataProvider. A data provider is responsible to lookup data to be indexed.
 * 
 * @author	René Nitzsche <nitzsche@das-medienkombinat.de>
 * @package	TYPO3
 * @subpackage	tx_mksearch
 */
interface tx_mksearch_interface_DataProvider {
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
	public function prepareData(array $options=array(), array $data=array());
	
	/**
	 * Return next record which is to be indexed
	 * 
	 * @return array
	 */
	public function getNextItem();
	
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
	 * @return array	Tablename <-> uids matrix of records to be indexed (array('tab1' => array(2,5,6), 'tab2' => array(4,5,8)) 
	 */
	public function cleanupData();

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/interface/class.tx_mksearch_interface_Indexer.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/interface/class.tx_mksearch_interface_Indexer.php']);
}