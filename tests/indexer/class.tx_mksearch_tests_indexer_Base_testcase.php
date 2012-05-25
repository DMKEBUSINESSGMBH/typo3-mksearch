<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 das Medienkombinat GmbH
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
tx_rnbase::load('tx_mksearch_tests_fixtures_indexer_Dummy');

/**
 * Wir müssen in diesem Fall mit der DB testen da wir die pages
 * Tabelle benötigen
 * @author Hannes Bochmann
 * 
 */
class tx_mksearch_tests_indexer_Base_testcase extends tx_phpunit_testcase {
	
	/**
	 * Check if the uid is set correct
	 */
	public function testGetPrimarKey() {
		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options = array();
		
		$aRawData = array('uid' => 1, 'test_field_1' => 'test value 1');
		
		$oIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		
		$aPrimaryKey = $oIndexDoc->getPrimaryKey();
		$this->assertEquals('mksearch',$aPrimaryKey['extKey']->getValue(),'Es wurde nicht der richtige extKey gesetzt!');
		$this->assertEquals('dummy',$aPrimaryKey['contentType']->getValue(),'Es wurde nicht der richtige contentType gesetzt!');
		$this->assertEquals(1,$aPrimaryKey['uid']->getValue(),'Es wurde nicht die richtige Uid gesetzt!');
	}
	
	public function testGetPidListPreparesTsfe() {
		$GLOBALS['TSFE'] = null;
		
		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		$indexer->callGetPidList();
		
		$this->assertNotNull($GLOBALS['TSFE'],'TSFE wurde nicht geladen!');
	}
	
	
		
	/**
	 *
	 */
	public function testCheckOptionsInclude() {
		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
			'include.'=>array('categories.' => array(3))
		);
		$options2 = array(
			'include.'=>array('categories' => 3)
		);
		
		$aRawData = array('uid' => 1, 'pid' => 1);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNull($aIndexDoc,'Das Element wurde indiziert! Option 1');
		
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options2);
		$this->assertNull($aIndexDoc,'Das Element wurde indiziert! Option 2');
		
		$options = array(
			'include.'=>array('categories.' => array(2))
		);
		$options2 = array(
			'include.'=>array('categories' => 2)
		);
		$aRawData = array('uid' => 1, 'pid' => 2);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNotNull($aIndexDoc,'Das Element wurde nicht indiziert! Option 1');
		
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options2);
		$this->assertNotNull($aIndexDoc,'Das Element wurde nicht indiziert! Option 2');
	}
	
	/**
	 *
	 */
	public function testCheckOptionsExclude() {
		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
			'exclude.'=>array('categories.' => array(3))
		);
		$options2 = array(
			'exclude.'=>array('categories' => 3)
		);
		
		$aRawData = array('uid' => 1, 'pid' => 1);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNotNull($aIndexDoc,'Das Element wurde nicht indiziert! Element 1 Option 1');
		
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options2);
		$this->assertNotNull($aIndexDoc,'Das Element wurde nicht indiziert! Element 1 Option 2');
		
		$options = array(
			'exclude.'=>array('categories.' => array(2))
		);
		$options2 = array(
			'exclude.'=>array('categories' => 2)
		);
		$aRawData = array('uid' => 1, 'pid' => 2);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNull($aIndexDoc,'Das Element wurde doch indiziert! Element 2 Option 1');
		
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options2);
		$this->assertNull($aIndexDoc,'Das Element wurde doch indiziert! Element 2 Option 2');
	}
	/**
	*
	*/
	public function testCheckOptionsIncludeReturnsCorrectDefaultValueWithEmptyCategory() {
		$indexer = $this->getMock('tx_mksearch_tests_fixtures_indexer_Dummy',array('getTestCategories'));
		
		$indexer->expects($this->once())
			->method('getTestCategories')
			->will($this->returnValue(array()));
		
		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
				'include.'=>array('categories.' => array(3))
		);
	
		$aRawData = array('uid' => 1, 'pid' => 1);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNull($aIndexDoc,'Das Element wurde indiziert! Option 1');
	}
	
	/**
	 *
	 */
	public function testCheckOptionsExcludeReturnsCorrectDefaultValueWithEmptyCategory() {
		$indexer = $this->getMock('tx_mksearch_tests_fixtures_indexer_Dummy',array('getTestCategories'));
		
		$indexer->expects($this->once())
			->method('getTestCategories')
			->will($this->returnValue(array()));
		
		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
				'exclude.'=>array('categories.' => array(3))
		);
	
		$aRawData = array('uid' => 1, 'pid' => 1);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNotNull($aIndexDoc,'Das Element wurde nicht indiziert! Option 1');
	}
	
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContent_testcase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContent_testcase.php']);
}

?>