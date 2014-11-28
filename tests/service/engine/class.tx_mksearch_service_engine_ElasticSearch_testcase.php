<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_tests
 *  @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2011 Hannes Bochmann <dev@dmk-ebusiness.de>
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
 */
require_once t3lib_extMgm::extPath('rn_base', 'class.tx_rnbase.php');
tx_rnbase::load('tx_mksearch_tests_Testcase');
tx_rnbase::load('tx_mksearch_service_engine_ElasticSearch');

/**
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_tests
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_service_engine_ElasticSearch_testcase
	extends tx_mksearch_tests_Testcase
{
	/**
	 * @group unit
	 */
	public function testGetElasticIndex() {
		$elasticaClient = $this->callInaccessibleMethod(
			tx_rnbase::makeInstance('tx_mksearch_service_engine_ElasticSearch'),
			'getElasticaIndex', array('index' => 'unknown')
		);

		$this->assertInstanceOf(
			'\\Elastica\\Index', $elasticaClient, 'Client hat falsche Klasse'
		);
	}

	/**
	 * @group unit
	 */
	public function testGetLogger() {
		$loggerClass = $this->callInaccessibleMethod(
			tx_rnbase::makeInstance('tx_mksearch_service_engine_ElasticSearch'),
			'getLogger'
		);

		$this->assertEquals(
				'tx_rnbase_util_Logger', $loggerClass, 'falsche Logger-Klasse'
		);
	}

	/**
	 * @group unit
	 * @dataProvider getServerStatus
	 */
	public function testIsServerAvailable($returnCode, $expectedReturn) {
		$status = $this->getMock(
			'stdClass', array('getServerStatus')
		);
		$status->expects($this->once())
			->method('getServerStatus')
			->will($this->returnValue(array('status' => $returnCode)));

		$client = $this->getMock(
			'stdClass', array('getStatus')
		);
		$client->expects($this->once())
			->method('getStatus')
			->will($this->returnValue($status));

		$index = $this->getMock(
			'stdClass', array('getClient')
		);
		$index->expects($this->once())
			->method('getClient')
			->will($this->returnValue($client));

		$service = $this->getMock(
			'tx_mksearch_service_engine_ElasticSearch', array('getIndex')
		);
		$service->expects($this->once())
			->method('getIndex')
			->will($this->returnValue($index));

		$this->assertEquals(
			$expectedReturn,
			$this->callInaccessibleMethod($service, 'isServerAvailable'),
			'falscher return Wert'
		);
	}

	/**
	 * @return multitype:multitype:number boolean
	 */
	public function getServerStatus() {
		return array(
			array(200, TRUE),
			array(300, FALSE),
		);
	}
}