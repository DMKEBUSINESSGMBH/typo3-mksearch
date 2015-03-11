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
tx_rnbase::load('tx_mksearch_tests_Testcase');

/**
 *
 * @package TYPO3
 * @subpackage mksearch
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_signalSlotDispatcher_FileIndexRepository_testcase
	extends tx_mksearch_tests_Testcase {


	/**
	 * (non-PHPdoc)
	 * @see tx_mksearch_tests_Testcase::setUp()
	 */
	protected function setUp() {
		if (!tx_rnbase_util_TYPO3::isTYPO60OrHigher()) {
			$this->markTestSkipped('Erst ab TYPO3 6.x relevant');
		}

		parent::setUp();
	}

	/**
	 * @group unit
	 */
	public function testGetInternalIndexService() {
		$signalSlotDispatcher = tx_rnbase::makeInstance(
			'tx_mksearch_signalSlotDispatcher_FileIndexRepsitory'
		);

		$this->assertInstanceOf(
			'tx_mksearch_service_internal_Index',
			$this->callInaccessibleMethod(
				$signalSlotDispatcher, 'getInternalIndexService'
			)
		);
	}

	/**
	 * @group unit
	 */
	public function testPutFileIntoQueueWhenDataIsInteger() {
		$internalIndexService = $this->getMock(
			'tx_mksearch_service_internal_Index',
			array('addRecordToIndex')
		);
		$internalIndexService->expects($this->once())
			->method('addRecordToIndex')
			->with('sys_file', 123);

		$signalSlotDispatcher = $this->getDispatcherMock($internalIndexService);

		$signalSlotDispatcher->putFileIntoQueue(123);
	}

	/**
	 * @group unit
	 */
	public function testPutFileIntoQueueWhenDataIsArray() {
		$internalIndexService = $this->getMock(
			'tx_mksearch_service_internal_Index',
			array('addRecordToIndex')
		);
		$internalIndexService->expects($this->once())
			->method('addRecordToIndex')
			->with('sys_file', 123);

		$signalSlotDispatcher = $this->getDispatcherMock($internalIndexService);

		$signalSlotDispatcher->putFileIntoQueue(array('uid' => 123));
	}

	/**
	 * @group unit
	 */
	public function testDispatcherRegisteredForRecordCreatedSignalSlot() {
		$signalSlotDispatcher = tx_rnbase::makeInstance(
			'TYPO3\CMS\Extbase\SignalSlot\Dispatcher'
		);

		$slots = new ReflectionProperty(
			'TYPO3\CMS\Extbase\SignalSlot\Dispatcher', 'slots'
		);
		$slots->setAccessible(TRUE);

		$slotsArray = $slots->getValue($signalSlotDispatcher);

		$singalSlotDispatcherCorrectConfigured = FALSE;
		foreach ($slotsArray['TYPO3\CMS\Core\Resource\Index\FileIndexRepository']['recordCreated'] as $signalSlotDispatcherConfiguration) {			;
			if (
				($signalSlotDispatcherConfiguration['class'] == 'tx_mksearch_signalSlotDispatcher_FileIndexRepsitory') &&
				($signalSlotDispatcherConfiguration['method'] == 'putFileIntoQueue')
			) {
				$singalSlotDispatcherCorrectConfigured = TRUE;
			}
		}

		$this->assertTrue($singalSlotDispatcherCorrectConfigured);
	}

	/**
	 * @group unit
	 */
	public function testDispatcherRegisteredForRecordUpdatedSignalSlot() {
		$signalSlotDispatcher = tx_rnbase::makeInstance(
			'TYPO3\CMS\Extbase\SignalSlot\Dispatcher'
		);

		$slots = new ReflectionProperty(
			'TYPO3\CMS\Extbase\SignalSlot\Dispatcher', 'slots'
		);
		$slots->setAccessible(TRUE);

		$slotsArray = $slots->getValue($signalSlotDispatcher);

		$singalSlotDispatcherCorrectConfigured = FALSE;
		foreach ($slotsArray['TYPO3\CMS\Core\Resource\Index\FileIndexRepository']['recordUpdated'] as $signalSlotDispatcherConfiguration) {			;
			if (
				($signalSlotDispatcherConfiguration['class'] == 'tx_mksearch_signalSlotDispatcher_FileIndexRepsitory') &&
				($signalSlotDispatcherConfiguration['method'] == 'putFileIntoQueue')
			) {
				$singalSlotDispatcherCorrectConfigured = TRUE;
			}
		}

		$this->assertTrue($singalSlotDispatcherCorrectConfigured);
	}

	/**
	 * @group unit
	 */
	public function testDispatcherRegisteredForRecordDeletedSignalSlot() {
		$signalSlotDispatcher = tx_rnbase::makeInstance(
			'TYPO3\CMS\Extbase\SignalSlot\Dispatcher'
		);

		$slots = new ReflectionProperty(
			'TYPO3\CMS\Extbase\SignalSlot\Dispatcher', 'slots'
		);
		$slots->setAccessible(TRUE);

		$slotsArray = $slots->getValue($signalSlotDispatcher);

		$singalSlotDispatcherCorrectConfigured = FALSE;
		foreach ($slotsArray['TYPO3\CMS\Core\Resource\Index\FileIndexRepository']['recordDeleted'] as $signalSlotDispatcherConfiguration) {			;
			if (
				($signalSlotDispatcherConfiguration['class'] == 'tx_mksearch_signalSlotDispatcher_FileIndexRepsitory') &&
				($signalSlotDispatcherConfiguration['method'] == 'putFileIntoQueue')
			) {
				$singalSlotDispatcherCorrectConfigured = TRUE;
			}
		}

		$this->assertTrue($singalSlotDispatcherCorrectConfigured);
	}

	/**
	 * @param tx_mksearch_service_internal_Index $internalIndexService
	 * @return Ambigous <PHPUnit_Framework_MockObject_MockObject, tx_mksearch_signalSlotDispatcher_FileIndexRepsitory>
	 */
	private function getDispatcherMock(
		tx_mksearch_service_internal_Index $internalIndexService
	) {
		$signalSlotDispatcher = $this->getMock(
			'tx_mksearch_signalSlotDispatcher_FileIndexRepsitory',
			array('getInternalIndexService')
		);

		$signalSlotDispatcher->expects($this->once())
			->method('getInternalIndexService')
			->will($this->returnValue($internalIndexService));

		return $signalSlotDispatcher;
	}
}