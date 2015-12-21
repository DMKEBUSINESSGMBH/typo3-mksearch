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
tx_rnbase::load('tx_mksearch_tests_fixtures_indexer_Dummy');
tx_rnbase::load('tx_mksearch_service_indexer_core_Config');

/**
 * Wir müssen in diesem Fall mit der DB testen da wir die pages
 * Tabelle benötigen
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_tests
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_indexer_Base_testcase
	extends tx_mksearch_tests_Testcase {

	/**
	 * Check if the uid is set correct
	 */
	public function testGetPrimarKey() {
		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options = array();

		$aRawData = array('uid' => 1, 'test_field_1' => 'test value 1');

		$oIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);

		$aPrimaryKey = $oIndexDoc->getPrimaryKey();
		self::assertEquals('mksearch',$aPrimaryKey['extKey']->getValue(),'Es wurde nicht der richtige extKey gesetzt!');
		self::assertEquals('dummy',$aPrimaryKey['contentType']->getValue(),'Es wurde nicht der richtige contentType gesetzt!');
		self::assertEquals(1,$aPrimaryKey['uid']->getValue(),'Es wurde nicht die richtige Uid gesetzt!');
	}

	public function testGetPidListPreparesTsfe() {
		$GLOBALS['TSFE'] = null;

		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		$indexer->callGetPidList();

		self::assertNotNull($GLOBALS['TSFE'],'TSFE wurde nicht geladen!');
	}

	/**
	 *
	 */
	public function testCheckOptionsIncludeDeletesDocs() {
		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
			'include.'=>array('categories.' => array(3))
		);
		$options2 = array(
			'include.'=>array('categories' => 3)
		);

		$aRawData = array('uid' => 1, 'pid' => 1);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		self::assertNull($aIndexDoc,'Das Element wurde indiziert! Option 1');

		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options2);
		self::assertNull($aIndexDoc,'Das Element wurde indiziert! Option 2');
	}

	/**
	 *
	 */
	public function testCheckOptionsIncludeDoesNotDeleteDocs() {
		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
			'include.'=>array('categories.' => array(2))
		);
		$options2 = array(
			'include.'=>array('categories' => 2)
		);
		$aRawData = array('uid' => 1, 'pid' => 2);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		self::assertNotNull($aIndexDoc,'Das Element wurde nicht indiziert! Option 1');

		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options2);
		self::assertNotNull($aIndexDoc,'Das Element wurde nicht indiziert! Option 2');
	}

	/**
	 *
	 */
	public function testCheckOptionsExcludeDoesNotDeleteDocs() {
		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
			'exclude.'=>array('categories.' => array(3))
		);
		$options2 = array(
			'exclude.'=>array('categories' => 3)
		);

		$aRawData = array('uid' => 1, 'pid' => 1);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		self::assertNotNull($aIndexDoc,'Das Element wurde nicht indiziert! Element 1 Option 1');

		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options2);
		self::assertNotNull($aIndexDoc,'Das Element wurde nicht indiziert! Element 1 Option 2');
	}

/**
	 *
	 */
	public function testCheckOptionsExcludeDeletesDocs() {
		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
			'exclude.'=>array('categories.' => array(2))
		);
		$options2 = array(
			'exclude.'=>array('categories' => 2)
		);
		$aRawData = array('uid' => 1, 'pid' => 2);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		self::assertNull($aIndexDoc,'Das Element wurde doch indiziert! Element 2 Option 1');

		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options2);
		self::assertNull($aIndexDoc,'Das Element wurde doch indiziert! Element 2 Option 2');
	}

	/**
	*
	*/
	public function testCheckOptionsIncludeReturnsCorrectDefaultValueWithEmptyCategory() {
		$indexer = $this->getMock('tx_mksearch_tests_fixtures_indexer_Dummy',array('getTestCategories'));

		$indexer->expects($this->once())
			->method('getTestCategories')
			->will($this->returnValue(array()));

		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
				'include.'=>array('categories.' => array(3))
		);

		$aRawData = array('uid' => 1, 'pid' => 1);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		self::assertNull($aIndexDoc,'Das Element wurde indiziert! Option 1');
	}

	/**
	 *
	 */
	public function testCheckOptionsExcludeReturnsCorrectDefaultValueWithEmptyCategory() {
		$indexer = $this->getMock('tx_mksearch_tests_fixtures_indexer_Dummy',array('getTestCategories'));

		$indexer->expects($this->once())
			->method('getTestCategories')
			->will($this->returnValue(array()));

		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
				'exclude.'=>array('categories.' => array(3))
		);

		$aRawData = array('uid' => 1, 'pid' => 1);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
		self::assertNotNull($aIndexDoc,'Das Element wurde nicht indiziert! Option 1');
	}

	/**
	 *
	 */
	public function testIndexEnableColumns() {
		$this->setTcaEnableColumnsForMyTestTable1();

		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		list($extKey, $cType) = $indexer->getContentType();

		$aRawData = array('uid' => 1, 'hidden' => 0,'startdate' => 2,'enddate' => 3,'fe_groups' => 4);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$indexDocData = $indexer->prepareSearchData('mytesttable_1', $aRawData, $indexDoc, array())->getData();

		//empty values are ignored
		self::assertEquals('1970-01-01T00:00:02Z', $indexDocData['starttime_dt']->getValue());
		self::assertEquals('1970-01-01T00:00:03Z', $indexDocData['endtime_dt']->getValue());
		self::assertEquals(array(0=>4), $indexDocData['fe_group_mi']->getValue());
	}

	/**
	 *
	 */
	public function testIndexEnableColumnsIfTableHasNoEnableColumns() {
		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		list($extKey, $cType) = $indexer->getContentType();

		$aRawData = array('uid' => 1);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$indexDocData = $indexer->prepareSearchData('doesn_t_matter', $aRawData, $indexDoc, array())->getData();

		self::assertEquals(
			1, count($indexDocData), 'es sollte nur 1 Feld (content_ident_s) indiziert werden.'
		);
		self::assertArrayHasKey(
			'content_ident_s', $indexDocData, 'content_ident_s nicht enthalten.'
		);
	}

	/**
	 *
	 */
	public function testIndexEnableColumnsWithEmptyStarttime() {
		$this->setTcaEnableColumnsForMyTestTable1();

		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		list($extKey, $cType) = $indexer->getContentType();

		$aRawData = array('uid' => 1, 'hidden' => 0,'startdate' => 0,'enddate' => 3,'fe_groups' => 4);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$indexDocData = $indexer->prepareSearchData('mytesttable_1', $aRawData, $indexDoc, array())->getData();

		//empty values are ignored
		self::assertFalse(isset($indexDocData['starttime_dt']));
		self::assertEquals('1970-01-01T00:00:03Z', $indexDocData['endtime_dt']->getValue());
		self::assertEquals(array(0=>4), $indexDocData['fe_group_mi']->getValue());
	}

	/**
	 *
	 */
	public function testIndexEnableColumnsWithSeveralFeGroups() {
		$this->setTcaEnableColumnsForMyTestTable1();

		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		list($extKey, $cType) = $indexer->getContentType();

		$aRawData = array('uid' => 1, 'hidden' => 0,'startdate' => 0,'enddate' => 3,'fe_groups' => '4,5');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$indexDocData = $indexer->prepareSearchData('mytesttable_1', $aRawData, $indexDoc, array())->getData();

		//empty values are ignored
		self::assertFalse(isset($indexDocData['starttime_dt']));
		self::assertEquals('1970-01-01T00:00:03Z', $indexDocData['endtime_dt']->getValue());
		self::assertEquals(array(0=>4,1=>5), $indexDocData['fe_group_mi']->getValue());
	}

	/**
	 *
	 */
	public function testIndexEnableColumnsDoesNotChangeRecordInPassedModel() {
		$this->setTcaEnableColumnsForMyTestTable1();

		$indexer =tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
		list($extKey, $cType) = $indexer->getContentType();

		$record = array('uid' => 1, 'startdate' => 2);
		$model = tx_rnbase::makeInstance('tx_rnbase_model_base', $record);
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);

		$this->callInaccessibleMethod($indexer, 'indexEnableColumns', $model, 'mytesttable_1', $indexDoc);

		self::assertSame(2, $model->record['startdate']);
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

		$this->callInaccessibleMethod(
			$indexer, 'indexModelByMapping',
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

		$this->callInaccessibleMethod(
			$indexer, 'indexModelByMapping',
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

		$this->callInaccessibleMethod(
			$indexer, 'indexModelByMapping',
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

		$this->callInaccessibleMethod(
			$indexer, 'indexModelByMapping',
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

		$this->callInaccessibleMethod(
			$indexer, 'indexModelByMapping',
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

		$this->callInaccessibleMethod(
			$indexer, 'indexModelByMapping',
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

		$this->callInaccessibleMethod(
			$indexer, 'indexArrayOfModelsByMapping',
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

		$this->callInaccessibleMethod(
			$indexer, 'indexArrayOfModelsByMapping',
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

		$this->callInaccessibleMethod(
			$indexer, 'indexArrayOfModelsByMapping',
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

		$this->callInaccessibleMethod(
			$indexer, 'indexArrayOfModelsByMapping',
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

		$this->callInaccessibleMethod(
			$indexer, 'indexArrayOfModelsByMapping',
			$models, array('recordField' => 'documentField'), $indexDoc
		);
		$docData = $indexDoc->getData();

		self::assertFalse(
			isset($docData['documentField']), 'model doch indiziert'
		);
	}

	private function setTcaEnableColumnsForMyTestTable1() {
		global $TCA;
		$TCA['mytesttable_1']['ctrl']['enablecolumns'] = array(
			'starttime' => 'startdate',
			'endtime' => 'enddate',
			'fe_group' => 'fe_groups',
		);
	}

	/**
	 * @group unit
	 */
	public function testGetCoreConfigUtility() {
		self::assertEquals(
			'tx_mksearch_service_indexer_core_Config',
			$this->callInaccessibleMethod(
				tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy'),
				'getCoreConfigUtility'
			)
		);
	}

	/**
	 * @group unit
	 */
	public function testHasDocToBeDeletedCallsGetRootlineCorrect() {
		$coreConfigUtility = $this->getMockClass(
			'tx_mksearch_service_indexer_core_Config', array('getRootline')
		);
		$coreConfigUtility::staticExpects($this->once())
			->method('getRootline')
			->with(123)
			->will($this->returnValue(array()));

		$indexer = $this->getMock(
			'tx_mksearch_tests_fixtures_indexer_Dummy', array('getCoreConfigUtility')
		);
		$indexer->expects($this->once())
			->method('getCoreConfigUtility')
			->will($this->returnValue($coreConfigUtility));

		$indexDoc = tx_rnbase::makeInstance(
			'tx_mksearch_model_IndexerDocumentBase', '', ''
		);
		$model = tx_rnbase::makeInstance(
			'tx_rnbase_model_base', array('pid' => 123)
		);
		$this->callInaccessibleMethod($indexer, 'hasDocToBeDeleted', $model, $indexDoc);
	}

	/**
	 * @group unit
	 */
	public function testHasDocToBeDeletedReturnsTrueIfOnePageInRootlineIsHidden() {
		$coreConfigUtility = $this->getMockClass(
			'tx_mksearch_service_indexer_core_Config', array('getRootline')
		);
		$coreConfigUtility::staticExpects($this->once())
			->method('getRootline')
			->will($this->returnValue(array(
				0 => array('uid' => 1, 'hidden' => 1),
				1 => array('uid' => 2, 'hidden' => 0)
			)));

		$indexer = $this->getMock(
			'tx_mksearch_tests_fixtures_indexer_Dummy', array('getCoreConfigUtility')
		);
		$indexer->expects($this->once())
			->method('getCoreConfigUtility')
			->will($this->returnValue($coreConfigUtility));

		$indexDoc = tx_rnbase::makeInstance(
			'tx_mksearch_model_IndexerDocumentBase', '', ''
		);
		$model = tx_rnbase::makeInstance(
			'tx_rnbase_model_base', array('pid' => 123)
		);

		self::assertTrue(
			$this->callInaccessibleMethod($indexer, 'hasDocToBeDeleted', $model, $indexDoc)
		);
	}

	/**
	 * @group unit
	 */
	public function testHasDocToBeDeletedReturnsTrueIfOnePageInRootlineIsBackendUserSection() {
		$coreConfigUtility = $this->getMockClass(
			'tx_mksearch_service_indexer_core_Config', array('getRootline')
		);
		$coreConfigUtility::staticExpects($this->once())
			->method('getRootline')
			->will($this->returnValue(array(
				0 => array('uid' => 1, 'doktype' => 6),
				1 => array('uid' => 2, 'doktype' => 1)
			)));

		$indexer = $this->getMock(
			'tx_mksearch_tests_fixtures_indexer_Dummy', array('getCoreConfigUtility')
		);
		$indexer->expects($this->once())
			->method('getCoreConfigUtility')
			->will($this->returnValue($coreConfigUtility));

		$indexDoc = tx_rnbase::makeInstance(
			'tx_mksearch_model_IndexerDocumentBase', '', ''
		);
		$model = tx_rnbase::makeInstance(
			'tx_rnbase_model_base', array('pid' => 123)
		);

		self::assertTrue(
			$this->callInaccessibleMethod($indexer, 'hasDocToBeDeleted', $model, $indexDoc)
		);
	}

	/**
	 * @group unit
	 */
	public function testHasDocToBeDeletedReturnsFalseIfAllPagesInRootlineAreOkay() {
		$coreConfigUtility = $this->getMockClass(
			'tx_mksearch_service_indexer_core_Config', array('getRootline')
		);
		$coreConfigUtility::staticExpects($this->once())
			->method('getRootline')
			->will($this->returnValue(array(
				0 => array('uid' => 1, 'doktype' => 2),
				1 => array('uid' => 2, 'doktype' => 1)
			)));

		$indexer = $this->getMock(
			'tx_mksearch_tests_fixtures_indexer_Dummy', array('getCoreConfigUtility')
		);
		$indexer->expects($this->once())
			->method('getCoreConfigUtility')
			->will($this->returnValue($coreConfigUtility));

		$indexDoc = tx_rnbase::makeInstance(
			'tx_mksearch_model_IndexerDocumentBase', '', ''
		);
		$model = tx_rnbase::makeInstance(
			'tx_rnbase_model_base', array('pid' => 123)
		);

		self::assertFalse(
			$this->callInaccessibleMethod($indexer, 'hasDocToBeDeleted', $model, $indexDoc)
		);
	}

	/**
	 * @group unit
	 */
	public function testGetIndexerUtility() {
		$indexer = $this->getMock(
			'tx_mksearch_tests_fixtures_indexer_Dummy', array('getCoreConfigUtility')
		);

		self::assertInstanceOf(
			'tx_mksearch_util_Indexer',
			$this->callInaccessibleMethod(
				$indexer, 'getIndexerUtility'
			)
		);
	}

	/**
	 * @group unit
	 */
	public function testStopIndexingCallsIndexerUtility() {
		$indexer = $this->getMockForAbstractClass(
			'tx_mksearch_indexer_Base',
			array(),
			'',
			FALSE,
			FALSE,
			FALSE,
			array(
				'indexData', 'getIndexerUtility'
			)
		);
		$indexerUtility = $this->getMock(
			'tx_mksearch_util_Indexer', array('stopIndexing')
		);

		$tableName = 'some_table';
		$sourceRecord = array('some_record');
		$options = array('some_options');
		$indexDoc = tx_rnbase::makeInstance(
			'tx_mksearch_model_IndexerDocumentBase', '', ''
		);
		$indexerUtility->expects($this->once())
			->method('stopIndexing')
			->with($tableName, $sourceRecord, $indexDoc, $options)
			->will($this->returnValue('return'));

		$indexer->expects($this->once())
			->method('getIndexerUtility')
			->will($this->returnValue($indexerUtility));

		self::assertEquals(
			'return',
			$this->callInaccessibleMethod(
				$indexer, 'stopIndexing', $tableName, $sourceRecord, $indexDoc, $options
			)
		);
	}


	/**
	 * @group unit
	 */
	public function testIndexSiteRootPageIndexesRootPageCorrectly() {
		$coreConfigUtility = $this->getMockClass(
			'tx_mksearch_service_indexer_core_Config', array('getSiteRootPage')
		);
		$coreConfigUtility::staticExpects($this->once())
			->method('getSiteRootPage')
			->with(123)
			->will($this->returnValue(array('uid' => 3)));

		$options = array('indexSiteRootPage' => 1);
		$indexer = $this->getMock(
			'tx_mksearch_tests_fixtures_indexer_Dummy', array('getCoreConfigUtility', 'shouldIndexSiteRootPage')
		);
		$indexer->expects($this->once())
			->method('getCoreConfigUtility')
			->will($this->returnValue($coreConfigUtility));

		$indexer->expects($this->once())
			->method('shouldIndexSiteRootPage')
			->with($options)
			->will($this->returnValue(TRUE));

		$indexDoc = $this->getMock(
			'tx_mksearch_model_IndexerDocumentBase', array('addField'), array('', '')
		);
		$model = tx_rnbase::makeInstance(
			'tx_rnbase_model_base', array('pid' => 123)
		);
		$indexDoc->expects($this->once())
			->method('addField')
			->with('siteRootPage', 3);

		$this->callInaccessibleMethod($indexer, 'indexSiteRootPage', $model, 'tt_content', $indexDoc, $options);
	}
	/**
	 * @group unit
	 */
	public function testIndexSiteRootPageDoesntIndexSiteRootPageWhenConfigMissing() {
		$options = array('indexSiteRootPage' => 0);
		$indexer = $this->getMock(
			'tx_mksearch_tests_fixtures_indexer_Dummy', array('getCoreConfigUtility', 'shouldIndexSiteRootPage')
		);
		$indexer->expects($this->never())
			->method('getCoreConfigUtility');

		$indexer->expects($this->once())
			->method('shouldIndexSiteRootPage')
			->with($options)
			->will($this->returnValue(FALSE));

		$indexDoc = $this->getMock(
			'tx_mksearch_model_IndexerDocumentBase', array('addField'), array('', '')
		);
		$model = tx_rnbase::makeInstance(
			'tx_rnbase_model_base', array('pid' => 123)
		);
		$indexDoc->expects($this->never())
			->method('addField');

		$this->callInaccessibleMethod($indexer, 'indexSiteRootPage', $model, 'tt_content', $indexDoc, $options);
	}

	/**
	 * @group unit
	 * @dataProvider getTestDataForShouldIndexSiteRootPageTest
	 */
	public function testShouldIndexSiteRootPage($options, $expected) {
		$indexer = $this->getMock(
			'tx_mksearch_tests_fixtures_indexer_Dummy', array()
		);

		self::assertEquals(
			$expected,
			$this->callInaccessibleMethod(
				$indexer, 'shouldIndexSiteRootPage', $options
			)
		);
	}

	public function getTestDataForShouldIndexSiteRootPageTest() {
		return array(
			__LINE__ => array(
				'options' => array('indexSiteRootPage' => 1),
				'expected' => TRUE,
			),
			__LINE__ => array(
				'options' => array('indexSiteRootPage' => 0),
				'expected' => FALSE,
			),
			__LINE__ => array(
				'options' => array(),
				'expected' => FALSE,
			),
		);
	}

}
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContent_testcase.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContent_testcase.php']);
}
