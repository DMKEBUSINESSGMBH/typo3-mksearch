<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 DMK E-Business GmbH
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

require_once t3lib_extMgm::extPath('rn_base', 'class.tx_rnbase.php');
tx_rnbase::load('tx_mksearch_tests_DbTestcase');
tx_rnbase::load('tx_mksearch_tests_Util');
tx_rnbase::load('tx_mksearch_service_indexer_core_Config');

require_once(t3lib_extMgm::extPath('mksearch') . 'lib/Apache/Solr/Document.php');

/**
 * Wir müssen in diesem Fall mit der DB testen da wir die pages
 * Tabelle benötigen
 *
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_tests
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_indexer_Page_DB_testcase
	extends tx_mksearch_tests_DbTestcase {

	/**
	 * Constructs a test case with the given name.
	 *
	 * @param string $name the name of a testcase
	 * @param array $data ?
	 * @param string $dataName ?
	 */
	public function __construct($name = NULL, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);

		$this->importDataSets[] = tx_mksearch_tests_Util::getFixturePath('db/pages.xml');
	}

	public function testPrepareSearchDataPreparesTsfeIfTablePagesSoGetPidListWorks() {
		$GLOBALS['TSFE'] = null;
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_Page');
		list($extKey, $cType) = $indexer->getContentType();

		$record = array('uid'=> 2, 'doktype' => 1);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);

		$oIndexDoc = $indexer->prepareSearchData('pages', $record, $indexDoc, array());

		self::assertNotNull($GLOBALS['TSFE'],'TSFE wurde nicht geladen!');
	}

	public function testPrepareSearchDataPutsCorrectShortcutTargetAndSubpagesIntoTheQueue() {
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_Page');
		list($extKey, $cType) = $indexer->getContentType();

		$record = array('uid'=> 1, 'doktype' => 4, 'shortcut' => 2);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);

		$result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);

		//nichtzs direkt indiziert?
		self::assertNull($result,'Es wurde nicht null zurück gegeben!');

		$aOptions = array(
			'enablefieldsoff' => true
		);
		$aResult = tx_rnbase_util_DB::doSelect('*', 'tx_mksearch_queue', $aOptions);

		self::assertEquals(3,count($aResult),'Es wurde nicht der richtige Anzahl in die queue gelegt!');

		//die anderen subpages sollten auch drin liegen
		self::assertEquals('pages',$aResult[0]['tablename'],'Es wurde nicht das richtige Element (tablename) in die queue gelegt! Element 1');
		self::assertEquals(3,$aResult[0]['recid'],'Es wurde nicht das richtige Element (recid) in die queue gelegt! Element 1');
		self::assertEquals('pages',$aResult[1]['tablename'],'Es wurde nicht das richtige Element (tablename) in die queue gelegt! Element 2');
		self::assertEquals(9,$aResult[1]['recid'],'Es wurde nicht das richtige Element (recid) in die queue gelegt! Element 2');

		//und das shortcut ziel
		self::assertEquals('pages',$aResult[2]['tablename'],'Es wurde nicht das richtige Element (tablename) in die queue gelegt! Element 3');
		self::assertEquals(2,$aResult[2]['recid'],'Es wurde nicht das richtige Element (recid) in die queue gelegt! Element 3');
	}

	public function testPrepareSearchDataPutsNoSubpagesIntoTheQueueIfPageHasNone() {
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_Page');
		list($extKey, $cType) = $indexer->getContentType();

		$record = array('uid'=> 2, 'doktype' => 1);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);

		$oIndexDoc = $indexer->prepareSearchData('pages', $record, $indexDoc, array());

		self::assertEquals('core:page:2',$oIndexDoc->getPrimaryKey(true),'falsch indiziert!');

		$aOptions = array(
			'enablefieldsoff' => true
		);
		$aResult = tx_rnbase_util_DB::doSelect('*', 'tx_mksearch_queue', $aOptions);

		self::assertEmpty($aResult,'Es wurden doch Elemente in die queue gelegt!');
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
		self::assertEquals(true, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);

		//everything alright
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_Page');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$record = array('uid'=> 125, 'pid' => 1, 'deleted' => 0, 'hidden' => 0, 'title' => 'test');
		$indexer->prepareSearchData('pages', $record, $indexDoc, $options);
		self::assertEquals(false, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);

		//parent is hidden
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$record = array('uid'=> 10, 'pid' => 7, 'deleted' => 0, 'hidden' => 0, 'title' => 'test');
		$indexer->prepareSearchData('pages', $record, $indexDoc, $options);
		self::assertEquals(true, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);

		//everything alright with parents
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_Page');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$record = array('uid'=> 126, 'pid' => 3, 'deleted' => 0, 'hidden' => 0, 'title' => 'test');
		$indexer->prepareSearchData('pages', $record, $indexDoc, $options);
		self::assertEquals(false, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);
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
		self::assertNull($result, 'Element sollte gelöscht werden, da nicht im richtigen Seitenbaum. 1');

		$options['exclude.']['pageTrees.'] = array(4);//als array
		$result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);
		self::assertNotNull($result, 'Element sollte nicht gelöscht werden, da im richtigen Seitenbaum. 1');

		$record = array('uid'=> 3,'title' => 'testPage');
		$options['exclude.']['pageTrees.'] = array(1);//als array
		$result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);
		self::assertNull($result, 'Element sollte gelöscht werden, da nicht im richtigen Seitenbaum. 2');

		$record2 = array('uid'=> 5,'title' => 'testPage');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options['exclude.']['pageTrees.'] = array(1,4);//als array
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options, 1);
		self::assertNull($result, 'Element sollte gelöscht werden, da im richtigen Seitenbaum. 3');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$result = $indexer->prepareSearchData('tt_content', $record2, $indexDoc, $options, 1);
		self::assertNull($result, 'Element sollte gelöscht werden, da im richtigen Seitenbaum. 4');

		//nichts explizit excluded also alles rein
		unset($options['exclude.']['pageTrees.']);//zurücksetzen
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options, 1);
		self::assertNotNull($result, 'Element sollte nicht gelöscht werden, da nichts excluded. 1');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$result = $indexer->prepareSearchData('tt_content', $record2, $indexDoc, $options, 1);
		self::assertNotNull($result, 'Element sollte nicht gelöscht werden, da nichts excluded. 2');
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
		self::assertNotNull($result, 'Element sollte nicht gelöscht werden, da im richtigen Seitenbaum. 1');

		$options['include.']['pageTrees.'] = array(4);//als array
		$result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);
		self::assertNull($result, 'Element sollte gelöscht werden, da nicht im richtigen Seitenbaum. 1');

		$record = array('uid'=> 4,'title' => 'testPage');
		$options['include.']['pageTrees.'] = array(1);//als array
		$result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);
		self::assertNull($result, 'Element sollte gelöscht werden, da nicht im richtigen Seitenbaum. 2');

		$record = array('uid'=> 3,'title' => 'testPage');
		$options['include.']['pageTrees.'] = array(1);//als array
		$result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);
		self::assertNotNull($result, 'Element sollte nicht gelöscht werden, da im richtigen Seitenbaum. 2');

		$record2 = array('uid'=> 5,'title' => 'testPage');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options['include.']['pageTrees.'] = array(1,4);//als array
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options, 1);
		self::assertNotNull($result, 'Element sollte nicht gelöscht werden, da im richtigen Seitenbaum. 3');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$result = $indexer->prepareSearchData('tt_content', $record2, $indexDoc, $options, 1);
		self::assertNotNull($result, 'Element sollte nicht gelöscht werden, da im richtigen Seitenbaum. 4');

		//nichts explizit included also alles rein
		unset($options['include.']['pageTrees.']);//zurücksetzen
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options, 1);
		self::assertNotNull($result, 'Element sollte nicht gelöscht werden, da nichts included. 1');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$result = $indexer->prepareSearchData('tt_content', $record2, $indexDoc, $options, 1);
		self::assertNotNull($result, 'Element sollte nicht gelöscht werden, da nichts included. 2');
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
		self::assertNotNull($aIndexDoc,'Das Element wurde nicht indiziert!');

		$aRawData = array('uid' => 2, 'title' => 'test');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		self::assertNull($aIndexDoc,'Das Element wurde doch indiziert!');
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
		self::assertNull($aIndexDoc,'Das Element wurde doch indiziert!');

		$aRawData = array('uid' => 2, 'title' => 'test');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		self::assertNotNull($aIndexDoc,'Das Element wurde nicht indiziert!');
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

		self::assertEquals('testPage',$aIndexDoc['title_s']->getValue(),'Es wurde nicht das Feld [title_s] richtig gesetzt!');
		self::assertEquals('test  test page for tests :-D',$aIndexDoc['abstract_s']->getValue(),'Es wurde nicht das Feld [abstract_s] richtig gesetzt!');
		self::assertEquals(2,$aIndexDoc['doktype_i']->getValue(),'Es wurde nicht das Feld [doktype_i] richtig gesetzt!');
		self::assertTrue(empty($aIndexDoc['emptyDummyField_s']),'Es wurde nicht das Feld [emptyDummyField_s] richtig gesetzt!');
		//common fields

		self::assertEquals('test test page for tests :-D',$aIndexDoc['abstract']->getValue(),'Es wurde nicht das Feld [abstract] richtig gesetzt!');
		self::assertEquals('testPage',$aIndexDoc['title']->getValue(),'Es wurde nicht das Feld [title] richtig gesetzt!');
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

		self::assertEquals('testPage',$aIndexDoc['title_s']->getValue(),'Es wurde nicht das Feld [title_s] richtig gesetzt!');
		self::assertEquals('<a href="http://www.test.de">test</a> test page for tests :-D',$aIndexDoc['abstract_s']->getValue(),'Es wurde nicht das Feld [abstract_s] richtig gesetzt!');
		self::assertEquals(2,$aIndexDoc['doktype_i']->getValue(),'Es wurde nicht das Feld [doktype_i] richtig gesetzt!');
		self::assertTrue(empty($aIndexDoc['emptyDummyField_s']),'Es wurde nicht das Feld [emptyDummyField_s] richtig gesetzt!');
		//common fields
		self::assertEquals('test  test page for tests :-D',$aIndexDoc['abstract']->getValue(),'Es wurde nicht das Feld [abstract] richtig gesetzt!');
		self::assertEquals('testPage',$aIndexDoc['title']->getValue(),'Es wurde nicht das Feld [title] richtig gesetzt!');
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

		self::assertTrue(empty($aIndexDoc['title_s']),'Es wurde nicht das Feld [title_s] richtig gesetzt!');
		self::assertTrue(empty($aIndexDoc['abstract_s']),'Es wurde nicht das Feld [abstract_s] richtig gesetzt!');
		self::assertTrue(empty($aIndexDoc['doktype_i']),'Es wurde nicht das Feld [doktype_i] richtig gesetzt!');
		self::assertTrue(empty($aIndexDoc['emptyDummyField_s']),'Es wurde nicht das Feld [emptyDummyField_s] richtig gesetzt!');
		//common fields
		self::assertEquals('test page for tests :-D',$aIndexDoc['abstract']->getValue(),'Es wurde nicht das Feld [abstract] richtig gesetzt!');
		self::assertEquals('testPage',$aIndexDoc['title']->getValue(),'Es wurde nicht das Feld [title] richtig gesetzt!');
	}
}
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContent_testcase.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContent_testcase.php']);
}
