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
		if (!tx_rnbase_util_Extensions::isLoaded('news')) {
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
		$model = $this->getNewsModel();

		$indexer = $this->getIndexerMock(
			$model->getRecord()
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
			'datetime_dt',
			tx_mksearch_util_Misc::getIsoDate(
				new \DateTime('@' . $GLOBALS['EXEC_TIME'])
			)
		);

		$this->assertIndexDocHasField(
			$indexDoc,
			'content',
			'the first news  html in body text  description'
		);

		$this->assertIndexDocHasField(
			$indexDoc,
			'news_text_s',
			'html in body text'
		);
		$this->assertIndexDocHasField(
			$indexDoc,
			'news_text_t',
			'html in body text'
		);

		$this->assertIndexDocHasField(
			$indexDoc,
			'abstract',
			'the first news'
		);

		$this->assertIndexDocHasField(
			$indexDoc,
			'keywords_ms',
			array('Tag1', 'Tag2')
		);
	}

	/**
	 * A model Mock containing news data
	 *
	 * @return tx_rnbase_model_base|PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getNewsModel()
	{
		return $this->getModel(
			array(
				'uid' => '5',
				'pid' => '7',
				'tstamp' => $GLOBALS['EXEC_TIME'],
				'datetime' => new \DateTime('@' . $GLOBALS['EXEC_TIME']),
				'title' => 'first news',
				'teaser' => 'the first news',
				'bodytext' => '<span>html in body text</span>',
				'description' => 'description',
				'tags' => array(
					$this->getModel(
						array(
							'uid' => '51',
							'title' => 'Tag1',
						)
					),
					$this->getModel(
						array(
							'uid' => '52',
							'title' => 'Tag2',
						)
					),
				)
			)
		);
	}

	/**
	 * Creates a Mock of the indexer object
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getIndexerMock()
	{
		$model = $this->getNewsModel();

		$indexer = $this->getMock(
			'tx_mksearch_indexer_TxNewsNews',
			array('createNewsModel')
		);

		($indexer
			->expects(self::once())
			->method('createNewsModel')
			->will(self::returnValue($model))
		);

		return $indexer;
	}
}

if ((
	defined('TYPO3_MODE') &&
	$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']
		['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TxNewsNews_testcase.php']
)) {
	include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']
		['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TxNewsNews_testcase.php'];
}
