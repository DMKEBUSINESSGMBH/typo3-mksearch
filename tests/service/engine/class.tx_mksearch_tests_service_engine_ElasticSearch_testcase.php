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
class tx_mksearch_tests_service_engine_ElasticSearch_testcase
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

	/**
	 * @group unit
	 */
	public function testInitElasticSearchConnectionSetsIndexProperty() {
		$service = $this->getMock(
			'tx_mksearch_service_engine_ElasticSearch',
			array('getElasticaIndex', 'isServerAvailable', 'getLogger')
		);

		$service->expects($this->once())
			->method('isServerAvailable')
			->will($this->returnValue(TRUE));

		$credentials = array('someCredentials');
		$service->expects($this->once())
			->method('getElasticaIndex')
			->with($credentials)
			->will($this->returnValue('index'));

		$indexProperty = new ReflectionProperty(
			'tx_mksearch_service_engine_ElasticSearch', 'index'
		);
		$indexProperty->setAccessible(TRUE);

		$this->callInaccessibleMethod(
			$service, 'initElasticSearchConnection', $credentials
		);

		$this->assertEquals(
			'index', $indexProperty ->getValue($service), 'index property falsch'
		);
	}

	/**
	 * @group unit
	 */
	public function testInitElasticSearchConnectionCallsLoggerNotIfServerAvailable() {
		$service = $this->getMock(
			'tx_mksearch_service_engine_ElasticSearch',
			array('getElasticaIndex', 'isServerAvailable', 'getLogger')
		);

		$service->expects($this->never())
			->method('getLogger');

		$service->expects($this->once())
			->method('isServerAvailable')
			->will($this->returnValue(TRUE));

		$this->callInaccessibleMethod(
			$service, 'initElasticSearchConnection', array()
		);
	}

	/**
	 * @group unit
	 * @expectedException Elastica\Exception\ClientException
	 * @expectedExceptionMessage ElasticSearch service not responding.
	 */
	public function testInitElasticSearchConnectionThrowsExceptionAndLogsErrorIfServerNotAvailable() {
		$service = $this->getMock(
				'tx_mksearch_service_engine_ElasticSearch',
				array('getElasticaIndex', 'isServerAvailable', 'getLogger')
		);

		$credentials = array('someCredentials');
		$logger = $this->getMockClass('tx_rnbase_util_Logger', array('fatal'));
		$logger::staticExpects($this->once())
			->method('fatal')
			->with(
				'ElasticSearch service not responding.', 'mksearch',
				array($credentials)
			);

		$service->expects($this->once())
			->method('getLogger')
			->will($this->returnValue($logger));

		$service->expects($this->once())
			->method('isServerAvailable')
			->will($this->returnValue(FALSE));

		$this->callInaccessibleMethod(
			$service, 'initElasticSearchConnection', $credentials
		);
	}

	/**
	 * @group unit
	 */
	public function testIsSingleServerConfiguration() {
		$this->assertTrue(
			$this->callInaccessibleMethod(
				tx_rnbase::makeInstance('tx_mksearch_service_engine_ElasticSearch'),
				'isSingleServerConfiguration', array(1)
			)
		);

		$this->assertFalse(
			$this->callInaccessibleMethod(
				tx_rnbase::makeInstance('tx_mksearch_service_engine_ElasticSearch'),
				'isSingleServerConfiguration', array(1, 2)
			)
		);
	}

	/**
	 * @group unit
	 */
	public function testGetElasticaCredentialsFromCredentialsStringForSingleServerConfiguration() {
		$this->assertEquals(
			array(
				'host' => 1,
				'port' => 2,
				'path' => 3,
				'index' => 4,
			),
			$this->callInaccessibleMethod(
				tx_rnbase::makeInstance('tx_mksearch_service_engine_ElasticSearch'),
				'getElasticaCredentialsFromCredentialsString', '1,2,3,4'
			),
			'Konfig falsch für Elastica gebaut'
		);
	}

	/**
	 * @group unit
	 */
	public function testGetElasticaCredentialsFromCredentialsStringForClusterSetupConfiguration() {
		$this->assertEquals(
			array(
				'servers' => array(
					array(
						'host' => 1,
						'port' => 2,
						'path' => 3,
						'index' => 4,
					),
					array(
						'host' => 5,
						'port' => 6,
						'path' => 7,
						'index' => 8,
					),
				)
			),
			$this->callInaccessibleMethod(
				tx_rnbase::makeInstance('tx_mksearch_service_engine_ElasticSearch'),
				'getElasticaCredentialsFromCredentialsString', '1,2,3,4;5,6,7,8'
			),
			'Konfig falsch für Elastica gebaut'
		);
	}

	/**
	 * @group unit
	 */
	public function testOpenIndex() {
		$service = $this->getMock(
			'tx_mksearch_service_engine_ElasticSearch',
			array(
				'getElasticaCredentialsFromCredentialsString',
				'initElasticSearchConnection'
			)
		);
		$index = $this->getMock(
			'tx_mksearch_model_internal_Index',
			array('getCredentialString'),
			array(array())
		);
		$index->expects($this->once())
			->method('getCredentialString')
			->will($this->returnValue('credentialsString'));

		$service->expects($this->once())
			->method('getElasticaCredentialsFromCredentialsString')
			->with('credentialsString')
			->will($this->returnValue(array('credentialsForElastica')));

		$service->expects($this->once())
			->method('initElasticSearchConnection')
			->with(array('credentialsForElastica'));

		$service->openIndex($index);
	}

	/**
	 * @group unit
	 */
	public function testGetIndex() {
		$service = $this->getMock(
			'tx_mksearch_service_engine_ElasticSearch',
			array('openIndex')
		);
		$indexModelProperty = new ReflectionProperty(
			'tx_mksearch_service_engine_ElasticSearch', 'indexModel'
		);
		$indexModelProperty->setAccessible(TRUE);
		$indexModel = tx_rnbase::makeInstance('tx_mksearch_model_internal_Index', array());
		$indexModelProperty->setValue($service, $indexModel);

		$service->expects($this->once())
			->method('openIndex')
			->with($indexModel);

		$service->getIndex();
	}

	/**
	 * @group unit
	 */
	public function testGetIndexIfIndexAlreadySet() {
		$service = $this->getMock(
			'tx_mksearch_service_engine_ElasticSearch',
			array('openIndex')
		);
		$indexProperty = new ReflectionProperty(
			'tx_mksearch_service_engine_ElasticSearch', 'index'
		);
		$indexProperty->setAccessible(TRUE);
		$indexProperty->setValue($service, new stdClass());

		$service->expects($this->never())
			->method('openIndex');

		$service->getIndex();
	}

	/**
	 * @group unit
	 */
	public function testSetIndexModel() {
		$service = tx_rnbase::makeInstance('tx_mksearch_service_engine_ElasticSearch');

		$indexModel = tx_rnbase::makeInstance('tx_mksearch_model_internal_Index', array());
		$service->setIndexModel($indexModel);

		$indexModelProperty = new ReflectionProperty(
				'tx_mksearch_service_engine_ElasticSearch', 'indexModel'
		);
		$indexModelProperty->setAccessible(TRUE);
		$this->assertSame(
			$indexModel, $indexModelProperty->getValue($service),
			'indexModel falsch gesetzt'
		);
	}
}