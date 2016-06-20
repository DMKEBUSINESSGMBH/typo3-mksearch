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


tx_rnbase::load('tx_mksearch_tests_Testcase');

/**
 *
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_tests
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_util_Indexer_testcase
	extends tx_mksearch_tests_Testcase {

	/**
	 * @group unit
	 */
	public function testGetDateTimeWithTimestamp() {
		$dateTime = tx_mksearch_util_Indexer::getInstance()
			->getDateTime('@2');
		self::assertEquals('1970-01-01T00:00:02Z', $dateTime);
	}

	/**
	 * @group unit
	 */
	public function testIsOnIndexablePageReturnsTrueIfNoInOrExcludesSet() {
		$sourceRecord = array('pid' => 2);
		$options = array();

		$isOnIndexablePage = tx_mksearch_util_Indexer::getInstance()
			->isOnIndexablePage($sourceRecord, $options);

		self::assertTrue($isOnIndexablePage,'Seite nicht indizierbar');
	}

	/**
	 * @group unit
	 */
	public function testIsOnIndexablePageReturnsTrueIfPidIsInIncludePages() {
		$sourceRecord = array('pid' => 1);
		$options = array(
			'include.' => array(
				'pages' => 1
			)
		);

		$isOnIndexablePage = tx_mksearch_util_Indexer::getInstance()
			->isOnIndexablePage($sourceRecord, $options);

		self::assertTrue($isOnIndexablePage,'Seite nicht indizierbar');
	}

	/**
	 * @group unit
	 */
	public function testIsOnIndexablePageReturnsFalseIfPidIsNotInIncludePages() {
		$sourceRecord = array('pid' => 2);
		$options = array(
			'include.' => array(
				'pages' => 1
			)
		);

		$isOnIndexablePage = tx_mksearch_util_Indexer::getInstance()
			->isOnIndexablePage($sourceRecord, $options);

		self::assertFalse($isOnIndexablePage,'Seite indizierbar');
	}

	/**
	 * @group unit
	 */
	public function testIsOnIndexablePageReturnsFalseIfPidIsInExcludePageTrees() {
		$sourceRecord = array('pid' => 3);
		$options = array(
			'exclude.' => array(
				'pageTrees' => 1
			)
		);

		$rootline = array(
			2 => array('uid' => 3, 'pid' => 2),
			1 => array('uid' => 2, 'pid' => 1),
			0 => array('uid' => 1, 'pid' => 0),
		);
		$utilIndexer = $this->getMockClassForIsOnIndexablePageTests($rootline);

		$isOnIndexablePage = $utilIndexer->isOnIndexablePage($sourceRecord, $options);

		self::assertFalse($isOnIndexablePage,'Seite indizierbar');
	}

	/**
	 * @group unit
	 */
	public function testIsOnIndexablePageReturnsTrueIfPidIsInIncludePageTrees() {
		$sourceRecord = array('pid' => 3);
		$options = array(
			'include.' => array(
				'pageTrees' => 2
			)
		);

		$rootline = array(
			1 => array('uid' => 3, 'pid' => 2),
			0 => array('uid' => 2, 'pid' => 0),
		);
		$utilIndexer = $this->getMockClassForIsOnIndexablePageTests($rootline);

		$isOnIndexablePage = $utilIndexer->isOnIndexablePage($sourceRecord, $options);

		self::assertTrue($isOnIndexablePage,'Seite nicht indizierbar');
	}

	/**
	 * @group unit
	 */
	public function testIsOnIndexablePageReturnsFalseIfPidIsNotInIncludePageTrees() {
		$sourceRecord = array('pid' => 3);
		$options = array(
			'include.' => array(
				'pageTrees' => 1
			)
		);

		$rootline = array(
			1 => array('uid' => 3, 'pid' => 2),
			0 => array('uid' => 2, 'pid' => 0),
		);
		$utilIndexer = $this->getMockClassForIsOnIndexablePageTests($rootline);

		$isOnIndexablePage = $utilIndexer->isOnIndexablePage($sourceRecord, $options);

		self::assertFalse($isOnIndexablePage,'Seite indizierbar');
	}

	/**
	 * @group unit
	 */
	public function testIsOnIndexablePageReturnsFalseIfPidIsInExcludePages() {
		$sourceRecord = array('pid' => 1);
		$options = array(
			'exclude.' => array(
				'pages' => 1
			)
		);

		$isOnIndexablePage = tx_mksearch_util_Indexer::getInstance()
			->isOnIndexablePage($sourceRecord, $options);

		self::assertFalse($isOnIndexablePage,'Seite indizierbar');
	}

	/**
	 * @group unit
	 */
	public function testIsOnIndexablePageReturnsTrueIfPidIsNotInExcludePages() {
		$sourceRecord = array('pid' => 2);
		$options = array(
			'exclude.' => array(
				'pages' => 1
			)
		);

		$isOnIndexablePage = tx_mksearch_util_Indexer::getInstance()
			->isOnIndexablePage($sourceRecord, $options);

		self::assertTrue($isOnIndexablePage,'Seite nicht indizierbar');
	}

	/**
	 * @group unit
	 */
	public function testIsOnIndexablePageReturnsFalseIfPidIsIncludePageTreesButAlsoInExcludePages() {
		$sourceRecord = array('pid' => 3);
		$options = array(
			'include.' => array(
				'pageTrees' => 1
			),
			'exclude.' => array(
				'pages' => 3
			)
		);

		$rootline = array(
			2 => array('uid' => 3, 'pid' => 2),
			1 => array('uid' => 2, 'pid' => 1),
			0 => array('uid' => 1, 'pid' => 0),
		);
		$utilIndexer = $this->getMockClassForIsOnIndexablePageTests($rootline);

		$isOnIndexablePage = $utilIndexer->isOnIndexablePage($sourceRecord, $options);

		self::assertFalse($isOnIndexablePage,'Seite indizierbar');
	}

	/**
	 * @group unit
	 */
	public function testIsOnIndexablePageReturnsTrueIfPidIsIncludePageTreesAndNotExcludePages() {
		$sourceRecord = array('pid' => 3);
		$options = array(
			'include.' => array(
				'pageTrees' => 1
			),
			'exclude.' => array(
				'pages' => 2
			)
		);

		$rootline = array(
			2 => array('uid' => 3, 'pid' => 2),
			1 => array('uid' => 2, 'pid' => 1),
			0 => array('uid' => 1, 'pid' => 0),
		);
		$utilIndexer = $this->getMockClassForIsOnIndexablePageTests($rootline);

		$isOnIndexablePage = $utilIndexer->isOnIndexablePage($sourceRecord, $options);

		self::assertTrue($isOnIndexablePage,'Seite indizierbar');
	}

	/**
	 * @group unit
	 */
	public function testIsOnIndexablePageReturnsFalseIfPidIsIncludePageTreesButAnExcludePageTreesIndexIsCloserToThePid() {
		$sourceRecord = array('pid' => 3);
		$options = array(
			'include.' => array(
				'pageTrees' => 1
			),
			'exclude.' => array(
				'pageTrees' => 2
			)
		);

		$rootline = array(
			2 => array('uid' => 3, 'pid' => 2),
			1 => array('uid' => 2, 'pid' => 1),
			0 => array('uid' => 1, 'pid' => 0),
		);
		$utilIndexer = $this->getMockClassForIsOnIndexablePageTests($rootline);

		$isOnIndexablePage = $utilIndexer->isOnIndexablePage($sourceRecord, $options);

		self::assertFalse($isOnIndexablePage,'Seite indizierbar');
	}


	/**
	 *
	 */
	public function testIndexModelByMapping() {
		$indexDoc = tx_rnbase::makeInstance(
			'tx_mksearch_model_IndexerDocumentBase', '', ''
		);
		$indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		$model = tx_rnbase::makeInstance(
			'tx_rnbase_model_base', array('recordField' => 123)
		);

		tx_mksearch_util_Indexer::getInstance()->indexModelByMapping(
			$model, array('recordField' => 'documentField'), $indexDoc
		);
		$docData = $indexDoc->getData();

		self::assertEquals(
			123, $docData['documentField']->getValue(), 'model falsch indiziert'
		);
	}

	/**
	 *
	 */
	public function testIndexModelByMappingDoesNotIndexHiddenModelsByDefault() {
		$indexDoc = tx_rnbase::makeInstance(
			'tx_mksearch_model_IndexerDocumentBase', '', ''
		);
		$indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		$model = tx_rnbase::makeInstance(
			'tx_rnbase_model_base', array('recordField' => 123, 'hidden' => 1)
		);

		tx_mksearch_util_Indexer::getInstance()->indexModelByMapping(
			$model, array('recordField' => 'documentField'), $indexDoc
		);
		$docData = $indexDoc->getData();

		self::assertFalse(
			isset($docData['documentField']), 'model doch indiziert'
		);
	}

	/**
	 *
	 */
	public function testIndexModelByMappingIndexesHiddenModelsIfSet() {
		$indexDoc = tx_rnbase::makeInstance(
			'tx_mksearch_model_IndexerDocumentBase', '', ''
		);
		$indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		$model = tx_rnbase::makeInstance(
			'tx_rnbase_model_base', array('recordField' => 123, 'hidden' => 1)
		);

		tx_mksearch_util_Indexer::getInstance()->indexModelByMapping(
			$model, array('recordField' => 'documentField'), $indexDoc, '',
			array(), FALSE
		);
		$docData = $indexDoc->getData();

		self::assertEquals(
			123, $docData['documentField']->getValue(), 'model falsch indiziert'
		);
	}

	/**
	 *
	 */
	public function testIndexModelByMappingWithPrefix() {
		$indexDoc = tx_rnbase::makeInstance(
			'tx_mksearch_model_IndexerDocumentBase', '', ''
		);
		$indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		$model = tx_rnbase::makeInstance(
			'tx_rnbase_model_base', array('recordField' => 123)
		);

		tx_mksearch_util_Indexer::getInstance()->indexModelByMapping(
			$model, array('recordField' => 'documentField'), $indexDoc, 'test_'
		);
		$docData = $indexDoc->getData();

		self::assertEquals(
			123, $docData['test_documentField']->getValue(), 'model falsch indiziert'
		);
	}

	/**
	 *
	 */
	public function testIndexModelByMappingMapsNotEmptyFields() {
		$indexDoc = tx_rnbase::makeInstance(
			'tx_mksearch_model_IndexerDocumentBase', '', ''
		);
		$indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		$model = tx_rnbase::makeInstance(
			'tx_rnbase_model_base', array('recordField' => '')
		);

		tx_mksearch_util_Indexer::getInstance()->indexModelByMapping(
			$model, array('recordField' => 'documentField'), $indexDoc
		);
		$docData = $indexDoc->getData();

		self::assertFalse(
			isset($docData['documentField']), 'model doch indiziert'
		);
	}

	/**
	 *
	 */
	public function testIndexModelByMappingMapsEmptyFieldsIfKeepEmptyOption() {
		$indexDoc = tx_rnbase::makeInstance(
				'tx_mksearch_model_IndexerDocumentBase', '', ''
		);
		$indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		$model = tx_rnbase::makeInstance(
				'tx_rnbase_model_base', array('recordField' => '')
		);

		tx_mksearch_util_Indexer::getInstance()->indexModelByMapping(
			$model, array('recordField' => 'documentField'), $indexDoc, '',
			array('keepEmpty' => 1)
		);
		$docData = $indexDoc->getData();

		self::assertEquals(
			'', $docData['documentField']->getValue(), 'model falsch indiziert'
		);
	}

	/**
	 *
	 */
	public function testIndexArrayOfModelsByMapping() {
		$indexDoc = tx_rnbase::makeInstance(
			'tx_mksearch_model_IndexerDocumentBase', '', ''
		);
		$indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		$models = array(
			tx_rnbase::makeInstance(
				'tx_rnbase_model_base', array('recordField' => 123)
			),
			tx_rnbase::makeInstance(
				'tx_rnbase_model_base', array('recordField' => 456)
			)
		);

		tx_mksearch_util_Indexer::getInstance()->indexArrayOfModelsByMapping(
			$models, array('recordField' => 'documentField'), $indexDoc
		);
		$docData = $indexDoc->getData();

		self::assertEquals(
			array(123, 456), $docData['documentField']->getValue(),
			'models falsch indiziert'
		);
	}

	/**
	 *
	 */
	public function testIndexArrayOfModelsByMappingWithFieldConversion() {
		$indexDoc = tx_rnbase::makeInstance(
				'tx_mksearch_model_IndexerDocumentBase', '', ''
		);
		$models = array(
				tx_rnbase::makeInstance(
						'tx_rnbase_model_base', array('recordField' => 1440668593)
				),
				tx_rnbase::makeInstance(
						'tx_rnbase_model_base', array('recordField' => 1439034300)
				)
		);
		$options = array(
				'fieldsConversion.' => array('documentField.' => array(
						'unix2isodate' => 1
				))
		);

		tx_mksearch_util_Indexer::getInstance()->indexArrayOfModelsByMapping(
				$models, array('recordField' => 'documentField'), $indexDoc, '', $options
		);
		$docData = $indexDoc->getData();

		self::assertEquals(
				array('2015-08-27T09:43:13Z', '2015-08-08T11:45:00Z'), $docData['documentField']->getValue(),
				'models falsch indiziert'
		);
	}

	public function testIndexArrayOfModelsByMappingWithMoreFields() {
		$indexDoc = tx_rnbase::makeInstance(
				'tx_mksearch_model_IndexerDocumentBase', '', ''
		);
		$models = array(
				tx_rnbase::makeInstance(
						'tx_rnbase_model_base', array('recordField' => 123)
				),
				tx_rnbase::makeInstance(
						'tx_rnbase_model_base', array('recordField' => 456)
				)
		);

		tx_mksearch_util_Indexer::getInstance()->indexArrayOfModelsByMapping(
				$models, array('recordField' => 'documentField,documentField2'), $indexDoc
		);
		$docData = $indexDoc->getData();

		self::assertEquals(
				array(123, 456), $docData['documentField']->getValue(),
				'models falsch indiziert'
		);
		self::assertEquals(
				array(123, 456), $docData['documentField2']->getValue(),
				'second field is wrong'
		);
	}

	/**
	 *
	 */
	public function testIndexArrayOfModelsByMappingDoesNotIndexHiddenModelsByDefault() {
		$indexDoc = tx_rnbase::makeInstance(
			'tx_mksearch_model_IndexerDocumentBase', '', ''
		);
		$indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		$models = array(
			tx_rnbase::makeInstance(
				'tx_rnbase_model_base', array('recordField' => 123)
			),
			tx_rnbase::makeInstance(
				'tx_rnbase_model_base', array('recordField' => 456, 'hidden' => 1)
			)
		);

		tx_mksearch_util_Indexer::getInstance()->indexArrayOfModelsByMapping(
			$models, array('recordField' => 'documentField'), $indexDoc
		);
		$docData = $indexDoc->getData();

		self::assertEquals(
			array(123), $docData['documentField']->getValue(),
			'models falsch indiziert'
		);
	}

	/**
	 *
	 */
	public function testIndexArrayOfModelsByMappingIndexesHiddenModelsIfSet() {
		$indexDoc = tx_rnbase::makeInstance(
			'tx_mksearch_model_IndexerDocumentBase', '', ''
		);
		$indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		$models = array(
			tx_rnbase::makeInstance(
				'tx_rnbase_model_base', array('recordField' => 123)
			),
			tx_rnbase::makeInstance(
				'tx_rnbase_model_base', array('recordField' => 456, 'hidden' => 1)
			)
		);

		tx_mksearch_util_Indexer::getInstance()->indexArrayOfModelsByMapping(
			$models, array('recordField' => 'documentField'), $indexDoc, '',
			array(), FALSE
		);
		$docData = $indexDoc->getData();

		self::assertEquals(
			array(123, 456), $docData['documentField']->getValue(),
			'models falsch indiziert'
		);
	}

	/**
	 *
	 */
	public function testIndexArrayOfModelsByMappingWithPrefix() {
		$indexDoc = tx_rnbase::makeInstance(
			'tx_mksearch_model_IndexerDocumentBase', '', ''
		);
		$indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		$models = array(
			tx_rnbase::makeInstance(
				'tx_rnbase_model_base', array('recordField' => 123)
			),
			tx_rnbase::makeInstance(
				'tx_rnbase_model_base', array('recordField' => 456)
			)
		);

		tx_mksearch_util_Indexer::getInstance()->indexArrayOfModelsByMapping(
			$models, array('recordField' => 'documentField'), $indexDoc, 'test_'
		);
		$docData = $indexDoc->getData();

		self::assertEquals(
			array(123, 456), $docData['test_documentField']->getValue(), 'model falsch indiziert'
		);
	}

	/**
	 *
	 */
	public function testIndexArrayOfModelsByMappingMapsNotEmptyFields() {
		$indexDoc = tx_rnbase::makeInstance(
			'tx_mksearch_model_IndexerDocumentBase', '', ''
		);
		$indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		$models = array(
			tx_rnbase::makeInstance(
				'tx_rnbase_model_base', array('recordField' => '')
			),
			tx_rnbase::makeInstance(
				'tx_rnbase_model_base', array('recordField' => '')
			)
		);

		tx_mksearch_util_Indexer::getInstance()->indexArrayOfModelsByMapping(
			$models, array('recordField' => 'documentField'), $indexDoc
		);
		$docData = $indexDoc->getData();

		self::assertFalse(
			isset($docData['documentField']), 'model doch indiziert'
		);
	}

	/**
	 * @param array $rootline
	 * @return tx_mksearch_util_Indexer
	 */
	protected function getMockClassForIsOnIndexablePageTests(array $rootline) {
		$utilIndexer = $this->getMock('tx_mksearch_util_Indexer',array('getRootlineByPid'));

		$utilIndexer->expects($this->any())
			->method('getRootlineByPid')
			->with(3)
			->will($this->returnValue($rootline));

		return $utilIndexer;
	}

	/**
	 * @group unit
	 */
	public function testStopIndexingReturnsFalseIfNoSysLanguageUidField() {
		/* @var $utility tx_mksearch_util_Indexer */
		$utility = tx_rnbase::makeInstance('tx_mksearch_util_Indexer');
		$tableName = 'tx_mksearch_queue';
		$sourceRecord = array('some_record');
		$options = array('some_options');
		$indexDoc = tx_rnbase::makeInstance(
			'tx_mksearch_model_IndexerDocumentBase', '', ''
		);

		self::assertFalse(
			$utility->stopIndexing($tableName, $sourceRecord, $indexDoc, $options)
		);
	}

	/**
	 * @group unit
	 */
	public function testStopIndexingReturnsFalseIfSysLanguageUidFieldMatchesLanguageFromOptions() {
		/* @var $utility tx_mksearch_util_Indexer */
		$utility = tx_rnbase::makeInstance('tx_mksearch_util_Indexer');
		$tableName = 'tt_content';
		$sourceRecord = array('sys_language_uid' => 123);
		$options = array('lang' => 123);
		$indexDoc = tx_rnbase::makeInstance(
			'tx_mksearch_model_IndexerDocumentBase', '', ''
		);

		self::assertFalse(
			$utility->stopIndexing($tableName, $sourceRecord, $indexDoc, $options)
		);
	}

	/**
	 * @group unit
	 */
	public function testStopIndexingReturnsTrueIfSysLanguageUidFieldMatchesNotLanguageFromOptions() {
		/* @var $utility tx_mksearch_util_Indexer */
		$utility = tx_rnbase::makeInstance('tx_mksearch_util_Indexer');
		$tableName = 'tt_content';
		$sourceRecord = array('sys_language_uid' => 123);
		$options = array('lang' => 456);
		$indexDoc = tx_rnbase::makeInstance(
			'tx_mksearch_model_IndexerDocumentBase', '', ''
		);

		self::assertTrue(
			$utility->stopIndexing($tableName, $sourceRecord, $indexDoc, $options)
		);
	}

	/**
	 * @group unit
	 */
	public function testAddModelsToIndex() {
		$models = array(
			tx_rnbase::makeInstance('tx_rnbase_model_base', array('uid' => 1)),
			tx_rnbase::makeInstance('tx_rnbase_model_base', array('uid' => 2))
		);
		$tableName = 'test_table';
		$prefer = 'prefer_me';
		$resolver = 'test_resolver';
		$data = array('test data');
		$options = array('test options');

		$utility = $this->getMock('tx_mksearch_util_Indexer', array('addModelToIndex'));

		$utility->expects(self::at(0))
			->method('addModelToIndex')
			->with($models[0], $tableName, $prefer, $resolver, $data, $options);

		$utility->expects(self::at(1))
			->method('addModelToIndex')
			->with($models[1], $tableName, $prefer, $resolver, $data, $options);

		$utility->addModelsToIndex($models, $tableName, $prefer, $resolver, $data, $options);
	}

	/**
	 * @group unit
	 */
	public function testGetInternalIndexService() {
		self::assertInstanceOf(
			'tx_mksearch_service_internal_Index',
			$this->callInaccessibleMethod(tx_mksearch_util_Indexer::getInstance(), 'getInternalIndexService')
		);
	}

	/**
	 * @group unit
	 */
	public function testAddModelToIndexWithInvalidModel() {
		$utility = $this->getMock('tx_mksearch_util_Indexer', array('getInternalIndexService'));
		$utility->expects(self::never())
			->method('getInternalIndexService');

		$utility->addModelToIndex(tx_rnbase::makeInstance('tx_rnbase_model_base', array()), 'test_table');
	}

	/**
	 * @group unit
	 */
	public function testAddModelToIndexWithValidModel() {
		$model = tx_rnbase::makeInstance('tx_rnbase_model_base', array('uid' => 123));
		$tableName = 'test_table';
		$prefer = 'prefer_me';
		$resolver = 'test_resolver';
		$data = array('test data');
		$options = array('test options');

		$internalIndexService = $this->getMock('tx_mksearch_service_internal_Index', array('addRecordToIndex'));
		$internalIndexService->expects(self::once())
			->method('addRecordToIndex')
			->with($tableName, 123, $prefer, $resolver, $data, $options);

		$utility = $this->getMock('tx_mksearch_util_Indexer', array('getInternalIndexService'));
		$utility->expects(self::once())
			->method('getInternalIndexService')
			->will(self::returnValue($internalIndexService));

		$utility->addModelToIndex($model, $tableName, $prefer, $resolver, $data, $options);
	}
}