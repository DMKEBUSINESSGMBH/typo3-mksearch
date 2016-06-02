<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011-2014 DMK E-Business GmbH <dev@dmk-ebusiness.de>
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
tx_rnbase::load('tx_mksearch_hooks_IndexerAutoUpdate');
tx_rnbase::load('tx_mksearch_service_internal_Index');
tx_rnbase::load('tx_mksearch_model_internal_Index');
tx_rnbase::load('tx_mksearch_tests_Util');

/**
 * Wir müssen in diesem Fall mit der DB testen da wir die pages
 * Tabelle benötigen
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_tests
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_hooks_IndexerAutoUpdate_testcase
	extends tx_mksearch_tests_Testcase {

	/**
	 * (non-PHPdoc)
	 * @see tx_mksearch_tests_Testcase::setUp()
	 */
	protected function setUp() {
		tx_mksearch_tests_Util::storeExtConf();
		tx_mksearch_tests_Util::setExtConfVar('enableRnBaseUtilDbHook', 1);
	}

	/**
	 * (non-PHPdoc)
	 * @see tx_mksearch_tests_Testcase::tearDown()
	 */
	protected function tearDown() {
		tx_mksearch_tests_Util::restoreExtConf();
	}

	/**
	 * @unit
	 */
	public function testProcessAutoUpdateWithWorkspace() {
		$hook = $this->getHookMock(
			$service = $this->getMock('tx_mksearch_service_internal_Index')
		);
		$tce = $this->getTceMock();
		$tce->BE_USER->workspace = 1;
		$tce->datamap = array('123' => array());

		$service
			->expects($this->once())
			->method('findAll')
		;

		$hook->processDatamap_afterAllOperations($tce);
	}

	/**
	 * @unit
	 */
	public function testProcessAutoUpdateWithEmptyDatamap() {
		$hook = $this->getHookMock(
			$service = $this->getMock('tx_mksearch_service_internal_Index')
		);
		$tce = $this->getTceMock();
		$tce->datamap = array();

		$service
			->expects($this->never())
			->method('findAll')
		;

		$hook->processDatamap_afterAllOperations($tce);
	}
	/**
	 * @unit
	 */
	public function testProcessAutoUpdate() {
		$hook = $this->getHookMock(
			$service = $this->getMock('tx_mksearch_service_internal_Index')
		);
		$tce = $this->getTceMock();

		$indices = array(
			$this->getMock(
				'tx_mksearch_model_internal_Index',
				NULL,
				array(
					array('uid'=>1)
				)
			),
		);

		$hook
			->expects($this->exactly(count($tce->datamap['tt_content'])))
			->method('getUidsToIndex')
			->will($this->returnValueMap(
				array(
					array('tt_content', 1, array(1)),
					array('tt_content', 2, array(2)),
					array('tt_content', 3, array(3)),
					array('tt_content', 5, array(5)),
					array('tt_content', 8, array(8)),
					array('tt_content', 13, array(13)),
				)
			))
		;

		$service
			->expects($this->once())
			->method('findAll')
			->will($this->returnValue($indices))
		;
		$service
			->expects($this->once())
			->method('isIndexerDefined')
			->will($this->returnValue(TRUE))
		;
		$service
			->expects($this->exactly(count($tce->datamap['tt_content'])))
			->method('addRecordToIndex')
		;

		$this->callInaccessibleMethod(
			$hook, 'processAutoUpdate',
			array('tt_content' => array_keys($tce->datamap['tt_content']))
		);

	}
	/**
	 * @unit
	 */
	public function testRnBaseDoInsertPost() {
		$hook = $this->getHookMock(
			$service = $this->getMock('tx_mksearch_service_internal_Index')
		);

		$hookParams = array('tablename' => 'tt_content', 'uid' => 1);

		$hook
			->expects($this->once())
			->method('getUidsToIndex')
			->will($this->returnValueMap(
				array(
					array('tt_content', 1, array(1)),
				)
			))
		;

		$indices = array(
			$this->getMock(
				'tx_mksearch_model_internal_Index',
				NULL,
				array(
					array('uid'=>1)
				)
			),
		);

		$service
			->expects($this->once())
			->method('findAll')
			->will($this->returnValue($indices))
		;
		$service
			->expects($this->once())
			->method('isIndexerDefined')
			->will($this->returnValue(TRUE))
		;
		$service
			->expects($this->once())
			->method('addRecordToIndex')
			->with(
				$this->equalTo($hookParams['tablename']),
				$this->equalTo($hookParams['uid'])
			)
		;

		$hook->rnBaseDoInsertPost($hookParams);
	}
	/**
	 * @unit
	 */
	public function testRnBaseDoUpdatePost() {
		$hook = $this->getHookMock(
			$service = $this->getMock('tx_mksearch_service_internal_Index')
		);

		$hookParams = array(
			'tablename' => 'tt_content',
			'where' => 'deleted = 0',
			'values' => array('hidden' => '1'),
			'options' => array(),
		);

		$hook
			->expects($this->once())
			->method('getUidsToIndex')
			->with(
				$this->equalTo(
					$hookParams['tablename']
				),
				$this->equalTo(
					array(
						'type' => 'select',
						'from' => $hookParams['tablename'],
						'where' => $hookParams['where'],
						'options' => $hookParams['options'],
					)
				)
			)
			->will($this->returnValue(array('1', '2')))
		;

		$indices = array(
			$this->getMock(
				'tx_mksearch_model_internal_Index',
				NULL,
				array(
					array('uid'=>1)
				)
			),
		);

		$service
			->expects($this->once())
			->method('findAll')
			->will($this->returnValue($indices))
		;
		$service
			->expects($this->once())
			->method('isIndexerDefined')
			->will($this->returnValue(TRUE))
		;
		$service
			->expects($this->exactly(2))
			->method('addRecordToIndex')
			->with(
				$this->equalTo($hookParams['tablename']),
				$this->logicalOr($this->equalTo(1), $this->equalTo(2))
			)
		;

		$hook->rnBaseDoUpdatePost($hookParams);
	}

	/**
	 * @unit
	 */
	public function testRnBaseDoDeletePre() {
		// use the same method as doUpdate
		$this->testRnBaseDoUpdatePost();
	}

	/**
	 * @unit
	 */
	public function testRnBaseDoInsertPostIfHookDeactivated() {
		tx_mksearch_tests_Util::setExtConfVar('enableRnBaseUtilDbHook', 0);

		$hook = $this->getHookMock(
			$service = $this->getMock('tx_mksearch_service_internal_Index')
		);

		$hookParams = array('tablename' => 'tt_content', 'uid' => 1);

		$hook
			->expects($this->never())
			->method('getUidsToIndex');

		$service
			->expects($this->never())
			->method('findAll');

		$service
			->expects($this->never())
			->method('isIndexerDefined');

		$service
			->expects($this->never())
			->method('addRecordToIndex');

		$hook->rnBaseDoInsertPost($hookParams);
	}
	/**
	 * @unit
	 */
	public function testRnBaseDoUpdatePostIfHookDeactivated() {
		tx_mksearch_tests_Util::setExtConfVar('enableRnBaseUtilDbHook', 0);

		$hook = $this->getHookMock(
			$service = $this->getMock('tx_mksearch_service_internal_Index')
		);

		$hookParams = array(
			'tablename' => 'tt_content',
			'where' => 'deleted = 0',
			'values' => array('hidden' => '1'),
			'options' => array(),
		);

		$hook
			->expects($this->never())
			->method('getUidsToIndex');

		$service
			->expects($this->never())
			->method('findAll');
		$service
			->expects($this->never())
			->method('isIndexerDefined');
		$service
			->expects($this->never())
			->method('addRecordToIndex');

		$hook->rnBaseDoUpdatePost($hookParams);
	}

	/**
	 * @unit
	 */
	public function testGetRnBaseDatabaseUtility() {
		self::assertInstanceOf(
			'Tx_Rnbase_Database_Connection',
			$this->callInaccessibleMethod($this->getHookMock(), 'getRnbaseDatabaseUtility')
		);
	}

	/**
	 * @unit
	 */
	public function testGetUidsToIndexIfDataIsNumeric() {
		$hook = $this->getMock(
			'tx_mksearch_hooks_IndexerAutoUpdate',
			array('getRnbaseDatabaseUtility')
		);
		$hook->expects($this->never())->method('getRnbaseDatabaseUtility');


		self::assertEquals(
			array(123),
			$this->callInaccessibleMethod($hook, 'getUidsToIndex', '', 123)
		);
	}

	/**
	 * @unit
	 */
	public function testGetUidsToIndexIfDataIsString() {
		$hook = $this->getMock(
			'tx_mksearch_hooks_IndexerAutoUpdate',
			array('getRnbaseDatabaseUtility')
		);
		$hook->expects($this->never())->method('getRnbaseDatabaseUtility');


		self::assertEquals(
			array(),
			$this->callInaccessibleMethod($hook, 'getUidsToIndex', '', 'testString')
		);
	}

	/**
	 * @unit
	 */
	public function testGetUidsToIndexIfDataIsArrayButHasNoTypeKey() {
		$hook = $this->getMock(
			'tx_mksearch_hooks_IndexerAutoUpdate',
			array('getRnbaseDatabaseUtility')
		);
		$hook->expects($this->never())->method('getRnbaseDatabaseUtility');


		self::assertEquals(
			array(),
			$this->callInaccessibleMethod($hook, 'getUidsToIndex', '', array())
		);
	}

	/**
	 * @unit
	 */
	public function testGetUidsToIndexIfDataIsArrayAndHasWrongTypeKey() {
		$hook = $this->getMock(
			'tx_mksearch_hooks_IndexerAutoUpdate',
			array('getRnbaseDatabaseUtility')
		);
		$hook->expects($this->never())->method('getRnbaseDatabaseUtility');

		self::assertEquals(
			array(),
			$this->callInaccessibleMethod(
				$hook, 'getUidsToIndex', '', array('type' => 'noSelect')
			)
		);
	}

	/**
	 * @unit
	 * @dataProvider dataProviderGetUidsToIndex
	 */
	public function testGetUidsToIndexIfDataIsArrayAndHasSelectTypeKey(
		$data, $expectedFrom, $expectedOptions, $selectReturn, $expectedReturn
	) {
		$hook = $this->getMock(
			'tx_mksearch_hooks_IndexerAutoUpdate',
			array('getRnbaseDatabaseUtility')
		);
		$databaseUtility = $this->getMock(
			'Tx_Rnbase_Database_Connection',
			array('doSelect')
		);
		$databaseUtility->expects($this->once())
			->method('doSelect')
			->with('uid', $expectedFrom, $expectedOptions)
			->will($this->returnValue($selectReturn));
		$hook->expects($this->once())
			->method('getRnbaseDatabaseUtility')
			->will($this->returnValue($databaseUtility));

		self::assertEquals(
			$expectedReturn,
			$this->callInaccessibleMethod(
				$hook, 'getUidsToIndex', 'test_table', $data
			)
		);
	}

	/**
	 * @return multitype:multitype:string multitype:string  multitype:number  multitype:multitype:number
	 */
	public function dataProviderGetUidsToIndex() {
		return array(
			// from aus übergebenem data array
			array(
				array('type' => 'select', 'from' => 'another_test_table'),
				'another_test_table',
				array('enablefieldsoff' => TRUE, 'where' => ''),
				array(123 => array('uid' => 123)),
				array(123)
			),
			// from aus übergebener tabelle
			array(
				array('type' => 'select'),
				'test_table',
				array('enablefieldsoff' => TRUE, 'where' => ''),
				array(123 => array('uid' => 123)),
				array(123)
			),
			// options aus übergebenem data array
			array(
				array('type' => 'select', 'options' => array('debug' => TRUE)),
				'test_table',
				array('enablefieldsoff' => TRUE, 'where' => '', 'debug' => TRUE),
				array(123 => array('uid' => 123)),
				array(123)
			),
			// where aus options hat vorrang
			array(
				array('type' => 'select', 'options' => array('where' => 'where clause'), 'where' => 'clause'),
				'test_table',
				array('enablefieldsoff' => TRUE, 'where' => 'where clause'),
				array(123 => array('uid' => 123)),
				array(123)
			),
			// where aus data
			array(
				array('type' => 'select', 'where' => 'clause'),
				'test_table',
				array('enablefieldsoff' => TRUE, 'where' => 'clause'),
				array(123 => array('uid' => 123)),
				array(123)
			),
			// doSelect liefert kein Ergebnis
			array(
				array('type' => 'select'),
				'test_table',
				array('enablefieldsoff' => TRUE, 'where' => ''),
				array(),
				array()
			),
			// kein uid key im select array result
			array(
				array('type' => 'select'),
				'test_table',
				array('enablefieldsoff' => TRUE, 'where' => ''),
				array(123 => array('pid' => 123)),
				array()
			),
			// mehr als ein ergebnis
			array(
				array('type' => 'select'),
				'test_table',
				array('enablefieldsoff' => TRUE, 'where' => ''),
				array(123 => array('uid' => 123), 456 => array('uid' => 456)),
				array(123, 456)
			)
		);
	}

	/**
	 * @return PHPUnit_Framework_MockObject_MockObject|tx_mksearch_hooks_IndexerAutoUpdate;
	 */
	protected function getHookMock($service = NULL) {
		$service = $service ? $service : $this->getMock('tx_mksearch_service_internal_Index');

		$hook = $this->getMock(
			'tx_mksearch_hooks_IndexerAutoUpdate',
			array('getIntIndexService', 'getIndexersForTable', 'getUidsToIndex')
		);

		$hook
			->expects($this->any())
			->method('getIntIndexService')
			->will($this->returnValue($service))
		;

		$indexers[] = tx_rnbase::makeInstance('tx_mksearch_indexer_TtContent');
		$hook
			->expects($this->any())
			->method('getIndexersForTable')
			->will($this->returnValue($indexers))
		;
		return $hook;
	}

	/**
	 * @return \TYPO3\CMS\Core\DataHandling\DataHandler
	 */
	protected function getTceMock() {
		$tce = tx_rnbase::makeInstance(tx_rnbase_util_Typo3Classes::getDataHandlerClass());
		// default datamap
		$tce->datamap = array(
			'tt_content' => array(
				'1' => array('uid' => '1'),
				'2' => array('uid' => '2'),
				'3' => array('uid' => '3'),
				'5' => array('uid' => '5'),
				'8' => array('uid' => '8'),
				'13' => array('uid' => '13'),
			),
		);
		// default workspace
		$tce->BE_USER = new stdClass();
		$tce->BE_USER->workspace = 0;
		return $tce;
	}

}
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/hooks/class.tx_mksearch_tests_hooks_IndexerAutoUpdate_testcase.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/hooks/class.tx_mksearch_tests_hooks_IndexerAutoUpdate_testcase.php']);
}

