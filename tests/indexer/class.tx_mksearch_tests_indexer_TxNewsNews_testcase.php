<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2017 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

tx_rnbase::load('tx_mksearch_tests_Testcase');
tx_rnbase::load('tx_rnbase_model_base');
tx_rnbase::load('tx_mksearch_indexer_TxNewsNews');

/**
 * Indexer for News from tx_news
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_tests
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_indexer_TxNewsNews_testcase
	extends tx_mksearch_tests_Testcase
{
	/**
	 * Set up testcase
	 *
	 * @return void
	 */
	protected function setUp()
	{
		if(!tx_rnbase_util_Extensions::isLoaded('news')) {
			$this->markTestSkipped('tx_news is not installed!');
		}
		parent::setUp();
	}

	/**
	 * Testet die indexData Methode.
	 *
	 * @return void
	 *
	 * @group unit
	 * @test
	 */
	public function testGetContentType()
	{
		$indexer = $this->getMock(
			'tx_mksearch_indexer_TxNewsNews',
			array('getDefaultTSConfig')
		);

		$this->assertEquals(
			array('tx_news', 'news'),
			$indexer->getContentType()
		);
	}

	/**
	 * Testet die indexData Methode.
	 *
	 * @return void
	 *
	 * @group unit
	 * @test
	 */
	public function testIndexData()
	{
		$model = $this->getModel(
			array(
				'uid' => '5',
				'pid' => '7',
				'tstamp' => $GLOBALS['EXEC_TIME'],
				'title' => 'first news',
				'teaser' => 'the first news',
				'bodytext' => '<span>html in body text</span>',
			)
		);

		$indexer = $this->getMock(
			'tx_mksearch_indexer_TxNewsNews',
			array('getDefaultTSConfig')
		);

		$indexDoc = $this->callInaccessibleMethod(
			array($indexer, 'indexData'),
			array(
				$model,
				'tx_news_domain_model_news',
				$model->getRecord(),
				$this->getIndexDocMock($indexer),
				array()
			)
		);

		$this->assertIndexDocHasField(
			$indexDoc,
			'content_ident_s',
			'tx_news.news'
		);

		$this->assertIndexDocHasField(
			$indexDoc,
			'title',
			'first news'
		);

		$this->assertIndexDocHasField(
			$indexDoc,
			'pid',
			'7'
		);

		$this->assertIndexDocHasField(
			$indexDoc,
			'tstamp',
			(string) $GLOBALS['EXEC_TIME']
		);

		$this->assertIndexDocHasField(
			$indexDoc,
			'content',
			'html in body text'
		);

		$this->assertIndexDocHasField(
			$indexDoc,
			'abstract',
			'the first news'
		);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TxNewsNews_testcase.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TxNewsNews_testcase.php']);
}
