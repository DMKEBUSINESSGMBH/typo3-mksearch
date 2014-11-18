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

require_once t3lib_extMgm::extPath('rn_base', 'class.tx_rnbase.php');
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
	 * @unit
	 */
	public function testProcessAutoUpdateWithEmptyDatamap() {
		$hook = $this->getHookMock(
			$service = $this->getMock('tx_mksearch_service_internal_Index')
		);
		$tce = $this->getTceMock();
		$tce->BE_USER->workspace = 1;

		$service
			->expects($this->never())
			->method('findAll')
		;

		$hook->processDatamap_afterAllOperations($tce);
	}
	/**
	 * @unit
	 */
	public function testProcessAutoUpdateWithWorkspace() {
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
	 * @return PHPUnit_Framework_MockObject_MockObject|tx_mksearch_hooks_IndexerAutoUpdate;
	 */
	protected function getHookMock($service = NULL) {
		$service = $service ? $service : $this->getMock('tx_mksearch_service_internal_Index');

		$hook = $this->getMock(
			'tx_mksearch_hooks_IndexerAutoUpdate',
			array('getIntIndexService', 'getIndexersForTable')
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
	 * @return t3lib_TCEmain
	 */
	protected function getTceMock() {
		$tce = tx_rnbase::makeInstance('t3lib_TCEmain');
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

