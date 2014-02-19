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
tx_rnbase::load('tx_mksearch_tests_Util');
tx_rnbase::load('tx_mksearch_service_indexer_core_Config');

/**
 * Wir müssen in diesem Fall mit der DB testen da wir definitiv
 * mindestens bis hasDocToBeDeleted() laufen. Dort wird die rootline geprüft
 * und daher benötigen wir für alle Tests, die Funktionalitäten
 * nach diesem Punkt testen eine leere DB um alles in einem erwarteten Zustand
 * zu haben. Sonst werden die tatsächlich vorhandenen pages geprüft und
 * diese können auf jedem system unterschiedlich sein.
 * @author Hannes Bochmann
 *
 *
 */
class tx_mksearch_tests_indexer_Base_DB_testcase extends tx_phpunit_database_testcase {
	
	protected $workspaceIdAtStart;
	protected $db;
	
	/**
	 * Klassenkonstruktor
	 *
	 * @param string $name
	 */
	public function __construct ($name=null) {
		global $TYPO3_DB, $BE_USER;
	
		parent::__construct ($name);
		$TYPO3_DB->debugOutput = TRUE;
	
		$this->workspaceIdAtStart = $BE_USER->workspace;
		$BE_USER->setWorkspace(0);
	}
	
	/**
	 * setUp() = init DB etc.
	 */
	public function setUp() {
		//WORKAROUND: phpunit seems to backup static attributes (in phpunit.xml)
		//from version 3.6.10 not before. I'm not completely
		//sure about that but from version 3.6.10 clearPageInstance is no
		//more neccessary to have the complete test suite succeed.
		//But this version is buggy. (http://forge.typo3.org/issues/36232)
		//as soon as this bug is fixed, we can use the new phpunit version
		//and dont need this anymore
		tx_mksearch_service_indexer_core_Config::clearPageInstance();
		
		//das devlog stört nur bei der Testausführung im BE und ist da auch
		//vollkommen unnötig
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['devlog']['nolog'] = true;
	
		$this->createDatabase();
		// assuming that test-database can be created otherwise PHPUnit will skip the test
		$this->db = $this->useTestDatabase();
		$this->importStdDB();
		
		$aExtensions = array('cms');
		//templavoila und realurl brauchen wir da es im BE sonst Warnungen hagelt
		//und man die Testergebnisse nicht sieht
		if(t3lib_extMgm::isLoaded('realurl')) $aExtensions[] = 'realurl';
		if(t3lib_extMgm::isLoaded('templavoila')) $aExtensions[] = 'templavoila';
		// fügt felder bei datenbank abfragen hinzu in $TYPO3_CONF_VARS['FE']['pageOverlayFields']
		// und $TYPO3_CONF_VARS['FE']['addRootLineFields']
		if(t3lib_extMgm::isLoaded('tq_seo')) $aExtensions[] = 'tq_seo';
		$this->importExtensions($aExtensions);
	}
	
	/**
	 * tearDown() = destroy DB etc.
	 */
	public function tearDown () {
		$this->cleanDatabase();
		$this->dropDatabase();
		$GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db);
	
		$GLOBALS['BE_USER']->setWorkspace($this->workspaceIdAtStart);
	}
	
	/**
	*
	*/
	public function testIsIndexableRecordWithInclude() {
		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
				'include.'=>array('pages.' => array(2)),
		);
	
		$aRawData = array('uid' => 1, 'pid' => 1);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNull($aIndexDoc,'Die Indizierung wurde doch indiziert! 1');
	
		$aRawData = array('uid' => 1, 'pid' => 2);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNotNull($aIndexDoc,'Die Indizierung wurde doch indiziert! 2');
	
		//noch mit kommaseparierter liste
		unset($options);
		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
				'include.'=>array('pages' => '2,3'),
		);
	
		$aRawData = array('uid' => 1, 'pid' => 1);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNull($aIndexDoc,'Die Indizierung wurde doch indiziert! 3');
	
		$aRawData = array('uid' => 1, 'pid' => 2);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNotNull($aIndexDoc,'Die Indizierung wurde doch indiziert! 4');
	
		$aRawData = array('uid' => 1, 'pid' => 3);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNotNull($aIndexDoc,'Die Indizierung wurde doch indiziert! 5');
	}
	
	/**
	 *
	 */
	public function testIsIndexableRecordWithIncludeAndDeleteIfNotIndexableOption() {
		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
				'include.'=>array('pages.' => array(2)),
				'deleteIfNotIndexable' => 1
		);
	
		$aRawData = array('uid' => 1, 'pid' => 1);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$oIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertTrue($oIndexDoc->getDeleted(),'Das Element wurde doch indiziert! pid:1');
		//es sollte auch keine exception durch getPrimaryKey geworfen werden damit das Löschen auch wirklich funktioniert
		$this->assertEquals('mksearch:dummy:1',$oIndexDoc->getPrimaryKey(true),'Das Element hat nicht den richtigen primary key! pid:1');
	
		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		$aRawData = array('uid' => 1, 'pid' => 2);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$oIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertFalse($oIndexDoc->getDeleted(),'Das Element wurde doch indiziert! pid:2');
	}
	
	/**
	 *
	 */
	public function testIsIndexableRecordWithExclude() {
		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
				'exclude.'=>array('pages.' => array(2))
		);
	
		$aRawData = array('uid' => 1, 'pid' => 1);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNotNull($aIndexDoc,'Das Element wurde nicht indiziert! 1');
	
		$aRawData = array('uid' => 1, 'pid' => 2);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNull($aIndexDoc,'Das Element wurde doch indiziert! 2');
	
		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
				'exclude.'=>array('pages' => '2,3')
		);
	
		$aRawData = array('uid' => 1, 'pid' => 1);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNotNull($aIndexDoc,'Das Element wurde nicht indiziert! 3');
	
		$aRawData = array('uid' => 1, 'pid' => 2);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNull($aIndexDoc,'Das Element wurde doch indiziert! 4');
	
		$aRawData = array('uid' => 1, 'pid' => 3);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNull($aIndexDoc,'Das Element wurde doch indiziert! 5');
	}

	/**
	 * 
	 */
	public function testIndexModelByMapping() {
		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options = array();
		
		$aRawData = array('uid' => 1, 'test_field_1' => '<a href="http://www.test.de">test value 1</a>', 'test_field_2' => 'test value 2');
		
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options)->getData();
		
		$this->assertEquals('test value 1',$aIndexDoc['test_field_1_s']->getValue(),'Es wurde nicht das erste Feld richtig gesetzt!');
		$this->assertEquals('test value 2',$aIndexDoc['test_field_2_s']->getValue(),'Es wurde nicht das zweite Feld richtig gesetzt!');
		//with keep html
		$this->assertEquals('<a href="http://www.test.de">test value 1</a>',$aIndexDoc['keepHtml_test_field_1_s']->getValue(),'Es wurde nicht das erste Feld richtig gesetzt!');
		$this->assertEquals('test value 2',$aIndexDoc['keepHtml_test_field_2_s']->getValue(),'Es wurde nicht das zweite Feld richtig gesetzt!');
	}
	
	/**
	 * 
	 */
	public function testIndexArrayOfModelsByMapping() {
		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options = array();
		
		$aRawData = array('uid' => 1, 'test_field_1' => 'test value 1', 'test_field_2' => 'test value 2', 'multiValue' => true);
		
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options)->getData();
		
		$this->assertEquals(array(0=>'test value 1',1=>'test value 1'),$aIndexDoc['multivalue_test_field_1_s']->getValue(),'Es wurde nicht das erste Feld richtig gesetzt!');
		$this->assertEquals(array(0=>'test value 2',1=>'test value 2'),$aIndexDoc['multivalue_test_field_2_s']->getValue(),'Es wurde nicht das zweite Feld richtig gesetzt!');
	}
	
	
	/**
	 * Check if setting the doc to deleted works
	 * @dataProvider someProvider
	 */
	public function testHasDocToBeDeletedConsideringOnlyTheModelItself() {
		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		list($extKey, $cType) = $indexer->getContentType();
		$options = array();

		//is hidden
		$oIndexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aRawData = array('uid' => 1, 'hidden' => 1);
		$oIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $oIndexDoc, $options);
		$this->assertTrue($oIndexDoc->getDeleted(),'Das Index Doc wurde nicht auf gelöscht gesetzt! 1');

		//is deleted
		$oIndexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aRawData = array('uid' => 1, 'hidden' => 0, 'deleted' => 1);
		$oIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $oIndexDoc, $options);
		$this->assertTrue($oIndexDoc->getDeleted(),'Das Index Doc wurde nicht auf gelöscht gesetzt! 2');
		
		//everything alright
		$oIndexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aRawData = array('uid' => 1, 'hidden' => 0, 'deleted' => 0);
		$oIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $oIndexDoc, $options);
		$this->assertFalse($oIndexDoc->getDeleted(),'Das Index Doc wurde doch auf gelöscht gesetzt! 3');
	}
	
	public function testHasDocToBeDeletedConsideringStatesOfTheParentPages() {
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/pages.xml'));
		
		$indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		$options = array();
	
		list($extKey, $cType) = $indexer->getContentType();
	
		//parent is hidden
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$record = array('uid'=> 10, 'pid' => 7);
		$indexer->prepareSearchData('doesnt_matter', $record, $indexDoc, $options);
		$this->assertEquals(true, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);
	
		//parent of parent is hidden
		$indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$record = array('uid'=> 127, 'pid' => 10);
		$indexer->prepareSearchData('doesnt_matter', $record, $indexDoc, $options);
		$this->assertEquals(true, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);
	
		//everything alright with parents
		$indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$record = array('uid'=> 125, 'pid' => 3);
		$indexer->prepareSearchData('doesnt_matter', $record, $indexDoc, $options);
		$this->assertEquals(false, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);
	}
	
	/**
	 *
	 */
	public function testStopIndexing() {
		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		list($extKey, $cType) = $indexer->getContentType();
		$options = array();
	
		$aRawData = array('uid' => 1, 'stopIndexing' => true);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNull($aIndexDoc,'Die Indizierung wurde nicht gestoppt!');
	
		$aRawData = array('uid' => 1, 'stopIndexing' => false);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNotNull($aIndexDoc,'Die Indizierung wurde gestoppt!');
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContent_testcase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContent_testcase.php']);
}

?>