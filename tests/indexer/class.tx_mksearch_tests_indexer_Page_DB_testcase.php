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
tx_rnbase::load('tx_mksearch_tests_Util');
tx_rnbase::load('tx_mksearch_service_indexer_core_Config');

require_once(t3lib_extMgm::extPath('mksearch') . 'lib/Apache/Solr/Document.php');

/**
 * Wir müssen in diesem Fall mit der DB testen da wir die pages
 * Tabelle benötigen
 * @author Hannes Bochmann
 *
 */
class tx_mksearch_tests_indexer_Page_DB_testcase extends tx_phpunit_database_testcase {
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
		tx_mksearch_tests_Util::tcaSetUp();

		//WORKAROUND: phpunit seems to backup static attributes (in phpunit.xml)
		//from version 3.6.10 not before. I'm not completely
		//sure about that but from version 3.6.10 clearPageInstance is no
		//more neccessary to have the complete test suite succeed.
		//But this version is buggy. (http://forge.typo3.org/issues/36232)
		//as soon as this bug is fixed, we can use the new phpunit version
		//and dont need this anymore
		tx_mksearch_service_indexer_core_Config::clearPageInstance();

		$this->createDatabase();
		// assuming that test-database can be created otherwise PHPUnit will skip the test
		$this->db = $this->useTestDatabase();

		//das devlog stört nur bei der Testausführung im BE und ist da auch
		//vollkommen unnötig
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['devlog']['nolog'] = true;

		$this->importStdDB();
		$aExtensions = array('cms','mksearch');
		//templavoila und realurl brauchen wir da es im BE sonst Warnungen hagelt
		//und man die Testergebnisse nicht sieht
		if(t3lib_extMgm::isLoaded('realurl')) $aExtensions[] = 'realurl';
		if(t3lib_extMgm::isLoaded('templavoila')) $aExtensions[] = 'templavoila';
		// fügt felder bei datenbank abfragen hinzu in $TYPO3_CONF_VARS['FE']['pageOverlayFields']
		// und $TYPO3_CONF_VARS['FE']['addRootLineFields']
		if(t3lib_extMgm::isLoaded('tq_seo')) $aExtensions[] = 'tq_seo';

		$this->importExtensions($aExtensions);

		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/pages.xml'));

		// eventuelle hooks entfernen
		tx_mksearch_tests_Util::hooksSetUp();
	}

	/**
	 * tearDown() = destroy DB etc.
	 */
	public function tearDown () {
		tx_mksearch_tests_Util::hooksTearDown();

		$this->cleanDatabase();
		$this->dropDatabase();
		$GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db);

		$GLOBALS['BE_USER']->setWorkspace($this->workspaceIdAtStart);

		// hooks zurücksetzen
		tx_mksearch_tests_Util::hooksTearDown();
	}

	public function testPrepareSearchDataPreparesTsfeIfTablePagesSoGetPidListWorks() {
		$GLOBALS['TSFE'] = null;
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_Page');
		list($extKey, $cType) = $indexer->getContentType();

		$record = array('uid'=> 2, 'doktype' => 1);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);

		$oIndexDoc = $indexer->prepareSearchData('pages', $record, $indexDoc, array());

		$this->assertNotNull($GLOBALS['TSFE'],'TSFE wurde nicht geladen!');
	}

	public function testPrepareSearchDataPutsCorrectShortcutTargetAndSubpagesIntoTheQueue() {
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_Page');
		list($extKey, $cType) = $indexer->getContentType();

		$record = array('uid'=> 1, 'doktype' => 4, 'shortcut' => 2);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);

		$result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);

		//nichtzs direkt indiziert?
		$this->assertNull($result,'Es wurde nicht null zurück gegeben!');

		$aOptions = array(
			'enablefieldsoff' => true
		);
		$aResult = tx_rnbase_util_DB::doSelect('*', 'tx_mksearch_queue', $aOptions);

		$this->assertEquals(3,count($aResult),'Es wurde nicht der richtige Anzahl in die queue gelegt!');

		//die anderen subpages sollten auch drin liegen
		$this->assertEquals('pages',$aResult[0]['tablename'],'Es wurde nicht das richtige Element (tablename) in die queue gelegt! Element 1');
		$this->assertEquals(3,$aResult[0]['recid'],'Es wurde nicht das richtige Element (recid) in die queue gelegt! Element 1');
		$this->assertEquals('pages',$aResult[1]['tablename'],'Es wurde nicht das richtige Element (tablename) in die queue gelegt! Element 2');
		$this->assertEquals(9,$aResult[1]['recid'],'Es wurde nicht das richtige Element (recid) in die queue gelegt! Element 2');

		//und das shortcut ziel
		$this->assertEquals('pages',$aResult[2]['tablename'],'Es wurde nicht das richtige Element (tablename) in die queue gelegt! Element 3');
		$this->assertEquals(2,$aResult[2]['recid'],'Es wurde nicht das richtige Element (recid) in die queue gelegt! Element 3');
	}

	public function testPrepareSearchDataPutsNoSubpagesIntoTheQueueIfPageHasNone() {
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_Page');
		list($extKey, $cType) = $indexer->getContentType();

		$record = array('uid'=> 2, 'doktype' => 1);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);

		$oIndexDoc = $indexer->prepareSearchData('pages', $record, $indexDoc, array());

		$this->assertEquals('core:page:2',$oIndexDoc->getPrimaryKey(true),'falsch indiziert!');

		$aOptions = array(
			'enablefieldsoff' => true
		);
		$aResult = tx_rnbase_util_DB::doSelect('*', 'tx_mksearch_queue', $aOptions);

		$this->assertEmpty($aResult,'Es wurden doch Elemente in die queue gelegt!');
	}

	public function testPrepareSearchDataSetsDocToDeleted() {
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_Page');
		$options = array();

		list($extKey, $cType) = $indexer->getContentType();

		//is hidden
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_Page');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$record = array('uid'=> 124, 'pid' => 0, 'deleted' => 0, 'hidden' => 1, 'title'=>'list');
		$indexer->prepareSearchData('pages', $record, $indexDoc, $options);
		$this->assertEquals(true, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);

		//everything alright
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_Page');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$record = array('uid'=> 125, 'pid' => 1, 'deleted' => 0, 'hidden' => 0, 'title' => 'test');
		$indexer->prepareSearchData('pages', $record, $indexDoc, $options);
		$this->assertEquals(false, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);

		//parent is hidden
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$record = array('uid'=> 10, 'pid' => 7, 'deleted' => 0, 'hidden' => 0, 'title' => 'test');
		$indexer->prepareSearchData('pages', $record, $indexDoc, $options);
		$this->assertEquals(true, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);

		//everything alright with parents
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_Page');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$record = array('uid'=> 126, 'pid' => 3, 'deleted' => 0, 'hidden' => 0, 'title' => 'test');
		$indexer->prepareSearchData('pages', $record, $indexDoc, $options);
		$this->assertEquals(false, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);
	}

	/**
	 * Prüft ob nicht die Elemente indiziert werden, die im
	 * angegebenen Seitenbaum liegen
	 */
	public function testPrepareSearchDataCheckExcludePageTrees() {
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_Page');
		list($extKey, $cType) = $indexer->getContentType();

		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);

		//testbeginn
		$record = array('uid'=> 1,'title' => 'testPage');
		$options['exclude.']['pageTrees.'] = array(1);//als array
		$result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);
		$this->assertNull($result, 'Element sollte gelöscht werden, da nicht im richtigen Seitenbaum. 1');

		$options['exclude.']['pageTrees.'] = array(4);//als array
		$result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);
		$this->assertNotNull($result, 'Element sollte nicht gelöscht werden, da im richtigen Seitenbaum. 1');

		$record = array('uid'=> 3,'title' => 'testPage');
		$options['exclude.']['pageTrees.'] = array(1);//als array
		$result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);
		$this->assertNull($result, 'Element sollte gelöscht werden, da nicht im richtigen Seitenbaum. 2');

		$record2 = array('uid'=> 5,'title' => 'testPage');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options['exclude.']['pageTrees.'] = array(1,4);//als array
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options, 1);
		$this->assertNull($result, 'Element sollte gelöscht werden, da im richtigen Seitenbaum. 3');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$result = $indexer->prepareSearchData('tt_content', $record2, $indexDoc, $options, 1);
		$this->assertNull($result, 'Element sollte gelöscht werden, da im richtigen Seitenbaum. 4');

		//nichts explizit excluded also alles rein
		unset($options['exclude.']['pageTrees.']);//zurücksetzen
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options, 1);
		$this->assertNotNull($result, 'Element sollte nicht gelöscht werden, da nichts excluded. 1');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$result = $indexer->prepareSearchData('tt_content', $record2, $indexDoc, $options, 1);
		$this->assertNotNull($result, 'Element sollte nicht gelöscht werden, da nichts excluded. 2');
	}

	/**
	 * Prüft ob nicht die Elemente indiziert werden, die im
	 * angegebenen Seitenbaum liegen
	 */
	public function testPrepareSearchDataCheckIncludePageTrees() {
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_Page');
		list($extKey, $cType) = $indexer->getContentType();

		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);

		//testbeginn
		$record = array('uid'=> 1,'title' => 'testPage');
		$options['include.']['pageTrees.'] = array(1);//als array
		$result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);
		$this->assertNotNull($result, 'Element sollte nicht gelöscht werden, da im richtigen Seitenbaum. 1');

		$options['include.']['pageTrees.'] = array(4);//als array
		$result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);
		$this->assertNull($result, 'Element sollte gelöscht werden, da nicht im richtigen Seitenbaum. 1');

		$record = array('uid'=> 4,'title' => 'testPage');
		$options['include.']['pageTrees.'] = array(1);//als array
		$result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);
		$this->assertNull($result, 'Element sollte gelöscht werden, da nicht im richtigen Seitenbaum. 2');

		$record = array('uid'=> 3,'title' => 'testPage');
		$options['include.']['pageTrees.'] = array(1);//als array
		$result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);
		$this->assertNotNull($result, 'Element sollte nicht gelöscht werden, da im richtigen Seitenbaum. 2');

		$record2 = array('uid'=> 5,'title' => 'testPage');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options['include.']['pageTrees.'] = array(1,4);//als array
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options, 1);
		$this->assertNotNull($result, 'Element sollte nicht gelöscht werden, da im richtigen Seitenbaum. 3');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$result = $indexer->prepareSearchData('tt_content', $record2, $indexDoc, $options, 1);
		$this->assertNotNull($result, 'Element sollte nicht gelöscht werden, da im richtigen Seitenbaum. 4');

		//nichts explizit included also alles rein
		unset($options['include.']['pageTrees.']);//zurücksetzen
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options, 1);
		$this->assertNotNull($result, 'Element sollte nicht gelöscht werden, da nichts included. 1');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$result = $indexer->prepareSearchData('tt_content', $record2, $indexDoc, $options, 1);
		$this->assertNotNull($result, 'Element sollte nicht gelöscht werden, da nichts included. 2');
	}

	/**
	 *
	 */
	public function testPrepareSearchDataCheckExcludePages() {
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_Page');
		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
			'exclude.'=>array('pages.' => array(2))
		);

		$aRawData = array('uid' => 1, 'title' => 'test');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNotNull($aIndexDoc,'Das Element wurde nicht indiziert!');

		$aRawData = array('uid' => 2, 'title' => 'test');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNull($aIndexDoc,'Das Element wurde doch indiziert!');
	}

	/**
	 *
	 */
	public function testPrepareSearchDataCheckIncludePages() {
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_Page');
		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
			'include.'=>array('pages.' => array(2))
		);

		$aRawData = array('uid' => 1, 'title' => 'test');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNull($aIndexDoc,'Das Element wurde doch indiziert!');

		$aRawData = array('uid' => 2, 'title' => 'test');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		$this->assertNotNull($aIndexDoc,'Das Element wurde nicht indiziert!');
	}

	/**
	 *
	 */
	public function testPrepareSearchDataProducesCorrectDocByMapping() {
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_Page');
		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options = array('mapping.' => array(
				'title' => 'title_s',
				'abstract' => 'abstract_s',
				'doktype' => 'doktype_i',
				'emptyDummyField' => 'emptyDummyField_s'
			)
		);

		$aRawData = array('uid' => 1, 'title' => 'testPage', 'abstract' => '<a href="http://www.test.de">test</a> test page for tests :-D', 'doktype' => 2);

		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options)->getData();

		$this->assertEquals('testPage',$aIndexDoc['title_s']->getValue(),'Es wurde nicht das Feld [title_s] richtig gesetzt!');
		$this->assertEquals('test  test page for tests :-D',$aIndexDoc['abstract_s']->getValue(),'Es wurde nicht das Feld [abstract_s] richtig gesetzt!');
		$this->assertEquals(2,$aIndexDoc['doktype_i']->getValue(),'Es wurde nicht das Feld [doktype_i] richtig gesetzt!');
		$this->assertTrue(empty($aIndexDoc['emptyDummyField_s']),'Es wurde nicht das Feld [emptyDummyField_s] richtig gesetzt!');
		//common fields

		$this->assertEquals('test test page for tests :-D',$aIndexDoc['abstract']->getValue(),'Es wurde nicht das Feld [abstract] richtig gesetzt!');
		$this->assertEquals('testPage',$aIndexDoc['title']->getValue(),'Es wurde nicht das Feld [title] richtig gesetzt!');
	}

	/**
	 *
	 */
	public function testPrepareSearchDataProducesCorrectDocByMappingWithKeepHtml() {
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_Page');
		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options = array(
			'mapping.' => array(
				'title' => 'title_s',
				'abstract' => 'abstract_s',
				'doktype' => 'doktype_i',
				'emptyDummyField' => 'emptyDummyField_s'
			),
			'keepHtml' => 1
		);

		$aRawData = array('uid' => 1, 'title' => 'testPage', 'abstract' => '<a href="http://www.test.de">test</a> test page for tests :-D', 'doktype' => 2);

		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options)->getData();

		$this->assertEquals('testPage',$aIndexDoc['title_s']->getValue(),'Es wurde nicht das Feld [title_s] richtig gesetzt!');
		$this->assertEquals('<a href="http://www.test.de">test</a> test page for tests :-D',$aIndexDoc['abstract_s']->getValue(),'Es wurde nicht das Feld [abstract_s] richtig gesetzt!');
		$this->assertEquals(2,$aIndexDoc['doktype_i']->getValue(),'Es wurde nicht das Feld [doktype_i] richtig gesetzt!');
		$this->assertTrue(empty($aIndexDoc['emptyDummyField_s']),'Es wurde nicht das Feld [emptyDummyField_s] richtig gesetzt!');
		//common fields
		$this->assertEquals('test  test page for tests :-D',$aIndexDoc['abstract']->getValue(),'Es wurde nicht das Feld [abstract] richtig gesetzt!');
		$this->assertEquals('testPage',$aIndexDoc['title']->getValue(),'Es wurde nicht das Feld [title] richtig gesetzt!');
	}

	/**
	 *
	 */
	public function testPrepareSearchDataProducesCorrectDocWithoutMapping() {
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_Page');
		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options = array();

		$aRawData = array('uid' => 1, 'title' => 'testPage', 'abstract' => 'test page for tests :-D', 'doktype' => 2);

		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options)->getData();

		$this->assertTrue(empty($aIndexDoc['title_s']),'Es wurde nicht das Feld [title_s] richtig gesetzt!');
		$this->assertTrue(empty($aIndexDoc['abstract_s']),'Es wurde nicht das Feld [abstract_s] richtig gesetzt!');
		$this->assertTrue(empty($aIndexDoc['doktype_i']),'Es wurde nicht das Feld [doktype_i] richtig gesetzt!');
		$this->assertTrue(empty($aIndexDoc['emptyDummyField_s']),'Es wurde nicht das Feld [emptyDummyField_s] richtig gesetzt!');
		//common fields
		$this->assertEquals('test page for tests :-D',$aIndexDoc['abstract']->getValue(),'Es wurde nicht das Feld [abstract] richtig gesetzt!');
		$this->assertEquals('testPage',$aIndexDoc['title']->getValue(),'Es wurde nicht das Feld [title] richtig gesetzt!');
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContent_testcase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContent_testcase.php']);
}

?>