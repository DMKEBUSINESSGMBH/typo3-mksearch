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
tx_rnbase::load('tx_mksearch_tests_Util');

/**
 *
 * @package TYPO3
 * @subpackage mksearch
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_service_internal_Index_testcase
	extends tx_mksearch_tests_Testcase {

	/**
	 * (non-PHPdoc)
	 * @see tx_mksearch_tests_Testcase::setUp()
	 */
	protected function setUp() {
		tx_mksearch_tests_Util::storeExtConf();
	}

	/**
	 * (non-PHPdoc)
	 * @see tx_mksearch_tests_Testcase::tearDown()
	 */
	protected function tearDown() {
		tx_mksearch_tests_Util::restoreExtConf();
	}

	/**
	 * @group unit
	 */
	public function testGetDatabaseUtility() {
		$indexService = tx_rnbase::makeInstance(
			'tx_mksearch_service_internal_Index'
		);

		self::assertInstanceOf(
			'Tx_Rnbase_Database_Connection',
			$this->callInaccessibleMethod(
				$indexService, 'getDatabaseUtility'
			)
		);
	}

	/**
	 * @group unit
	 */
	public function testGetSecondsToKeepQueueEntries() {
		$indexService = tx_rnbase::makeInstance(
			'tx_mksearch_service_internal_Index'
		);

		self::assertEquals(
			2592000,
			$this->callInaccessibleMethod(
				$indexService, 'getSecondsToKeepQueueEntries'
			),
			'Fallback falsch'
		);

		tx_mksearch_tests_Util::setExtConfVar('secondsToKeepQueueEntries', 123);

		self::assertEquals(
			123,
			$this->callInaccessibleMethod(
				$indexService, 'getSecondsToKeepQueueEntries'
			),
			'Konfiguration nicht korrekt ausgelsen'
		);
	}

	/**
	 * @group unit
	 */
	public function testDeleteOldQueueEntries() {
		$indexService = $this->getAccessibleMock(
			'tx_mksearch_service_internal_Index',
			array('getDatabaseUtility', 'getSecondsToKeepQueueEntries')
		);

		$indexService->expects($this->once())
			->method('getSecondsToKeepQueueEntries')
			->will($this->returnValue(123));

		$databaseUtility = $this->getMock(
			'Tx_Rnbase_Database_Connection',
			array('doDelete')
		);
		$databaseUtility->expects($this->once())
			->method('doDelete')
			->with('tx_mksearch_queue', 'deleted = 1 AND cr_date < NOW() - 123');

		$indexService->expects($this->once())
			->method('getDatabaseUtility')
			->will($this->returnValue($databaseUtility));

		$indexService->_call('deleteOldQueueEntries');
	}


	/**
	 * test for tx_mksearch_service_internal_Index::addModelsToIndex.
	 * @group unit
	 * @test
	 */
	public function testAddModelsToIndex()
	{
		$model = $this->getModel(array('uid' => '5'));
		$options = array();

		/* @var $srv tx_mksearch_service_internal_Index */
		$srv = $this->getMock(
			'tx_mksearch_service_internal_Index',
			array('addRecordToIndex')
		);

		$srv
			->expects(self::once())
			->method('addRecordToIndex')
			->with(
				$model->getTableName(),
				$model->getUid(),
				FALSE,
				FALSE,
				FALSE,
				$options
			)
		;

		$srv->addModelsToIndex(
			array($model),
			$options
		);
	}
}
