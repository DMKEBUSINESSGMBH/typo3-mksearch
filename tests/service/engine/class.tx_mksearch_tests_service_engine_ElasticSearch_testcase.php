<?php
/**
 * @author Hannes Bochmann
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
tx_rnbase::load('tx_mksearch_tests_Testcase');
tx_rnbase::load('tx_mksearch_service_engine_ElasticSearch');
tx_rnbase::load('tx_mksearch_model_internal_Index');

/**
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_service_engine_ElasticSearch_testcase extends tx_mksearch_tests_Testcase
{
    public function setUp()
    {
        if (version_compare(phpversion(), '7.0.0') >= 0) {
            $this->markTestSkipped('Elastic does not support php 7 currently');
        }
    }

    /**
     * @group unit
     */
    public function testGetElasticIndex()
    {
        $service = tx_rnbase::makeInstance('tx_mksearch_service_engine_ElasticSearch');
        $indexName = new ReflectionProperty(
            'tx_mksearch_service_engine_ElasticSearch',
            'indexName'
        );
        $indexName->setAccessible(true);
        $indexName->setValue($service, 'unknown');
        $elasticaClient = $this->callInaccessibleMethod(
            $service,
            'getElasticaIndex',
            array()
        );

        self::assertInstanceOf(
            '\\Elastica\\Index',
            $elasticaClient,
            'Client hat falsche Klasse'
        );
    }

    /**
     * @group unit
     */
    public function testGetLogger()
    {
        $loggerClass = $this->callInaccessibleMethod(
            tx_rnbase::makeInstance('tx_mksearch_service_engine_ElasticSearch'),
            'getLogger'
        );

        self::assertEquals(
            'tx_rnbase_util_Logger',
            $loggerClass,
            'falsche Logger-Klasse'
        );
    }

    /**
     * @group unit
     * @dataProvider getServerStatus
     */
    public function testIsServerAvailable($returnCode, $expectedReturn)
    {
        $response = $this->getMock(
            'stdClass',
            array('getStatus')
        );
        $response->expects($this->once())
            ->method('getStatus')
            ->will($this->returnValue($returnCode));

        $status = $this->getMock(
            'stdClass',
            array('getResponse')
        );
        $status->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($response));

        $client = $this->getMock(
            'stdClass',
            array('getStatus')
        );
        $client->expects($this->once())
            ->method('getStatus')
            ->will($this->returnValue($status));

        $index = $this->getMock(
            'stdClass',
            array('getClient')
        );
        $index->expects($this->once())
            ->method('getClient')
            ->will($this->returnValue($client));

        $service = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            array('getIndex')
        );
        $service->expects($this->once())
            ->method('getIndex')
            ->will($this->returnValue($index));

        self::assertEquals(
            $expectedReturn,
            $this->callInaccessibleMethod($service, 'isServerAvailable'),
            'falscher return Wert'
        );
    }

    /**
     * @return multitype:multitype:number boolean
     */
    public function getServerStatus()
    {
        return array(
            array(200, true),
            array(300, false),
        );
    }

    /**
     * @group unit
     */
    public function testInitElasticSearchConnectionSetsIndexPropertyCreatesIndexIfNotExistsAndOpensIndex()
    {
        $service = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            array('getElasticaIndex', 'isServerAvailable', 'getLogger')
        );

        $service->expects($this->once())
            ->method('isServerAvailable')
            ->will($this->returnValue(true));

        $credentials = array('someCredentials');
        $index = $this->getMock('stdClass', array('open', 'exists', 'create'));
        $index->expects($this->once())
            ->method('open');
        $index->expects($this->once())
            ->method('exists')
            ->will($this->returnValue(true));
        $index->expects($this->never())
            ->method('create');

        $service->expects($this->once())
            ->method('getElasticaIndex')
            ->with($credentials)
            ->will($this->returnValue($index));

        $indexProperty = new ReflectionProperty(
            'tx_mksearch_service_engine_ElasticSearch',
            'index'
        );
        $indexProperty->setAccessible(true);

        $this->callInaccessibleMethod(
            $service,
            'initElasticSearchConnection',
            $credentials
        );

        self::assertEquals(
            $index,
            $indexProperty->getValue($service),
            'index property falsch'
        );
    }

    /**
     * @group unit
     */
    public function testInitElasticSearchConnectionSetsIndexPropertyCreatesIndexNotIfExistsAndOpensIndex()
    {
        $service = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            array('getElasticaIndex', 'isServerAvailable', 'getLogger')
        );

        $service->expects($this->once())
            ->method('isServerAvailable')
            ->will($this->returnValue(true));

        $credentials = array('someCredentials');
        $index = $this->getMock('stdClass', array('open', 'exists', 'create'));
        $index->expects($this->once())
            ->method('open');
        $index->expects($this->once())
            ->method('exists')
            ->will($this->returnValue(false));
        $index->expects($this->once())
            ->method('create');

        $service->expects($this->once())
            ->method('getElasticaIndex')
            ->with($credentials)
            ->will($this->returnValue($index));

        $indexProperty = new ReflectionProperty(
            'tx_mksearch_service_engine_ElasticSearch',
            'index'
        );
        $indexProperty->setAccessible(true);

        $this->callInaccessibleMethod(
            $service,
            'initElasticSearchConnection',
            $credentials
        );

        self::assertEquals(
            $index,
            $indexProperty->getValue($service),
            'index property falsch'
        );
    }

    /**
     * @group unit
     */
    public function testInitElasticSearchConnectionCallsLoggerNotIfServerAvailable()
    {
        $service = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            array('getElasticaIndex', 'isServerAvailable', 'getLogger')
        );

        $index = $this->getMock('stdClass', array('open', 'exists', 'create'));
        $service->expects($this->once())
            ->method('getElasticaIndex')
            ->will($this->returnValue($index));

        $service->expects($this->never())
            ->method('getLogger');

        $service->expects($this->once())
            ->method('isServerAvailable')
            ->will($this->returnValue(true));

        $this->callInaccessibleMethod(
            $service,
            'initElasticSearchConnection',
            array()
        );
    }

    /**
     * @group unit
     * @expectedException Elastica\Exception\ClientException
     * @expectedExceptionMessage ElasticSearch service not responding.
     */
    public function testInitElasticSearchConnectionThrowsExceptionAndLogsErrorIfServerNotAvailable()
    {
        $service = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            array('getElasticaIndex', 'isServerAvailable', 'getLogger')
        );

        $credentials = array('someCredentials');
        $logger = $this->getMock('stdClass', array('fatal'));
        $logger->expects($this->once())
            ->method('fatal')
            ->with(
                'ElasticSearch service not responding.',
                'mksearch',
                array($credentials)
            );

        $service->expects($this->once())
            ->method('getLogger')
            ->will($this->returnValue($logger));

        $service->expects($this->once())
            ->method('isServerAvailable')
            ->will($this->returnValue(false));

        $index = $this->getMock('stdClass', array('open', 'exists', 'create'));
        $service->expects($this->once())
            ->method('getElasticaIndex')
            ->will($this->returnValue($index));

        $this->callInaccessibleMethod(
            $service,
            'initElasticSearchConnection',
            $credentials
        );
    }

    /**
     * @group unit
     */
    public function testGetElasticaCredentialsFromCredentialsStringSetsInitialCredentialsStringToProperty()
    {
        $service = tx_rnbase::makeInstance('tx_mksearch_service_engine_ElasticSearch');
        $this->callInaccessibleMethod(
            $service,
            'getElasticaCredentialsFromCredentialsString',
            'index;1,2,3,4'
        );
        $credentialsStringProperty = new ReflectionProperty(
            'tx_mksearch_service_engine_ElasticSearch',
            'credentialsString'
        );
        $credentialsStringProperty->setAccessible(true);
        self::assertEquals(
            'index;1,2,3,4',
            $credentialsStringProperty->getValue($service),
            'property falsch'
        );
    }

    /**
     * @group unit
     */
    public function testGetElasticaCredentialsFromCredentialsStringSetsIndexNameProperty()
    {
        $service = tx_rnbase::makeInstance('tx_mksearch_service_engine_ElasticSearch');
        $this->callInaccessibleMethod(
            $service,
            'getElasticaCredentialsFromCredentialsString',
            'index;1,2,3,4'
        );
        $indexNameProperty = new ReflectionProperty(
            'tx_mksearch_service_engine_ElasticSearch',
            'indexName'
        );
        $indexNameProperty->setAccessible(true);
        self::assertEquals(
            'index',
            $indexNameProperty->getValue($service),
            'property falsch'
        );
    }

    /**
     * @group unit
     */
    public function testGetElasticaCredentialsFromCredentialsStringForSingleServerConfiguration()
    {
        self::assertEquals(
            array(
                'servers' => array(
                    array(
                        'host' => 1,
                        'port' => 2,
                        'path' => 3,
                    ),
                ),
            ),
            $this->callInaccessibleMethod(
                tx_rnbase::makeInstance('tx_mksearch_service_engine_ElasticSearch'),
                'getElasticaCredentialsFromCredentialsString',
                'index;1,2,3,4'
            ),
            'Konfig falsch für Elastica gebaut'
        );
    }

    /**
     * @group unit
     */
    public function testGetElasticaCredentialsFromCredentialsStringIfTrailingSemikolon()
    {
        self::assertEquals(
            array(
                'servers' => array(
                    array(
                        'host' => 1,
                        'port' => 2,
                        'path' => 3,
                    ),
                ),
            ),
            $this->callInaccessibleMethod(
                tx_rnbase::makeInstance('tx_mksearch_service_engine_ElasticSearch'),
                'getElasticaCredentialsFromCredentialsString',
                'index;1,2,3,4;'
            ),
            'Konfig falsch für Elastica gebaut'
        );
    }

    /**
     * @group unit
     */
    public function testGetElasticaCredentialsFromCredentialsStringForClusterSetupConfiguration()
    {
        self::assertEquals(
            array(
                'servers' => array(
                    array(
                        'host' => 1,
                        'port' => 2,
                        'path' => 3,
                    ),
                    array(
                        'host' => 5,
                        'port' => 6,
                        'path' => 7,
                    ),
                ),
            ),
            $this->callInaccessibleMethod(
                tx_rnbase::makeInstance('tx_mksearch_service_engine_ElasticSearch'),
                'getElasticaCredentialsFromCredentialsString',
                'index;1,2,3,4;5,6,7,8'
            ),
            'Konfig falsch für Elastica gebaut'
        );
    }

    /**
     * @group unit
     */
    public function testOpenIndex()
    {
        $service = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            array(
                'getElasticaCredentialsFromCredentialsString',
                'initElasticSearchConnection',
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
    public function testGetIndex()
    {
        $service = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            array('openIndex')
        );
        $indexModelProperty = new ReflectionProperty(
            'tx_mksearch_service_engine_ElasticSearch',
            'mksearchIndexModel'
        );
        $indexModelProperty->setAccessible(true);
        $indexModel = tx_rnbase::makeInstance('tx_mksearch_model_internal_Index', array());
        $indexModelProperty->setValue($service, $indexModel);

        $service->expects($this->once())
            ->method('openIndex')
            ->with($indexModel);

        $index = $service->getIndex();
    }

    /**
     * @group unit
     */
    public function testGetIndexIfIndexAlreadySet()
    {
        $service = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            array('openIndex')
        );
        $indexProperty = new ReflectionProperty(
            'tx_mksearch_service_engine_ElasticSearch',
            'index'
        );
        $indexProperty->setAccessible(true);
        $indexProperty->setValue($service, new stdClass());

        $service->expects($this->never())
            ->method('openIndex');

        $service->getIndex();
    }

    /**
     * @group unit
     */
    public function testSetIndexModel()
    {
        $service = tx_rnbase::makeInstance('tx_mksearch_service_engine_ElasticSearch');

        $indexModel = tx_rnbase::makeInstance('tx_mksearch_model_internal_Index', array());
        $service->setIndexModel($indexModel);

        $indexModelProperty = new ReflectionProperty(
            'tx_mksearch_service_engine_ElasticSearch',
            'mksearchIndexModel'
        );
        $indexModelProperty->setAccessible(true);
        self::assertSame(
            $indexModel,
            $indexModelProperty->getValue($service),
            'indexModel falsch gesetzt'
        );
    }

    /**
     * @group unit
     */
    public function testGetStatusWhenServerIsAvailable()
    {
        $response = $this->getMock(
            'stdClass',
            array('getQueryTime')
        );
        $response->expects($this->once())
            ->method('getQueryTime')
            ->will($this->returnValue(123));

        $status = $this->getMock(
            'stdClass',
            array('getResponse')
        );
        $status->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($response));

        $client = $this->getMock(
            'stdClass',
            array('getStatus')
        );
        $client->expects($this->once())
            ->method('getStatus')
            ->will($this->returnValue($status));

        $index = $this->getMock(
            'stdClass',
            array('getClient')
        );
        $index->expects($this->once())
            ->method('getClient')
            ->will($this->returnValue($client));

        $service = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            array('getIndex', 'isServerAvailable')
        );

        $service->expects($this->once())
            ->method('getIndex')
            ->will($this->returnValue($index));

        $service->expects($this->once())
            ->method('isServerAvailable')
            ->will($this->returnValue(true));

        $status = $service->getStatus();

        self::assertEquals(1, $status->getStatus(), 'Status falsch');
        self::assertEquals(
            'Up and running (Ping time: 123 ms)',
            $status->getMessage(),
            'Message falsch'
        );
    }

    /**
     * @group unit
     */
    public function testGetStatusWhenServerIsNotAvailable()
    {
        $service = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            array('isServerAvailable')
        );
        $service->expects($this->once())
            ->method('isServerAvailable')
            ->will($this->returnValue(false));

        $status = $service->getStatus();

        self::assertEquals(-1, $status->getStatus(), 'Status falsch');
        self::assertEquals(
            'Down. Maybe not started?',
            $status->getMessage(),
            'Message falsch'
        );
    }

    /**
     * @group unit
     */
    public function testGetStatusWhenIsServerAvailableThrowsException()
    {
        $service = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            array('isServerAvailable')
        );
        $service->expects($this->once())
            ->method('isServerAvailable')
            ->will($this->throwException(
                tx_rnbase::makeInstance('Exception', 'Verbindung fehlgeschlagen')
            ));

        $credentialsStringProperty = new ReflectionProperty(
            'tx_mksearch_service_engine_ElasticSearch',
            'credentialsString'
        );
        $credentialsStringProperty->setAccessible(true);
        $credentialsStringProperty->setValue($service, '1,2,3,4');

        $status = $service->getStatus();

        self::assertEquals(-1, $status->getStatus(), 'Status falsch');
        self::assertEquals(
            'Error connecting ElasticSearch: Verbindung fehlgeschlagen. Credentials: 1,2,3,4',
            $status->getMessage(),
            'Message falsch'
        );
    }

    /**
     * @group unit
     */
    public function testIndexExists()
    {
        $status = $this->getMock(
            'stdClass',
            array('indexExists')
        );
        $status->expects($this->once())
            ->method('indexExists')
            ->with('indexName')
            ->will($this->returnValue(true));

        $client = $this->getMock(
            'stdClass',
            array('getStatus')
        );
        $client->expects($this->once())
            ->method('getStatus')
            ->will($this->returnValue($status));

        $index = $this->getMock(
            'stdClass',
            array('getClient')
        );
        $index->expects($this->once())
            ->method('getClient')
            ->will($this->returnValue($client));

        $service = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            array('getIndex')
        );
        $service->expects($this->once())
            ->method('getIndex')
            ->will($this->returnValue($index));

        self::assertTrue(
            $service->indexExists('indexName'),
            'falscher return Wert'
        );
    }

    /**
     * @group unit
     */
    public function testDeleteIndex()
    {
        $index = $this->getMock(
            'stdClass',
            array('delete')
        );
        $index->expects($this->once())
            ->method('delete');

        $service = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            array('getIndex')
        );
        $service->expects($this->once())
            ->method('getIndex')
            ->will($this->returnValue($index));

        $service->deleteIndex();
    }

    /**
     * @group unit
     */
    public function testDeleteIndexIfNameGiven()
    {
        $index = $this->getMock(
            'stdClass',
            array('delete', 'getClient')
        );
        $index->expects($this->once())
            ->method('delete');

        $client = $this->getMock(
            'stdClass',
            array('getIndex')
        );
        $client->expects($this->once())
            ->method('getIndex')
            ->with('indexName')
            ->will($this->returnValue($index));

        $index->expects($this->once())
            ->method('getClient')
            ->will($this->returnValue($client));

        $service = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            array('getIndex')
        );
        $service->expects($this->once())
            ->method('getIndex')
            ->will($this->returnValue($index));

        $service->deleteIndex('indexName');
    }

    /**
     * @group unit
     */
    public function testOptimizeIndex()
    {
        $index = $this->getMock(
            'stdClass',
            array('optimize')
        );
        $index->expects($this->once())
            ->method('optimize');

        $service = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            array('getIndex')
        );
        $service->expects($this->once())
            ->method('getIndex')
            ->will($this->returnValue($index));

        $service->optimizeIndex();
    }

    /**
     * @group unit
     */
    public function testMakeIndexDocInstance()
    {
        $service = tx_rnbase::makeInstance('tx_mksearch_service_engine_ElasticSearch');
        $docInstance = $service->makeIndexDocInstance('mksearch', 'tt_content');

        self::assertInstanceOf(
            'tx_mksearch_model_IndexerDocumentBase',
            $docInstance,
            'docInstance hat falsche Klasse'
        );

        $extKey = new ReflectionProperty(
            'tx_mksearch_model_IndexerDocumentBase',
            'extKey'
        );
        $extKey->setAccessible(true);
        self::assertEquals(
            'mksearch',
            $extKey->getValue($docInstance)->getValue(),
            'extKey falsch'
        );

        $contentType = new ReflectionProperty(
            'tx_mksearch_model_IndexerDocumentBase',
            'contentType'
        );
        $contentType->setAccessible(true);
        self::assertEquals(
            'tt_content',
            $contentType->getValue($docInstance)->getValue(),
            'contentType falsch'
        );

        $fieldClass = new ReflectionProperty(
            'tx_mksearch_model_IndexerDocumentBase',
            'fieldClass'
        );
        $fieldClass->setAccessible(true);
        self::assertEquals(
            'tx_mksearch_model_IndexerFieldBase',
            $fieldClass->getValue($docInstance),
            'fieldClass falsch'
        );
    }

    /**
     * @group unit
     */
    public function testIndexUpdateCallsIndexNew()
    {
        $service = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            array('indexNew')
        );
        $doc = tx_rnbase::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            'mksearch',
            'tt_content',
            'tx_mksearch_model_IndexerFieldBase'
        );
        $service->expects($this->once())
            ->method('indexNew')
            ->with($doc)
            ->will($this->returnValue(true));

        self::assertTrue($service->indexUpdate($doc));
    }

    /**
     * @group unit
     */
    public function testGetOpenIndexName()
    {
        $service = tx_rnbase::makeInstance('tx_mksearch_service_engine_ElasticSearch');
        $indexName = new ReflectionProperty(
            'tx_mksearch_service_engine_ElasticSearch',
            'indexName'
        );
        $indexName->setAccessible(true);
        $indexName->setValue($service, 'unknown');

        self::assertEquals('unknown', $service->getOpenIndexName());
    }

    /**
     * @group unit
     */
    public function testCloseIndex()
    {
        $index = $this->getMock(
            'stdClass',
            array('close')
        );
        $index->expects($this->once())
            ->method('close')
            ->will($this->returnValue(true));

        $service = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            array('getIndex')
        );
        $service->expects($this->once())
            ->method('getIndex')
            ->will($this->returnValue($index));

        $service->closeIndex();

        $indexProperty = new ReflectionProperty(
            'tx_mksearch_service_engine_ElasticSearch',
            'index'
        );
        $indexProperty->setAccessible(true);

        self::assertNull(
            $indexProperty->getValue($service),
            'index property nicht auf NULL gesetzt'
        );
    }

    /**
     * @group unit
     */
    public function testIndexNew()
    {
        /* @var $doc tx_mksearch_model_IndexerDocumentBase */
        $doc = tx_rnbase::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            'mksearch',
            'tt_content',
            'tx_mksearch_model_IndexerFieldBase'
        );
        $doc->setUid(123);
        $doc->addField('first_field', 'flat value');
        $doc->addField('second_field', array('multi', 'value'));

        $index = $this->getMock(
            'stdClass',
            array('addDocuments')
        );

        $elasticaDocument = new Elastica\Document(
            '123',
            array(
                'content_ident_s' => 'mksearch.tt_content',
                'first_field' => 'flat value',
                'second_field' => array('multi', 'value'),
                'extKey' => 'mksearch',
                'contentType' => 'tt_content',
                'uid' => 123,
            )
        );
        $elasticaDocument->setType('mksearch:tt_content');
        $response = $this->getMock(
            'stdClass',
            array('isOk')
        );
        $response->expects($this->once())
            ->method('isOk')
            ->will($this->returnValue(true));
        $index->expects($this->once())
            ->method('addDocuments')
            ->with(array($elasticaDocument))
            ->will($this->returnValue($response));

        $service = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            array('getIndex')
        );
        $service->expects($this->once())
            ->method('getIndex')
            ->will($this->returnValue($index));

        self::assertTrue($service->indexNew($doc));
    }

    /**
     * @group unit
     */
    public function testIndexDeleteByContentUid()
    {
        $index = $this->getMock(
            'stdClass',
            array('deleteDocuments')
        );

        $elasticaDocument = new Elastica\Document('123');
        $elasticaDocument->setType('mksearch:tt_content');
        $response = $this->getMock(
            'stdClass',
            array('isOk')
        );
        $response->expects($this->once())
            ->method('isOk')
            ->will($this->returnValue(true));
        $index->expects($this->once())
            ->method('deleteDocuments')
            ->with(array($elasticaDocument))
            ->will($this->returnValue($response));

        $service = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            array('getIndex')
        );
        $service->expects($this->once())
            ->method('getIndex')
            ->will($this->returnValue($index));

        self::assertTrue($service->indexDeleteByContentUid(
            123,
            'mksearch',
            'tt_content'
        ));
    }

    /**
     * @group unit
     * @expectedException RuntimeException
     * @expectedExceptionMessage ohoh
     */
    public function testSearchThrowsRuntimeExceptionIfElasticaThrowsException()
    {
        $exception = new Exception('ohoh');

        $service = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            array('getIndex')
        );
        $service->expects($this->once())
            ->method('getIndex')
            ->will($this->throwException($exception));

        $service->search();
    }

    /**
     * @group unit
     * @expectedException RuntimeException
     * @expectedExceptionMessage Exception caught from ElasticSearch: Error requesting ElasticSearch. HTTP status: 201; Path: pfad; Query: query
     */
    public function testSearchThrowsRuntimeExceptionIfResponseHttpStatusIsNot200()
    {
        $response = $this->getMock(
            'stdClass',
            array('getStatus')
        );
        $response->expects($this->once())
            ->method('getStatus')
            ->will($this->returnValue(201));

        $searchResult = $this->getMock(
            '\\Elastica\\ResultSet',
            array('getResponse'),
            array(),
            '',
            false
        );
        $searchResult->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($response));

        $index = $this->getMock(
            'stdClass',
            array('search', 'getClient')
        );
        $index->expects($this->once())
            ->method('search')
            ->will($this->returnValue($searchResult));

        $lastRequest = $this->getMock(
            'stdClass',
            array('getPath', 'getQuery', 'getData')
        );
        $lastRequest->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('pfad'));
        $lastRequest->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue('query'));
        $lastRequest->expects($this->once())
            ->method('getData')
            ->will($this->returnValue('data'));
        $client = $this->getMock(
            'stdClass',
            array('getLastRequest')
        );
        $client->expects($this->once())
            ->method('getLastRequest')
            ->will($this->returnValue($lastRequest));

        $index->expects($this->once())
            ->method('getClient')
            ->will($this->returnValue($client));

        $service = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            array('getIndex')
        );
        $service->expects($this->any())
            ->method('getIndex')
            ->will($this->returnValue($index));

        $service->search();
    }

    /**
     * @group unit
     */
    public function testGetElasticaQuery()
    {
        $service = tx_rnbase::makeInstance('tx_mksearch_service_engine_ElasticSearch');

        $fields['term'] = 'test term';
        $elasticaQuery = $this->callInaccessibleMethod($service, 'getElasticaQuery', $fields, array());
        $expectedQuery = Elastica\Query::create('test term');

        self::assertEquals($expectedQuery->getQuery(), $elasticaQuery->getQuery(), 'query falsch');
    }

    /**
     * @group unit
     */
    public function testGetElasticaQueryHandlesSorting()
    {
        $service = tx_rnbase::makeInstance('tx_mksearch_service_engine_ElasticSearch');

        $fields['term'] = 'test term';
        $elasticaQuery = $this->callInaccessibleMethod(
            $service,
            'getElasticaQuery',
            $fields,
            array('sort' => 'uid desc')
        );

        $expectedSort = array(0 => array('uid' => array('order' => 'desc')));

        self::assertEquals($expectedSort, $elasticaQuery->getParam('sort'), 'sort falsch');
    }

    /**
     * @group unit
     * @dataProvider getOptionsForElastica
     */
    public function testGetOptionsForElastica($initialOption, $expectedMappedOption)
    {
        $service = tx_rnbase::makeInstance('tx_mksearch_service_engine_ElasticSearch');

        $options = array($initialOption => 'test value');
        $mappedOptions = $this->callInaccessibleMethod(
            $service,
            'getOptionsForElastica',
            $options
        );

        $expectedMappedOptions = array();
        if (null !== $expectedMappedOption) {
            $expectedMappedOptions = array($expectedMappedOption => 'test value');
        }
        self::assertEquals(
            $expectedMappedOptions,
            $mappedOptions,
            'option '.$initialOption.' falsch gemapped'
        );
    }

    /**
     * @return multitype:multitype:string multitype:string NULL
     */
    public function getOptionsForElastica()
    {
        return array(
            array('search_type', 'search_type'),
            array('debug', 'explain'),
            array('limit', 'limit'),
            array('offset', 'from'),
            array('unknown', null),
        );
    }

    /**
     * @group unit
     */
    public function testGetItemsFromSearchResult()
    {
        $resultSetBuilder = new Elastica\ResultSet\DefaultBuilder();

        $searchResult = $resultSetBuilder->buildResultSet(
            new Elastica\Response(''),
            Elastica\Query::create('')
        );

        $resultProperty = new ReflectionProperty('\\Elastica\\ResultSet', '_results');
        $results = array(
            0 => new Elastica\Result(
                array(
                    '_index' => 'main',
                    '_type' => 'content',
                    '_id' => '5',
                    '_score' => '1',
                    '_source' => array('title' => 'hit data one'),
                )
            ),
            1 => new Elastica\Result(array('_source' => array('title' => 'hit data two'))),
        );
        $resultProperty->setAccessible(true);
        $resultProperty->setValue($searchResult, $results);

        $service = tx_rnbase::makeInstance('tx_mksearch_service_engine_ElasticSearch');

        $searchHits = $this->callInaccessibleMethod(
            $service,
            'getItemsFromSearchResult',
            $searchResult
        );

        $this->assertCount(2, $searchHits);
        $this->assertCount(5, $searchHits[0]->getRecord());
        $this->assertSame('hit data one', $searchHits[0]->getProperty('title'));
        $this->assertSame('main', $searchHits[0]->getProperty('index'));
        $this->assertSame('content', $searchHits[0]->getProperty('type'));
        $this->assertSame('5', $searchHits[0]->getProperty('id'));
        $this->assertSame('1', $searchHits[0]->getProperty('score'));
        $this->assertCount(1, $searchHits[1]->getRecord());
        $this->assertSame('hit data two', $searchHits[1]->getProperty('title'));
    }

    /**
     * @group unit
     */
    public function testSearchWithValidSearchResult()
    {
        $lastRequest = $this->getMock(
            'stdClass',
            array('getPath', 'getQuery', 'getData')
        );
        $lastRequest->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('pfad'));
        $lastRequest->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue('query'));
        $lastRequest->expects($this->once())
            ->method('getData')
            ->will($this->returnValue('data'));
        $client = $this->getMock(
            'stdClass',
            array('getLastRequest')
        );
        $client->expects($this->once())
            ->method('getLastRequest')
            ->will($this->returnValue($lastRequest));

        $index = $this->getMock(
            'stdClass',
            array('search', 'getClient')
        );
        $index->expects($this->once())
            ->method('getClient')
            ->will($this->returnValue($client));

        $service = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            array(
                'getIndex', 'getElasticaQuery', 'getOptionsForElastica',
                'checkResponseOfSearchResult', 'getItemsFromSearchResult',
            )
        );
        $service->expects($this->any())
            ->method('getIndex')
            ->will($this->returnValue($index));

        $fields = array('fields');
        $options = array('options');
        $service->expects($this->once())
            ->method('getElasticaQuery')
            ->with($fields, $options)
            ->will($this->returnValue('elastica query'));
        $service->expects($this->once())
            ->method('getOptionsForElastica')
            ->with($options)
            ->will($this->returnValue(123));

        $response = $this->getMock(
            '\\Elastica\\Response',
            ['getError'],
            [[
                'hits' => [
                    'total' => 123,
                ],
                'took' => 456,
            ]]
        );

        $response->expects($this->any())
            ->method('getError')
            ->will($this->returnValue('es gab einen Fehler'));

        $resultSetBuilder = new Elastica\ResultSet\DefaultBuilder();

        $searchResult = $resultSetBuilder->buildResultSet(
            $response,
            Elastica\Query::create('')
        );

        $index->expects($this->once())
            ->method('search')
            ->with('elastica query', 123)
            ->will($this->returnValue($searchResult));

        $service->expects($this->once())
            ->method('checkResponseOfSearchResult')
            ->with($searchResult);

        $service->expects($this->once())
            ->method('getItemsFromSearchResult')
            ->with($searchResult)
            ->will($this->returnValue(array('search results')));

        $result = $service->search($fields, $options);

        self::assertEquals(array('search results'), $result['items'], 'items falsch');
        self::assertEquals('pfad', $result['searchUrl'], 'searchUrl falsch');
        self::assertEquals('query', $result['searchQuery'], 'searchQuery falsch');
        self::assertEquals('data', $result['searchData'], 'searchQuery falsch');
        self::assertContains(
            ' ms',
            $result['searchTime'],
            'searchTime enthält nicht die Einheit'
        );
        self::assertNotNull(
            str_replace('ms', '', $result['searchTime']),
            'searchTime enthält keine Millisekunden Angabe'
        );
        self::assertEquals('456 ms', $result['queryTime'], 'queryTime falsch');
        self::assertEquals(123, $result['numFound'], 'searchQuery falsch');
        self::assertEquals(
            'es gab einen Fehler',
            $result['error'],
            'error falsch'
        );
    }

    /**
     * @group unit
     */
    public function testSearchPrintsNoDebugIfNotSetInOptions()
    {
        $this->expectOutputString('');

        $lastRequest = $this->getMock(
            'stdClass',
            array('getPath', 'getQuery', 'getData')
        );
        $client = $this->getMock(
            'stdClass',
            array('getLastRequest')
        );
        $client->expects($this->once())
            ->method('getLastRequest')
            ->will($this->returnValue($lastRequest));

        $index = $this->getMock(
            'stdClass',
            array('search', 'getClient')
        );
        $index->expects($this->once())
            ->method('getClient')
            ->will($this->returnValue($client));

        $service = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            array(
                'getIndex', 'getElasticaQuery', 'getOptionsForElastica',
                'checkResponseOfSearchResult', 'getItemsFromSearchResult',
            )
        );
        $service->expects($this->any())
            ->method('getIndex')
            ->will($this->returnValue($index));

        $fields = array('fields');
        $options = array('options');

        $resultSetBuilder = new Elastica\ResultSet\DefaultBuilder();

        $searchResult = $resultSetBuilder->buildResultSet(
            new Elastica\Response(''),
            Elastica\Query::create('')
        );

        $index->expects($this->once())
            ->method('search')
            ->will($this->returnValue($searchResult));

        $service->search($fields, $options);
    }

    /**
     * @group unit
     */
    public function testSearchPrintsDebugIfSetInOptions()
    {
        $this->markTestIncomplete(
            'The output is version dependent. Refactoring needet.'
        );
        // es reicht zu prüfen ob einige Teile des Debug vorhanden sind
        // "s" modifier, damit auf der CLI alle Zeilen in Betracht gezogen werden. Sonst
        // wird nur die Zeile genommen, mit dem ersten Treffer.
        $regularExpression = '/.*(debug.+=>.+TRUE).*/s';
        $this->expectOutputRegex($regularExpression);

        $lastRequest = $this->getMock(
            'stdClass',
            array('getPath', 'getQuery', 'getData')
        );
        $client = $this->getMock(
            'stdClass',
            array('getLastRequest')
        );
        $client->expects($this->once())
            ->method('getLastRequest')
            ->will($this->returnValue($lastRequest));

        $index = $this->getMock(
            'stdClass',
            array('search', 'getClient')
        );
        $index->expects($this->once())
            ->method('getClient')
            ->will($this->returnValue($client));

        $service = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            array(
                'getIndex', 'getElasticaQuery', 'getOptionsForElastica',
                'checkResponseOfSearchResult', 'getItemsFromSearchResult',
            )
        );
        $service->expects($this->any())
            ->method('getIndex')
            ->will($this->returnValue($index));

        $fields = array('fields');
        $options = array('debug' => true);

        $resultSetBuilder = new Elastica\ResultSet\DefaultBuilder();

        $searchResult = $resultSetBuilder->buildResultSet(
            new Elastica\Response(''),
            Elastica\Query::create('')
        );

        $index->expects($this->once())
            ->method('search')
            ->will($this->returnValue($searchResult));

        $service->search($fields, $options);
    }
}
