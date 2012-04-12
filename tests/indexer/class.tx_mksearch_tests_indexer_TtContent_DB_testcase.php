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
tx_rnbase::load('tx_mksearch_indexer_TtContent');
tx_rnbase::load('tx_mksearch_model_IndexerDocumentBase');
tx_rnbase::load('tx_mksearch_tests_Util');


require_once(t3lib_extMgm::extPath('mksearch') . 'lib/Apache/Solr/Document.php');

/**
 * Wir müssen in diesem Fall mit der DB testen da wir die pages
 * Tabelle benötigen
 * @author Hannes Bochmann
 */
class tx_mksearch_tests_indexer_TtContent_DB_testcase extends tx_phpunit_database_testcase {
	protected $workspaceIdAtStart;
	protected $db;
	protected $aTvConfig;

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
		$this->createDatabase();
		// assuming that test-database can be created otherwise PHPUnit will skip the test
		$this->db = $this->useTestDatabase();

		//das devlog stört nur bei der Testausführung im BE und ist da auch
		//vollkommen unnötig
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['devlog']['nolog'] = true;

		$this->importStdDB();
		$aExtensions = array('cms','mksearch');
		if(t3lib_extMgm::isLoaded('templavoila')) $aExtensions[] = 'templavoila';
		//templavoila und realurl brauchen wir da es im BE sonst Warnungen hagelt
		//und man die Testergebnisse nicht sieht
		if(t3lib_extMgm::isLoaded('realurl')) $aExtensions[] = 'realurl';
		$this->importExtensions($aExtensions);

		//uninstall templavoila so the tests run without it as they used to
		global $TYPO3_LOADED_EXT;
		$this->aTvConfig = $TYPO3_LOADED_EXT['templavoila'];
		$TYPO3_LOADED_EXT['templavoila'] = null;

		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/pages.xml'));
	}

	/**
	 * tearDown() = destroy DB etc.
	 */
	public function tearDown () {
		$this->cleanDatabase();
		$this->dropDatabase();
		$GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db);

		$GLOBALS['BE_USER']->setWorkspace($this->workspaceIdAtStart);

		//re-install templavoila so the tests run without it as they used to
		global $TYPO3_LOADED_EXT;
		$TYPO3_LOADED_EXT['templavoila'] = $this->aTvConfig;
	}

	public function testPrepareSearchDataAndCheckDeleted() {
		$indexer = new tx_mksearch_indexer_TtContent();
		$options = $this->getDefaultConfig();

		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);

		//is deleted
		$record = array('uid'=> 123, 'pid' => 1, 'deleted' => 1, 'CType'=>'list');
		$indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
		$this->assertEquals(true, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);

		//is hidden
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$record = array('uid'=> 124, 'pid' => 1, 'deleted' => 0, 'hidden' => 1, 'CType'=>'list');
		$indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
		$this->assertEquals(true, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);

		//everything alright
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$record = array('uid'=> 125, 'pid' => 1, 'deleted' => 0, 'hidden' => 0, 'header' => 'test', 'CType'=>'list');
		$indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
		$this->assertEquals(false, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);

		//everything alright
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$record = array('uid'=> 126, 'pid' => 1, 'deleted' => 0, 'hidden' => 0, 'sectionIndex' => 1, 'header' => 'test', 'CType'=>'list');
		$indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
		$this->assertEquals(false, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);
		//data correct indexed?
		$aData = $indexDoc->getData();
		$this->assertEquals('test', $aData['title']->getValue(), 'Wrong value for title for uid '.$record['uid']);
		$this->assertEquals('test', $aData['abstract']->getValue(), 'Wrong value for title for uid '.$record['uid']);
		$this->assertEquals('test ', $aData['content']->getValue(), 'Wrong value for title for uid '.$record['uid']);

//
//		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
//		$record = array('uid'=> 127, 'deleted' => 0, 'hidden' => 0, 'sectionIndex' => 0, 'content' => 'test');
//		$indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
//		$this->assertEquals(true, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);

		//no content
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$record = array('uid'=> 128, 'pid' => 1, 'deleted' => 0, 'hidden' => 0, 'CType'=>'list');
		$indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
		$this->assertEquals(true, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);
	}

	/**
	 * Prüft ob nur die Elemente indiziert werden, die im
	 * angegebenen Seitenbaum liegen
	 */
	public function testPrepareSearchData_CheckIncludePageTrees() {
		$indexer = new tx_mksearch_indexer_TtContent();
		list($extKey, $cType) = $indexer->getContentType();

		$record = array('uid'=> 123, 'deleted' => 0, 'hidden' => 0, 'pid' => 3, 'CType'=>'list','bodytext' => 'test');
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		//damit es nicht an der Konfig scheitert
		$options = $this->getDefaultConfig();

		//testbeginn
		$options['include.']['pageTrees.'] = array(1);//als array
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
		$this->assertNotNull($result, 'Element sollte nicht gelöscht werden, da im richtigen Seitenbaum. 1');

		unset($options['include.']['pageTrees.']);//zurücksetzen
		$options['include.']['pageTrees'] = '4';//als string
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
		$this->assertNull($result, 'Element sollte gelöscht werden, da nicht im richtigen Seitenbaum.');

		unset($options['include.']['pageTrees']);//zurücksetzen
		$options['include.']['pageTrees.'] = array(4);//als array
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
		$this->assertNull($result, 'Element sollte gelöscht werden, da nicht im richtigen Seitenbaum.');

		$record2 = array('uid'=> 123, 'deleted' => 0, 'hidden' => 0, 'pid' => 5, 'CType'=>'list','bodytext' => 'test');
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$options['include.']['pageTrees.'] = array(1,4);//als array
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options, 1);
		$this->assertNotNull($result, 'Element sollte nicht gelöscht werden, da im richtigen Seitenbaum. 2');
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$result = $indexer->prepareSearchData('tt_content', $record2, $indexDoc, $options, 1);
		$this->assertNotNull($result, 'Element sollte nicht gelöscht werden, da im richtigen Seitenbaum. 3');

		//nichts explizit inkluded also alles rein
		unset($options['include.']['pageTrees.']);//zurücksetzen
		$record2 = array('uid'=> 123, 'deleted' => 0, 'hidden' => 0, 'pid' => 5, 'CType'=>'list','bodytext' => 'test');
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options, 1);
		$this->assertNotNull($result, 'Element sollte nicht gelöscht werden, da im richtigen Seitenbaum und nichts explizit included. 1');
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$result = $indexer->prepareSearchData('tt_content', $record2, $indexDoc, $options, 1);
		$this->assertNotNull($result, 'Element sollte nicht gelöscht werden, da im richtigen Seitenbaum und nichts explizit included. 2');

		//klappt auch alles wenn das Element auf der obersten Ebene liegt?
		$indexer = new tx_mksearch_indexer_TtContent();
		list($extKey, $cType) = $indexer->getContentType();

		$record = array('uid'=> 123, 'deleted' => 0, 'hidden' => 0, 'pid' => 1, 'CType'=>'list','bodytext' => 'test');
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		//damit es nicht an der Konfig scheitert
		$options = $this->getDefaultConfig();

		//testbeginn
		$options['include.']['pageTrees.'] = array(1);//als array
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
		$this->assertNotNull($result, 'Element sollte nicht gelöscht werden, da im richtigen Seitenbaum. 1');
	}

	/**
	 * Prüft ob nicht die Elemente indiziert werden, die im
	 * angegebenen Seitenbaum liegen
	 */
	public function testPrepareSearchData_CheckExcludePageTrees() {
		$indexer = new tx_mksearch_indexer_TtContent();
		list($extKey, $cType) = $indexer->getContentType();

		$record = array('uid'=> 123, 'deleted' => 0, 'hidden' => 0, 'pid' => 3, 'CType'=>'list','bodytext' => 'test');
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		//damit es nicht an der Konfig scheitert
		$options = $this->getDefaultConfig();

		//testbeginn
		$options['exclude.']['pageTrees.'] = array(4);//als array
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
		$this->assertNotNull($result, 'Element sollte nicht gelöscht werden, da im richtigen Seitenbaum.');

		unset($options['exclude.']['pageTrees.']);//zurücksetzen
		$options['exclude.']['pageTrees'] = '1';//als string
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
		$this->assertNull($result, 'Element sollte gelöscht werden, da nicht im richtigen Seitenbaum. 1');

		unset($options['exclude.']['pageTrees']);//zurücksetzen
		$options['exclude.']['pageTrees.'] = array(1);//als array
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
		$this->assertNull($result, 'Element sollte gelöscht werden, da nicht im richtigen Seitenbaum. 2');

		$record2 = array('uid'=> 123, 'deleted' => 0, 'hidden' => 0, 'pid' => 5, 'CType'=>'list','bodytext' => 'test');
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$options['exclude.']['pageTrees.'] = array(1,4);//als array
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options, 1);
		$this->assertNull($result, 'Element sollte gelöscht werden, da im richtigen Seitenbaum. 3');
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$result = $indexer->prepareSearchData('tt_content', $record2, $indexDoc, $options, 1);
		$this->assertNull($result, 'Element sollte gelöscht werden, da im richtigen Seitenbaum. 4');

		//nichts explizit excluded also alles rein
		unset($options['exclude.']['pageTrees.']);//zurücksetzen
		$record2 = array('uid'=> 123, 'deleted' => 0, 'hidden' => 0, 'pid' => 5, 'CType'=>'list','bodytext' => 'test');
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options, 1);
		$this->assertNotNull($result, 'Element sollte nicht gelöscht werden, da nichts excluded. 1');
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$result = $indexer->prepareSearchData('tt_content', $record2, $indexDoc, $options, 1);
		$this->assertNotNull($result, 'Element sollte nicht gelöscht werden, da nichts excluded. 2');

		//klappt esw auch wenn das Element auf der obersten Ebene liegt?
		$indexer = new tx_mksearch_indexer_TtContent();
		list($extKey, $cType) = $indexer->getContentType();

		$record = array('uid'=> 123, 'deleted' => 0, 'hidden' => 0, 'pid' => 1, 'CType'=>'list','bodytext' => 'test');
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		//damit es nicht an der Konfig scheitert
		$options = $this->getDefaultConfig();

		unset($options['exclude.']['pageTrees.']);//zurücksetzen
		$options['exclude.']['pageTrees'] = '1';//als string
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
		$this->assertNull($result, 'Element sollte gelöscht werden, da nicht im richtigen Seitenbaum. 1');
	}
	
	public function testPrepareSearchDataPreparesTsfeIfTablePagesSoGetPidListWorks() {
		$GLOBALS['TSFE'] = null;
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/tt_content.xml'));
		//damit es nicht an der Konfig scheitert
		$options = $this->getDefaultConfig();

		$indexer = new tx_mksearch_indexer_TtContent();
		list($extKey, $cType) = $indexer->getContentType();

		$record = array('uid'=> 1);
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);

		$result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);

		$this->assertNotNull($GLOBALS['TSFE'],'TSFE wurde nicht geladen!');
	}

	public function testPrepareSearchDataPutsCorrectElementsIntoTheQueueIfTablePagesAndPageHasSubpages() {
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/tt_content.xml'));
		//damit es nicht an der Konfig scheitert
		$options = $this->getDefaultConfig();

		$indexer = new tx_mksearch_indexer_TtContent();
		list($extKey, $cType) = $indexer->getContentType();

		$record = array('uid'=> 1);
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);

		$result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);

		//nichtzs direkt indiziert?
		$this->assertNull($result,'Es wurde nicht null zurück gegeben!');

		$aOptions = array(
			'enablefieldsoff' => true
		);
		$aResult = tx_rnbase_util_DB::doSelect('*', 'tx_mksearch_queue', $aOptions);

		$this->assertEquals(4,count($aResult),'Es wurde nicht der richtige Anzahl in die queue gelegt!');
		//elemente von unterseiten
		$this->assertEquals('tt_content',$aResult[0]['tablename'],'Es wurde nicht das richtige Element (tablename) in die queue gelegt! Element 1');
		$this->assertEquals(4,$aResult[0]['recid'],'Es wurde nicht das richtige Element (recid) in die queue gelegt! Element 1');
		$this->assertEquals('tt_content',$aResult[1]['tablename'],'Es wurde nicht das richtige Element (tablename) in die queue gelegt! Element 2');
		$this->assertEquals(5,$aResult[1]['recid'],'Es wurde nicht das richtige Element (recid) in die queue gelegt! Element 2');
		//elemente auf der eigentlichen seite
		$this->assertEquals('tt_content',$aResult[2]['tablename'],'Es wurde nicht das richtige Element (tablename) in die queue gelegt! Element 3');
		$this->assertEquals(1,$aResult[2]['recid'],'Es wurde nicht das richtige Element (recid) in die queue gelegt! Element 3');
		$this->assertEquals('tt_content',$aResult[3]['tablename'],'Es wurde nicht das richtige Element (tablename) in die queue gelegt! Element 4');
		$this->assertEquals(2,$aResult[3]['recid'],'Es wurde nicht das richtige Element (recid) in die queue gelegt! Element 4');
	}

	public function testPrepareSearchDataDoesNothingIfTablePagesButPageNotExistent() {
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/tt_content.xml'));
		//damit es nicht an der Konfig scheitert
		$options = $this->getDefaultConfig();

		$indexer = new tx_mksearch_indexer_TtContent();
		list($extKey, $cType) = $indexer->getContentType();

		$record = array('uid'=> 99);
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);

		$result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);

		//nichtzs direkt indiziert?
		$this->assertNull($result,'Es wurde nicht null zurück gegeben!');

		$aOptions = array(
			'enablefieldsoff' => true
		);
		$aResult = tx_rnbase_util_DB::doSelect('*', 'tx_mksearch_queue', $aOptions);

		$this->assertEmpty($aResult,'Es wurde doch etwas in die queue gelegt!');
	}

	public function testPrepareSearchDataDoesNothingIfTablePagesButPageHasNoContent() {
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/tt_content.xml'));
		//damit es nicht an der Konfig scheitert
		$options = $this->getDefaultConfig();

		$indexer = new tx_mksearch_indexer_TtContent();
		list($extKey, $cType) = $indexer->getContentType();

		$record = array('uid'=> 5);
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);

		$result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);

		//nichtzs direkt indiziert?
		$this->assertNull($result,'Es wurde nicht null zurück gegeben!');

		$aOptions = array(
			'enablefieldsoff' => true
		);
		$aResult = tx_rnbase_util_DB::doSelect('*', 'tx_mksearch_queue', $aOptions);

		$this->assertEmpty($aResult,'Es wurde doch etwas in die queue gelegt!');
	}

	public function testPrepareSearchDataPutsCorrectElementsIntoTheQueueIfTablePagesAndPageIsHidden() {
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/tt_content.xml'));
		//damit es nicht an der Konfig scheitert
		$options = $this->getDefaultConfig();

		$indexer = new tx_mksearch_indexer_TtContent();
		list($extKey, $cType) = $indexer->getContentType();

		$record = array('uid'=> 7);
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);

		$result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);

		//nichtzs direkt indiziert?
		$this->assertNull($result,'Es wurde nicht null zurück gegeben!');

		$aOptions = array(
			'enablefieldsoff' => true
		);
		$aResult = tx_rnbase_util_DB::doSelect('*', 'tx_mksearch_queue', $aOptions);

		$this->assertEquals(2,count($aResult),'Es wurde nicht der richtige Anzahl in die queue gelegt!');
		$this->assertEquals('tt_content',$aResult[0]['tablename'],'Es wurde nicht das richtige Element (tablename) in die queue gelegt! Element 1');
		$this->assertEquals(3,$aResult[0]['recid'],'Es wurde nicht das richtige Element (recid) in die queue gelegt! Element 1');
		$this->assertEquals('tt_content',$aResult[1]['tablename'],'Es wurde nicht das richtige Element (tablename) in die queue gelegt! Element 2');
		$this->assertEquals(6,$aResult[1]['recid'],'Es wurde nicht das richtige Element (recid) in die queue gelegt! Element 2');
	}

	public function testPrepareSearchDataPutsCorrectElementsIntoTheQueueIfTablePagesAndPageHasNoSubpages() {
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/tt_content.xml'));
		//damit es nicht an der Konfig scheitert
		$options = $this->getDefaultConfig();

		$indexer = new tx_mksearch_indexer_TtContent();
		list($extKey, $cType) = $indexer->getContentType();

		$record = array('uid'=> 2);
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);

		$result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);

		//nichtzs direkt indiziert?
		$this->assertNull($result,'Es wurde nicht null zurück gegeben!');

		$aOptions = array(
			'enablefieldsoff' => true
		);
		$aResult = tx_rnbase_util_DB::doSelect('*', 'tx_mksearch_queue', $aOptions);

		$this->assertEquals(1,count($aResult),'Es wurde nicht der richtige Anzahl in die queue gelegt!');
		$this->assertEquals('tt_content',$aResult[0]['tablename'],'Es wurde nicht das richtige Element (tablename) in die queue gelegt! Element 1');
		$this->assertEquals(3,$aResult[0]['recid'],'Es wurde nicht das richtige Element (recid) in die queue gelegt! Element 1');
	}

	public function testPrepareSearchDataSetsDocToDeletedIfPageIsNotValid() {
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/tt_content.xml'));

		//damit es nicht an der Konfig scheitert
		$options = $this->getDefaultConfig();

		$indexer = new tx_mksearch_indexer_TtContent();
		list($extKey, $cType) = $indexer->getContentType();

		//seite ist versteckt
		$record = array('uid'=> 123, 'pid'=>7, 'deleted' => 0, 'hidden' => 0, 'CType'=>'list','bodytext' => 'test');
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
		$this->assertTrue($result->getDeleted(),'Das Element wurde nicht auf gelöscht gesetzt! Pid: 7');

		//seite ist gelöscht
		$record = array('uid'=> 123, 'pid'=>8, 'deleted' => 0, 'hidden' => 0, 'CType'=>'list','bodytext' => 'test');
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
		$this->assertTrue($result->getDeleted(),'Das Element wurde nicht auf gelöscht gesetzt! Pid: 8');

		$record = array('uid'=> 123, 'pid'=>6, 'deleted' => 0, 'hidden' => 0, 'CType'=>'list','bodytext' => 'test');
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
		$this->assertFalse($result->getDeleted(),'Das Element wurde auf gelöscht gesetzt! Pid: 6');

		//seite ist hart gelöscht bzw. nicht vorhanden
		$record = array('uid'=> 123, 'pid'=>99, 'deleted' => 0, 'hidden' => 0, 'CType'=>'list','bodytext' => 'test');
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
		$this->assertTrue($result->getDeleted(),'Das Element wurde nicht auf gelöscht gesetzt! Pid: 8');

		//seite ist nicht gelöscht aber der parent ist versteckt
		$record = array('uid'=> 123, 'pid'=>10, 'deleted' => 0, 'hidden' => 0, 'CType'=>'list','bodytext' => 'test');
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
		$this->assertTrue($result->getDeleted(),'Das Element wurde nicht auf gelöscht gesetzt! Pid: 10');
	}

	public function testPrepareSearchDataIndexesHeaderLayoutCorrect() {
		$indexer = new tx_mksearch_indexer_TtContent();
		list($extKey, $cType) = $indexer->getContentType();

		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$options = $this->getDefaultConfig();

		//header should be indexed
		$record = array('uid'=> 123, 'pid'=>6, 'CType'=>'list', 'header' => 'test', 'header_layout' => 101);
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options)->getData();
		$this->assertEquals('test',$result['title']->getValue(), 'Titel nicht indiziert!');

		//header shouldn't be indexed
		$record = array('uid'=> 123, 'pid'=>6, 'CType'=>'list', 'header' => 'test', 'header_layout' => 100);
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options)->getData();
		$this->assertEquals('',$result['title']->getValue(), 'Titel doch indiziert!');
	}

	/**
	 * @return array
	 */
	protected function getDefaultConfig() {
		$options = array();
		$options['includeCTypes.'] = array('list');
		$options['CType.']['_default_.']['indexedFields.'] = array('bodytext', 'imagecaption' , 'altText', 'titleText');
		return $options;
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContent_testcase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContent_testcase.php']);
}

?>