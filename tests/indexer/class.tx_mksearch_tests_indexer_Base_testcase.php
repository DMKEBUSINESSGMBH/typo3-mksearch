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
 */
class tx_mksearch_tests_indexer_Base_testcase extends tx_phpunit_testcase {
	
	/**
	 * Check if the uid is set correct
	 */
	public function testGetPrimarKey() {
		$indexer = new tx_mksearch_tests_fixtures_indexer_Dummy();
		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$options = array();
		
		$aRawData = array('uid' => 1, 'test_field_1' => 'test value 1');
		
		$oIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		
		$aPrimaryKey = $oIndexDoc->getPrimaryKey();
		$this->assertEquals('mksearch',$aPrimaryKey['extKey']->getValue(),'Es wurde nicht der richtige extKey gesetzt!');
		$this->assertEquals('dummy',$aPrimaryKey['contentType']->getValue(),'Es wurde nicht der richtige contentType gesetzt!');
		$this->assertEquals(1,$aPrimaryKey['uid']->getValue(),'Es wurde nicht die richtige Uid gesetzt!');
	}
	
	/**
	 * Check if setting the doc to deleted works
	 * @dataProvider getHasDocToBeDeletedDataProvider
	 */
	public function testHasDocToBeDeleted($aRawData,$bResult,$sMessage) {
		$indexer = new tx_mksearch_tests_fixtures_indexer_Dummy();
		list($extKey, $cType) = $indexer->getContentType();
		$oIndexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$options = array();

		$oIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $oIndexDoc, $options);
		$this->assertEquals($bResult,$oIndexDoc->getDeleted(),$sMessage);
	}
	
	public function getHasDocToBeDeletedDataProvider(){
		return array(
			array(
				array('uid' => 1, 'hidden' => 1),
				true,
				'Das Index Doc wurde nicht auf gelöscht gesetzt!'
			),
			array(
				array('uid' => 1, 'hidden' => 0, 'deleted' => 1),
				true,
				'Das Index Doc wurde nicht auf gelöscht gesetzt!'
			),
			array(
				array('uid' => 1, 'hidden' => 0, 'deleted' => 0),
				false,
				'Das Index Doc wurde auf gelöscht gesetzt!'
			),
		
		);
	}
	
	/**
	 * 
	 */
	public function testIndexModelByMapping() {
		$indexer = new tx_mksearch_tests_fixtures_indexer_Dummy();
		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$options = array();
		
		$aRawData = array('uid' => 1, 'test_field_1' => '<a href="http://www.test.de">test value 1</a>', 'test_field_2' => 'test value 2');
		
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options)->getData();
		
		$this->assertEquals(' test value 1 ',$aIndexDoc['test_field_1_s']->getValue(),'Es wurde nicht das erste Feld richtig gesetzt!');
		$this->assertEquals('test value 2',$aIndexDoc['test_field_2_s']->getValue(),'Es wurde nicht das zweite Feld richtig gesetzt!');
		//with keep html
		$this->assertEquals('<a href="http://www.test.de">test value 1</a>',$aIndexDoc['keepHtml_test_field_1_s']->getValue(),'Es wurde nicht das erste Feld richtig gesetzt!');
		$this->assertEquals('test value 2',$aIndexDoc['keepHtml_test_field_2_s']->getValue(),'Es wurde nicht das zweite Feld richtig gesetzt!');
	}
	
	/**
	 * 
	 */
	public function testIndexArrayOfModelsByMapping() {
		$indexer = new tx_mksearch_tests_fixtures_indexer_Dummy();
		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$options = array();
		
		$aRawData = array('uid' => 1, 'test_field_1' => 'test value 1', 'test_field_2' => 'test value 2', 'multiValue' => true);
		
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options)->getData();
		
		$this->assertEquals(array(0=>'test value 1',1=>'test value 1'),$aIndexDoc['multivalue_test_field_1_s']->getValue(),'Es wurde nicht das erste Feld richtig gesetzt!');
		$this->assertEquals(array(0=>'test value 2',1=>'test value 2'),$aIndexDoc['multivalue_test_field_2_s']->getValue(),'Es wurde nicht das zweite Feld richtig gesetzt!');
	}
	
	/**
	 * 
	 */
	public function testStopIndexing() {
		$indexer = new tx_mksearch_tests_fixtures_indexer_Dummy();
		list($extKey, $cType) = $indexer->getContentType();
		$options = array();
		
		$aRawData = array('uid' => 1, 'stopIndexing' => true);
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNull($aIndexDoc,'Die Indizierung wurde nicht gestoppt!');
		
		$aRawData = array('uid' => 1, 'stopIndexing' => false);
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNotNull($aIndexDoc,'Die Indizierung wurde gestoppt!');
	}
	
	/**
	 * 
	 */
	public function testIsIndexableRecordWithInclude() {
		$indexer = new tx_mksearch_tests_fixtures_indexer_Dummy();
		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
			'include.'=>array('pages.' => array(2)),
		);
		
		$aRawData = array('uid' => 1, 'pid' => 1);
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNull($aIndexDoc,'Die Indizierung wurde doch indiziert! 1');
		
		$aRawData = array('uid' => 1, 'pid' => 2);
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNotNull($aIndexDoc,'Die Indizierung wurde doch indiziert! 2');
		
		//noch mit kommaseparierter liste
		unset($options);
		$indexer = new tx_mksearch_tests_fixtures_indexer_Dummy();
		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
			'include.'=>array('pages' => '2,3'),
		);
		
		$aRawData = array('uid' => 1, 'pid' => 1);
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNull($aIndexDoc,'Die Indizierung wurde doch indiziert! 3');
		
		$aRawData = array('uid' => 1, 'pid' => 2);
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNotNull($aIndexDoc,'Die Indizierung wurde doch indiziert! 4');
		
		$aRawData = array('uid' => 1, 'pid' => 3);
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNotNull($aIndexDoc,'Die Indizierung wurde doch indiziert! 5');
	}
	
	/**
	 * 
	 */
	public function testIsIndexableRecordWithIncludeAndDeleteIfNotIndexableOption() {
		$indexer = new tx_mksearch_tests_fixtures_indexer_Dummy();
		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
			'include.'=>array('pages.' => array(2)),
			'deleteIfNotIndexable' => 1
		);
		
		$aRawData = array('uid' => 1, 'pid' => 1);
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$oIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertTrue($oIndexDoc->getDeleted(),'Das Element wurde doch indiziert! pid:1');
		//es sollte auch keine exception durch getPrimaryKey geworfen werden damit das Löschen auch wirklich funktioniert
		$this->assertEquals('mksearch:dummy:1',$oIndexDoc->getPrimaryKey(true),'Das Element hat nicht den richtigen primary key! pid:1');
		
		$aRawData = array('uid' => 1, 'pid' => 2);
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$oIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertFalse($oIndexDoc->getDeleted(),'Das Element wurde doch indiziert! pid:2');
	}
	
	/**
	 * 
	 */
	public function testIsIndexableRecordWithExclude() {
		$indexer = new tx_mksearch_tests_fixtures_indexer_Dummy();
		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
			'exclude.'=>array('pages.' => array(2))
		);
		
		$aRawData = array('uid' => 1, 'pid' => 1);
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNotNull($aIndexDoc,'Das Element wurde nicht indiziert! 1');
		
		$aRawData = array('uid' => 1, 'pid' => 2);
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNull($aIndexDoc,'Das Element wurde doch indiziert! 2');
		
		$indexer = new tx_mksearch_tests_fixtures_indexer_Dummy();
		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
			'exclude.'=>array('pages' => '2,3')
		);
		
		$aRawData = array('uid' => 1, 'pid' => 1);
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNotNull($aIndexDoc,'Das Element wurde nicht indiziert! 3');
		
		$aRawData = array('uid' => 1, 'pid' => 2);
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNull($aIndexDoc,'Das Element wurde doch indiziert! 4');
		
		$aRawData = array('uid' => 1, 'pid' => 3);
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNull($aIndexDoc,'Das Element wurde doch indiziert! 5');
	}
	
	/**
	 * 
	 */
	public function testCheckOptionsInclude() {
		$indexer = new tx_mksearch_tests_fixtures_indexer_Dummy();
		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
			'include.'=>array('categories.' => array(3))
		);
		$options2 = array(
			'include.'=>array('categories' => 3)
		);
		
		$aRawData = array('uid' => 1, 'pid' => 1);
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNull($aIndexDoc,'Das Element wurde indiziert! Option 1');
		
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options2);
		$this->assertNull($aIndexDoc,'Das Element wurde indiziert! Option 2');
		
		$options = array(
			'include.'=>array('categories.' => array(2))
		);
		$options2 = array(
			'include.'=>array('categories' => 2)
		);
		$aRawData = array('uid' => 1, 'pid' => 2);
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNotNull($aIndexDoc,'Das Element wurde nicht indiziert! Option 1');
		
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options2);
		$this->assertNotNull($aIndexDoc,'Das Element wurde nicht indiziert! Option 2');
	}
	
/**
	 * 
	 */
	public function testCheckOptionsExclude() {
		$indexer = new tx_mksearch_tests_fixtures_indexer_Dummy();
		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
			'exclude.'=>array('categories.' => array(3))
		);
		$options2 = array(
			'exclude.'=>array('categories' => 3)
		);
		
		$aRawData = array('uid' => 1, 'pid' => 1);
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNotNull($aIndexDoc,'Das Element wurde nicht indiziert! Option 1');
		
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options2);
		$this->assertNotNull($aIndexDoc,'Das Element wurde nicht indiziert! Option 2');
		
		$options = array(
			'exclude.'=>array('categories.' => array(2))
		);
		$options2 = array(
			'exclude.'=>array('categories' => 2)
		);
		$aRawData = array('uid' => 1, 'pid' => 2);
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNull($aIndexDoc,'Das Element wurde doch indiziert! Option 1');
		
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options2);
		$this->assertNull($aIndexDoc,'Das Element wurde doch indiziert! Option 2');
	}
	
	public function testGetPidListPreparesTsfe() {
		$GLOBALS['TSFE'] = null;
		
		$indexer = new tx_mksearch_tests_fixtures_indexer_Dummy();
		$indexer->callGetPidList();
		
		$this->assertNotNull($GLOBALS['TSFE'],'TSFE wurde nicht geladen!');
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContent_testcase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContent_testcase.php']);
}

?>