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
require_once t3lib_extMgm::extPath('mksearch', 'lib/Apache/Solr/Document.php');
tx_rnbase::load('tx_mksearch_tests_Testcase');
tx_rnbase::load('tx_rnbase_util_Dates');



/**
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_tests
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_indexer_TtNewsNews_testcase
	extends tx_mksearch_tests_Testcase {

	protected function setUp() {
		if(!t3lib_extMgm::isLoaded('tt_news')) {
			$this->markTestSkipped('tt_news is not installed!');
		}
		parent::setUp();
	}

	function test_prepareSearchData() {
		$options = array();
		$options['indexedFields.'] = array(
			'uid_i' => 'uid',
			'bodytext_s' => 'bodytext'
		);

		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_TtNewsNews');

		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType, 'tx_mksearch_model_IndexerFieldBase');

		// Datumswerte werden immer in UTC in den Index gelegt
		$tstamp = tx_rnbase_util_Dates::datetime_mysql2tstamp('2010-01-30 15:00:00', 'UTC');
		$record = array('uid'=> 123, 'deleted' => 0, 'datetime'=>$tstamp, 'bodytext'=>'some test');
		$indexer->prepareSearchData('tt_news', $record, $indexDoc, $options);
		$data = $indexDoc->getData();
		self::assertEquals('2010-01-30T15:00:00Z', $data['datetime_dt']->getValue());
		//indexing of extra fields worked?
		self::assertEquals(array('some test'), $data['bodytext_s']->getValue());
		self::assertEquals(array(123), $data['uid_i']->getValue());
		//merging into content worked?
		self::assertEquals('123 some test', $data['content']->getValue());
		//indexing of standard fields worked?
		self::assertEquals('some test', $data['news_text_s']->getValue(),'news_text_s falsch');
		self::assertEquals('some test', $data['news_text_t']->getValue(),'news_text_t falsch');
	}

	function test_prepareSearchDataWithHtmlMarkupInBodytext() {
		$options = array();
		$options['indexedFields.'] = array(
			'uid_i' => 'uid',
			'bodytext_s' => 'bodytext'
		);

		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_TtNewsNews');

		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType, 'tx_mksearch_model_IndexerFieldBase');

		// Datumswerte werden immer in UTC in den Index gelegt
		$tstamp = tx_rnbase_util_Dates::datetime_mysql2tstamp('2010-01-30 15:00:00', 'UTC');
		$record = array('uid'=> 123, 'deleted' => 0, 'datetime'=>$tstamp, 'bodytext'=>'some test <p>with html markup</p>');
		$indexer->prepareSearchData('tt_news', $record, $indexDoc, $options);
		$data = $indexDoc->getData();
		self::assertEquals('2010-01-30T15:00:00Z', $data['datetime_dt']->getValue(),'datetime falsch');
		//indexing of extra fields worked?
		self::assertEquals(array('some test  with html markup'), $data['bodytext_s']->getValue(),'bodytext falsch');
		self::assertEquals(array(123), $data['uid_i']->getValue());
		//merging into content worked?
		self::assertEquals('123 some test  with html markup', $data['content']->getValue(),'content falsch');
		//indexing of standard fields worked?
		self::assertEquals('some test  with html markup', $data['news_text_s']->getValue(),'news_text_s falsch');
		self::assertEquals('some test  with html markup', $data['news_text_t']->getValue(),'news_text_t falsch');
	}

	function test_prepareSearchDataWithHtmlMarkupInBodytextAndkeepHtmlOption() {
		$options = array(
			'indexedFields.' => array(
				'uid_i' => 'uid',
				'bodytext_s' => 'bodytext'
			),
			'keepHtml' => 1
		);

		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_TtNewsNews');

		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType, 'tx_mksearch_model_IndexerFieldBase');

		// Datumswerte werden immer in UTC in den Index gelegt
		$tstamp = tx_rnbase_util_Dates::datetime_mysql2tstamp('2010-01-30 15:00:00', 'UTC');
		$record = array('uid'=> 123, 'deleted' => 0, 'datetime'=>$tstamp, 'bodytext'=>'some test <p>with html markup</p>');
		$indexer->prepareSearchData('tt_news', $record, $indexDoc, $options);
		$data = $indexDoc->getData();
		self::assertEquals('2010-01-30T15:00:00Z', $data['datetime_dt']->getValue(),'datetime falsch');
		//indexing of extra fields worked?
		self::assertEquals(array('some test <p>with html markup</p>'), $data['bodytext_s']->getValue(),'bodytext falsch');
		self::assertEquals(array(123), $data['uid_i']->getValue());
		//merging into content worked?
		self::assertEquals('123 some test <p>with html markup</p> ', $data['content']->getValue(),'content falsch');
		//indexing of standard fields worked?
		self::assertEquals('some test <p>with html markup</p>', $data['news_text_s']->getValue(),'news_text_s falsch');
		self::assertEquals('some test <p>with html markup</p>', $data['news_text_t']->getValue(),'news_text_t falsch');
	}

	function test_prepareSearchDataDeleted() {
		$options = array();
		$options['indexedFields'] = 'uid,bodytext';

		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_TtNewsNews');

		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType, 'tx_mksearch_model_IndexerFieldBase');
		$record = array('uid'=> 123, 'deleted' => 1);
		$indexer->prepareSearchData('tt_news', $record, $indexDoc, $options);
		self::assertEquals(true, $indexDoc->getDeleted());

		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType, 'tx_mksearch_model_IndexerFieldBase');
		$record = array('uid'=> 123, 'deleted' => 0, 'hidden' => 1);
		$indexer->prepareSearchData('tt_news', $record, $indexDoc, $options);
		self::assertEquals(true, $indexDoc->getDeleted());

		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType, 'tx_mksearch_model_engineSpecific_solr_IndexerField');
		$record = array('uid'=> 123, 'deleted' => 0, 'hidden' => 0, 'bodytext' => 'some test');
		$indexer->prepareSearchData('tt_news', $record, $indexDoc, $options);
		self::assertEquals(false, $indexDoc->getDeleted());
		$data = $indexDoc->getData();
		//merging into content worked?
		self::assertEquals('123 some test', $data['content']->getValue());
		//no extra fields added?
		self::assertFalse(array_key_exists('uid_i', $data),'uid_i Field is existent!');
		self::assertFalse(array_key_exists('uid', $data),'uid Field is existent!');
		self::assertFalse(array_key_exists('bodytext_s', $data),'bodytext_s Field is existent!');
		self::assertFalse(array_key_exists('bodytext', $data),'bodytext Field is existent!');
	}

	function test_prepareSearchDataWithoutIndexedFieldsOption() {
		$options = array();

		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_TtNewsNews');

		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType, 'tx_mksearch_model_IndexerFieldBase');

		// Datumswerte werden immer in UTC in den Index gelegt
		$tstamp = tx_rnbase_util_Dates::datetime_mysql2tstamp('2010-01-30 15:00:00', 'UTC');
		$record = array('uid'=> 123, 'deleted' => 0, 'datetime'=>$tstamp, 'bodytext'=>'some test <p>with html markup</p>');
		$indexer->prepareSearchData('tt_news', $record, $indexDoc, $options);
		$data = $indexDoc->getData();
		self::assertEquals('2010-01-30T15:00:00Z', $data['datetime_dt']->getValue(),'datetime falsch');
		//indexing of standard fields worked?
		self::assertEquals('some test  with html markup', $data['news_text_s']->getValue(),'news_text_s falsch');
		self::assertEquals('some test  with html markup', $data['news_text_t']->getValue(),'news_text_t falsch');
		//merging into content ignored as nothing?
		self::assertTrue(empty($data['content']),'content falsch');
	}

	/**
	 * @group unit
	 */
	public function testGetDbUtil() {
		self::assertEquals(
			'tx_rnbase_util_DB',
			$this->callInaccessibleMethod(
				tx_rnbase::makeInstance('tx_mksearch_indexer_TtNewsNews'),
				'getDbUtil'
			)
		);
	}

	/**
	 * @group unit
	 */
	public function testGetIntIndexService() {
		self::assertInstanceOf(
			'tx_mksearch_service_internal_Index',
			$this->callInaccessibleMethod(
				tx_rnbase::makeInstance('tx_mksearch_indexer_TtNewsNews'),
				'getIntIndexService'
			)
		);
	}

	/**
	 * @group unit
	 */
	public function testStopIndexingWhenTtNewsCatChangedThrowsNoException() {
		$options = array();

		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_TtNewsNews');

		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType, 'tx_mksearch_model_IndexerFieldBase');

		$record = array('uid' => 123);
		$indexer->prepareSearchData('tt_news_cat', $record, $indexDoc, $options);
		self::assertTrue(TRUE);
	}

	/**
	 * @group unit
	 */
	public function testStopIndexingWhenTtNewsCatChangedPutsREcordsCorrectToQueue() {
		$options = array();

		$indexService = $this->getMock(
			'tx_mksearch_service_internal_Index', array('addRecordToIndex')
		);
		$indexService->expects($this->at(0))
			->method('addRecordToIndex')
			->with('tt_news', 456);
		$indexService->expects($this->at(1))
			->method('addRecordToIndex')
			->with('tt_news', 789);

		// Vor dem Mocking aufrufen, da sonst die static Methode weg ist...
		tx_rnbase::load('tx_mksearch_indexer_TtNewsNews');
		list($extKey, $cType) = tx_mksearch_indexer_TtNewsNews::getContentType();

		$indexer = $this->getMock(
			'tx_mksearch_indexer_TtNewsNews', array('doSelect', 'getIntIndexService')
		);
		$indexer->expects($this->once())
			->method('doSelect')
			->with(
				'tt_news.uid AS uid',
				array('tt_news_cat_mm JOIN tt_news ON tt_news.uid=tt_news_cat_mm.uid_local AND tt_news.deleted=0', 'tt_news_cat_mm'),
				array('where' => 'tt_news_cat_mm.uid_foreign=123', 'enablefieldsoff' => TRUE)
			)
			->will($this->returnValue(array(0 => array('uid' => 456), 1 => array('uid' => 789))));
		$indexer->expects($this->once())
			->method('getIntIndexService')
			->will($this->returnValue($indexService));

		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType, 'tx_mksearch_model_IndexerFieldBase');

		$record = array('uid' => 123);
		$indexerReturn = $indexer->prepareSearchData('tt_news_cat', $record, $indexDoc, $options);
		self::assertNull($indexerReturn);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtNewsNews_testcase.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtNewsNews_testcase.php']);
}

?>
