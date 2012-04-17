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
tx_rnbase::load('tx_rnbase_util_Dates');


require_once(t3lib_extMgm::extPath('mksearch') . 'lib/Apache/Solr/Document.php');

class tx_mksearch_tests_indexer_TtNewsNews_testcase extends tx_phpunit_testcase {
	
	public function setUp() {
		if(!t3lib_extMgm::isLoaded('tt_news'))
			$this->markTestSkipped('tt_news is not installed!');
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
		$this->assertEquals('2010-01-30T15:00:00Z', $data['datetime_dt']->getValue());
		//indexing of extra fields worked?
		$this->assertEquals('some test', $data['bodytext_s']->getValue());
		$this->assertEquals(123, $data['uid_i']->getValue());
		//merging into content worked?
		$this->assertEquals('123 some test ', $data['content']->getValue());
		//indexing of standard fields worked?
		$this->assertEquals('some test', $data['news_text_s']->getValue(),'news_text_s falsch');
		$this->assertEquals('some test', $data['news_text_t']->getValue(),'news_text_t falsch');
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
		$this->assertEquals('2010-01-30T15:00:00Z', $data['datetime_dt']->getValue(),'datetime falsch');
		//indexing of extra fields worked?
		$this->assertEquals('some test  with html markup ', $data['bodytext_s']->getValue(),'bodytext falsch');
		$this->assertEquals(123, $data['uid_i']->getValue());
		//merging into content worked?
		$this->assertEquals('123 some test  with html markup  ', $data['content']->getValue(),'content falsch');
		//indexing of standard fields worked?
		$this->assertEquals('some test  with html markup ', $data['news_text_s']->getValue(),'news_text_s falsch');
		$this->assertEquals('some test  with html markup ', $data['news_text_t']->getValue(),'news_text_t falsch');
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
		$this->assertEquals('2010-01-30T15:00:00Z', $data['datetime_dt']->getValue(),'datetime falsch');
		//indexing of extra fields worked?
		$this->assertEquals('some test <p>with html markup</p>', $data['bodytext_s']->getValue(),'bodytext falsch');
		$this->assertEquals(123, $data['uid_i']->getValue());
		//merging into content worked?
		$this->assertEquals('123 some test <p>with html markup</p> ', $data['content']->getValue(),'content falsch');
		//indexing of standard fields worked?
		$this->assertEquals('some test <p>with html markup</p>', $data['news_text_s']->getValue(),'news_text_s falsch');
		$this->assertEquals('some test <p>with html markup</p>', $data['news_text_t']->getValue(),'news_text_t falsch');
	}
	
	function test_prepareSearchDataDeleted() {
		$options = array();
		$options['indexedFields'] = 'uid,bodytext';
		
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_TtNewsNews');

		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType, 'tx_mksearch_model_IndexerFieldBase');
		$record = array('uid'=> 123, 'deleted' => 1);
		$indexer->prepareSearchData('tt_news', $record, $indexDoc, $options);
		$this->assertEquals(true, $indexDoc->getDeleted());

		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType, 'tx_mksearch_model_IndexerFieldBase');
		$record = array('uid'=> 123, 'deleted' => 0, 'hidden' => 1);
		$indexer->prepareSearchData('tt_news', $record, $indexDoc, $options);
		$this->assertEquals(true, $indexDoc->getDeleted());

		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType, 'tx_mksearch_model_engineSpecific_solr_IndexerField');
		$record = array('uid'=> 123, 'deleted' => 0, 'hidden' => 0, 'bodytext' => 'some test');
		$indexer->prepareSearchData('tt_news', $record, $indexDoc, $options);
		$this->assertEquals(false, $indexDoc->getDeleted());
		$data = $indexDoc->getData();
		//merging into content worked?
		$this->assertEquals('123 some test ', $data['content']->getValue());
		//no extra fields added?
		$this->assertFalse(array_key_exists('uid_i', $data),'uid_i Field is existent!');
		$this->assertFalse(array_key_exists('uid', $data),'uid Field is existent!');
		$this->assertFalse(array_key_exists('bodytext_s', $data),'bodytext_s Field is existent!');
		$this->assertFalse(array_key_exists('bodytext', $data),'bodytext Field is existent!');
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
		$this->assertEquals('2010-01-30T15:00:00Z', $data['datetime_dt']->getValue(),'datetime falsch');
		//indexing of standard fields worked?
		$this->assertEquals('some test  with html markup ', $data['news_text_s']->getValue(),'news_text_s falsch');
		$this->assertEquals('some test  with html markup ', $data['news_text_t']->getValue(),'news_text_t falsch');
		//merging into content ignored as nothing?
		$this->assertTrue(empty($data['content']),'content falsch');
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtNewsNews_testcase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtNewsNews_testcase.php']);
}

?>