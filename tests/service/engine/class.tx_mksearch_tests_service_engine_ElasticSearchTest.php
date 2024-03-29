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

/**
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_service_engine_ElasticSearchTest extends tx_mksearch_tests_Testcase
{
    protected function setUp(): void
    {
        self::markTestSkipped('Loading of the Elastica library doesn\'t work since using the TYPOe testing framework because the PackageManager is not available.');
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['mksearch']['useInternalElasticaLib'] = 1;
    }

    /**
     * @group unit
     */
    public function testGetElasticIndex()
    {
        $service = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch');
        $indexName = new ReflectionProperty(
            'tx_mksearch_service_engine_ElasticSearch',
            'indexName'
        );
        $indexName->setAccessible(true);
        $indexName->setValue($service, 'unknown');
        $elasticaClient = $this->callInaccessibleMethod(
            $service,
            'getElasticaIndex',
            []
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
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch'),
            'getLogger'
        );

        self::assertEquals(
            \Sys25\RnBase\Utility\Logger::class,
            $loggerClass,
            'falsche Logger-Klasse'
        );
    }

    /**
     * @group unit
     *
     * @dataProvider getServerStatus
     */
    public function testIsServerAvailable($returnCode, $expectedReturn)
    {
        $response = $this->getMockBuilder('stdClass')
            ->addMethods(['getStatus'])
            ->getMock();
        $response->expects($this->once())
            ->method('getStatus')
            ->will($this->returnValue($returnCode));

        $status = $this->getMockBuilder('stdClass')
            ->addMethods(['getResponse'])
            ->getMock();
        $status->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($response));

        $client = $this->getMockBuilder('stdClass')
            ->addMethods(['getStatus'])
            ->getMock();
        $client->expects($this->once())
            ->method('getStatus')
            ->will($this->returnValue($status));

        $index = $this->getMockBuilder('stdClass')
            ->addMethods(['getClient'])
            ->getMock();
        $index->expects($this->once())
            ->method('getClient')
            ->will($this->returnValue($client));

        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getIndex'])
            ->getMock();
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
        return [
            [200, true],
            [300, false],
        ];
    }

    /**
     * @group unit
     */
    public function testInitElasticSearchConnectionSetsIndexPropertyCreatesIndexIfNotExistsAndOpensIndex()
    {
        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getElasticaIndex', 'isServerAvailable', 'getLogger'])
            ->getMock();

        $service->expects($this->once())
            ->method('isServerAvailable')
            ->will($this->returnValue(true));

        $credentials = ['someCredentials'];
        $index = $this->getMock('stdClass', ['open', 'exists', 'create']);
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
        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getElasticaIndex', 'isServerAvailable', 'getLogger'])
            ->getMock();

        $service->expects($this->once())
            ->method('isServerAvailable')
            ->will($this->returnValue(true));

        $credentials = ['someCredentials'];
        $index = $this->getMock('stdClass', ['open', 'exists', 'create']);
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
        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getElasticaIndex', 'isServerAvailable', 'getLogger'])
            ->getMock();

        $index = $this->getMock('stdClass', ['open', 'exists', 'create']);
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
            []
        );
    }

    /**
     * @group unit
     */
    public function testInitElasticSearchConnectionThrowsExceptionAndLogsErrorIfServerNotAvailable()
    {
        $this->expectException(\Elastica\Exception\ClientException::class);
        $this->expectExceptionMessage('ElasticSearch service not responding.');

        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getElasticaIndex', 'isServerAvailable', 'getLogger'])
            ->getMock();

        $credentials = ['someCredentials'];
        $logger = $this->getMock('stdClass', ['fatal']);
        $logger->expects($this->once())
            ->method('fatal')
            ->with(
                'ElasticSearch service not responding.',
                'mksearch',
                [$credentials]
            );

        $service->expects($this->once())
            ->method('getLogger')
            ->will($this->returnValue($logger));

        $service->expects($this->once())
            ->method('isServerAvailable')
            ->will($this->returnValue(false));

        $index = $this->getMock('stdClass', ['open', 'exists', 'create']);
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
        $service = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch');
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
        $service = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch');
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
            [
                'servers' => [
                    [
                        'host' => 1,
                        'port' => 2,
                        'path' => 3,
                    ],
                ],
            ],
            $this->callInaccessibleMethod(
                \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch'),
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
            [
                'servers' => [
                    [
                        'host' => 1,
                        'port' => 2,
                        'path' => 3,
                    ],
                ],
            ],
            $this->callInaccessibleMethod(
                \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch'),
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
            [
                'servers' => [
                    [
                        'host' => 1,
                        'port' => 2,
                        'path' => 3,
                    ],
                    [
                        'host' => 5,
                        'port' => 6,
                        'path' => 7,
                    ],
                ],
            ],
            $this->callInaccessibleMethod(
                \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch'),
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
        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(
                [
                    'getElasticaCredentialsFromCredentialsString',
                    'initElasticSearchConnection',
                ]
            )
            ->getMock();
        $index = $this->getMock(
            'tx_mksearch_model_internal_Index',
            ['getCredentialString'],
            [[]]
        );
        $index->expects($this->once())
            ->method('getCredentialString')
            ->will($this->returnValue('credentialsString'));

        $service->expects($this->once())
            ->method('getElasticaCredentialsFromCredentialsString')
            ->with('credentialsString')
            ->will($this->returnValue(['credentialsForElastica']));

        $service->expects($this->once())
            ->method('initElasticSearchConnection')
            ->with(['credentialsForElastica']);

        $service->openIndex($index);
    }

    /**
     * @group unit
     */
    public function testGetIndex()
    {
        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['openIndex'])
            ->getMock();
        $indexModelProperty = new ReflectionProperty(
            'tx_mksearch_service_engine_ElasticSearch',
            'mksearchIndexModel'
        );
        $indexModelProperty->setAccessible(true);
        $indexModel = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_internal_Index', []);
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
        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['openIndex'])
            ->getMock();
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
        $service = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch');

        $indexModel = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_internal_Index', []);
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
        $response = $this->getMockBuilder('stdClass')
            ->addMethods(['getQueryTime'])
            ->getMock();
        $response->expects($this->once())
            ->method('getQueryTime')
            ->will($this->returnValue(123));

        $status = $this->getMockBuilder('stdClass')
            ->addMethods(['getResponse'])
            ->getMock();
        $status->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($response));

        $client = $this->getMockBuilder('stdClass')
            ->addMethods(['getStatus'])
            ->getMock();
        $client->expects($this->once())
            ->method('getStatus')
            ->will($this->returnValue($status));

        $index = $this->getMockBuilder('stdClass')
            ->addMethods(['getClient'])
            ->getMock();
        $index->expects($this->once())
            ->method('getClient')
            ->will($this->returnValue($client));

        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getIndex', 'isServerAvailable'])
            ->getMock();

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
        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['isServerAvailable'])
            ->getMock();
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
        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['isServerAvailable'])
            ->getMock();
        $service->expects($this->once())
            ->method('isServerAvailable')
            ->will($this->throwException(
                \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Exception', 'Verbindung fehlgeschlagen')
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
        $status = $this->getMockBuilder('stdClass')
            ->addMethods(['indexExists'])
            ->getMock();
        $status->expects($this->once())
            ->method('indexExists')
            ->with('indexName')
            ->will($this->returnValue(true));

        $client = $this->getMockBuilder('stdClass')
            ->addMethods(['getStatus'])
            ->getMock();
        $client->expects($this->once())
            ->method('getStatus')
            ->will($this->returnValue($status));

        $index = $this->getMockBuilder('stdClass')
            ->addMethods(['getClient'])
            ->getMock();
        $index->expects($this->once())
            ->method('getClient')
            ->will($this->returnValue($client));

        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getIndex'])
            ->getMock();
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
        $index = $this->getMockBuilder('stdClass')
            ->addMethods(['delete'])
            ->getMock();
        $index->expects($this->once())
            ->method('delete');

        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getIndex'])
            ->getMock();
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
        $index = $this->getMockBuilder('stdClass')
            ->addMethods(['delete', 'getClient'])
            ->getMock();
        $index->expects($this->once())
            ->method('delete');

        $client = $this->getMockBuilder('stdClass')
            ->addMethods(['getIndex'])
            ->getMock();
        $client->expects($this->once())
            ->method('getIndex')
            ->with('indexName')
            ->will($this->returnValue($index));

        $index->expects($this->once())
            ->method('getClient')
            ->will($this->returnValue($client));

        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getIndex'])
            ->getMock();
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
        $index = $this->getMockBuilder('stdClass')
            ->addMethods(['optimize'])
            ->getMock();
        $index->expects($this->once())
            ->method('optimize');

        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getIndex'])
            ->getMock();
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
        $service = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch');
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
        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['indexNew'])
            ->getMock();
        $doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
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
        $service = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch');
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
        $index = $this->getMockBuilder('stdClass')
            ->addMethods(['close'])
            ->getMock();
        $index->expects($this->once())
            ->method('close')
            ->will($this->returnValue(true));

        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getIndex'])
            ->getMock();
        $service->expects($this->once())
            ->method('getIndex')
            ->will($this->returnValue($index));

        $service->closeIndex();

        self::assertObjectNotHasAttribute(
            'index',
            $service,
            'index property nicht auf NULL gesetzt'
        );
    }

    /**
     * @group unit
     */
    public function testIndexNew()
    {
        /* @var $doc tx_mksearch_model_IndexerDocumentBase */
        $doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            'mksearch',
            'tt_content',
            'tx_mksearch_model_IndexerFieldBase'
        );
        $doc->setUid(123);
        $doc->addField('first_field', 'flat value');
        $doc->addField('second_field', ['multi', 'value']);

        $index = $this->getMockBuilder('stdClass')
            ->addMethods(['addDocuments'])
            ->getMock();

        $elasticaDocument = new Elastica\Document(
            '123',
            [
                'content_ident_s' => 'mksearch.tt_content',
                'first_field' => 'flat value',
                'second_field' => ['multi', 'value'],
                'extKey' => 'mksearch',
                'contentType' => 'tt_content',
                'uid' => 123,
            ]
        );
        $elasticaDocument->setType('mksearch:tt_content');
        $response = $this->getMockBuilder('stdClass')
            ->addMethods(['isOk'])
            ->getMock();
        $response->expects($this->once())
            ->method('isOk')
            ->will($this->returnValue(true));
        $index->expects($this->once())
            ->method('addDocuments')
            ->with([$elasticaDocument])
            ->will($this->returnValue($response));

        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getIndex'])
            ->getMock();
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
        $index = $this->getMockBuilder('stdClass')
            ->addMethods(['deleteDocuments'])
            ->getMock();

        $elasticaDocument = new Elastica\Document('123');
        $elasticaDocument->setType('mksearch:tt_content');
        $response = $this->getMockBuilder('stdClass')
            ->addMethods(['isOk'])
            ->getMock();
        $response->expects($this->once())
            ->method('isOk')
            ->will($this->returnValue(true));
        $index->expects($this->once())
            ->method('deleteDocuments')
            ->with([$elasticaDocument])
            ->will($this->returnValue($response));

        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getIndex'])
            ->getMock();
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
     */
    public function testSearchThrowsRuntimeExceptionIfElasticaThrowsException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ohoh');

        $exception = new Exception('ohoh');

        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getIndex'])
            ->disableOriginalConstructor()
            ->getMock();
        $service->expects($this->once())
            ->method('getIndex')
            ->will($this->throwException($exception));

        $service->search();
    }

    /**
     * @group unit
     */
    public function testSearchThrowsRuntimeExceptionIfResponseHttpStatusIsNot200()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Exception caught from ElasticSearch: Error requesting ElasticSearch. HTTP status: 201; Path: pfad; Query: query');
        $response = $this->getMockBuilder('stdClass')
            ->addMethods(['getIndex'])
            ->getMock();
        $response->expects($this->once())
            ->method('getStatus')
            ->will($this->returnValue(201));

        $searchResult = $this->getMockBuilder('\\Elastica\\ResultSet')
            ->onlyMethods(['getResponse'])
            ->disableOriginalConstructor()
            ->getMock();
        $searchResult->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($response));

        $index = $this->getMockBuilder('stdClass')
            ->addMethods(['search', 'getClient'])
            ->getMock();
        $index->expects($this->once())
            ->method('search')
            ->will($this->returnValue($searchResult));

        $lastRequest = $this->getMockBuilder('stdClass')
            ->addMethods(['getPath', 'getQuery', 'getData'])
            ->getMock();
        $lastRequest->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('pfad'));
        $lastRequest->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue('query'));
        $lastRequest->expects($this->once())
            ->method('getData')
            ->will($this->returnValue('data'));
        $client = $this->getMockBuilder('stdClass')
            ->addMethods(['getLastRequest'])
            ->getMock();
        $client->expects($this->once())
            ->method('getLastRequest')
            ->will($this->returnValue($lastRequest));

        $index->expects($this->once())
            ->method('getClient')
            ->will($this->returnValue($client));

        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getIndex'])
            ->getMock();
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
        $service = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch');

        $fields['term'] = 'test term';
        $elasticaQuery = $this->callInaccessibleMethod($service, 'getElasticaQuery', $fields, []);
        $expectedQuery = Elastica\Query::create('test term');

        self::assertEquals($expectedQuery->getQuery(), $elasticaQuery->getQuery(), 'query falsch');
    }

    /**
     * @group unit
     */
    public function testGetElasticaQueryHandlesSorting()
    {
        $service = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch');

        $fields['term'] = 'test term';
        $elasticaQuery = $this->callInaccessibleMethod(
            $service,
            'getElasticaQuery',
            $fields,
            ['sort' => 'uid desc']
        );

        $expectedSort = [0 => ['uid' => ['order' => 'desc']]];

        self::assertEquals($expectedSort, $elasticaQuery->getParam('sort'), 'sort falsch');
    }

    /**
     * @group unit
     *
     * @dataProvider getOptionsForElastica
     */
    public function testGetOptionsForElastica($initialOption, $expectedMappedOption)
    {
        $service = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch');

        $options = [$initialOption => 'test value'];
        $mappedOptions = $this->callInaccessibleMethod(
            $service,
            'getOptionsForElastica',
            $options
        );

        $expectedMappedOptions = [];
        if (null !== $expectedMappedOption) {
            $expectedMappedOptions = [$expectedMappedOption => 'test value'];
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
        return [
            ['search_type', 'search_type'],
            ['debug', 'explain'],
            ['limit', 'limit'],
            ['offset', 'from'],
            ['unknown', null],
        ];
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
        $results = [
            0 => new Elastica\Result(
                [
                    '_index' => 'main',
                    '_type' => 'content',
                    '_id' => '5',
                    '_score' => '1',
                    '_source' => ['title' => 'hit data one'],
                ]
            ),
            1 => new Elastica\Result(['_source' => ['title' => 'hit data two']]),
        ];
        $resultProperty->setAccessible(true);
        $resultProperty->setValue($searchResult, $results);

        $service = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch');

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
        $lastRequest = $this->getMockBuilder('stdClass')
            ->addMethods(['getPath', 'getQuery', 'getData'])
            ->getMock();
        $lastRequest->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('pfad'));
        $lastRequest->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue('query'));
        $lastRequest->expects($this->once())
            ->method('getData')
            ->will($this->returnValue('data'));
        $client = $this->getMockBuilder('stdClass')
            ->addMethods(['getLastRequest'])
            ->getMock();
        $client->expects($this->once())
            ->method('getLastRequest')
            ->will($this->returnValue($lastRequest));

        $index = $this->getMockBuilder('stdClass')
            ->addMethods(['search', 'getClient'])
            ->getMock();
        $index->expects($this->once())
            ->method('getClient')
            ->will($this->returnValue($client));

        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(
                [
                    'getIndex', 'getElasticaQuery', 'getOptionsForElastica',
                    'checkResponseOfSearchResult', 'getItemsFromSearchResult',
                ]
            )
            ->getMock();
        $service->expects($this->any())
            ->method('getIndex')
            ->will($this->returnValue($index));

        $fields = ['fields'];
        $options = ['options'];
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
            ->will($this->returnValue(['search results']));

        $result = $service->search($fields, $options);

        self::assertEquals(['search results'], $result['items'], 'items falsch');
        self::assertEquals('pfad', $result['searchUrl'], 'searchUrl falsch');
        self::assertEquals('query', $result['searchQuery'], 'searchQuery falsch');
        self::assertEquals('data', $result['searchData'], 'searchQuery falsch');
        self::assertStringContainsString(
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

        $lastRequest = $this->getMockBuilder('stdClass')
            ->addMethods(['getPath', 'getQuery', 'getData'])
            ->getMock();
        $client = $this->getMockBuilder('stdClass')
            ->addMethods(['getLastRequest'])
            ->getMock();
        $client->expects($this->once())
            ->method('getLastRequest')
            ->will($this->returnValue($lastRequest));

        $index = $this->getMockBuilder('stdClass')
            ->addMethods(['search', 'getClient'])
            ->getMock();
        $index->expects($this->once())
            ->method('getClient')
            ->will($this->returnValue($client));

        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(
                [
                    'getIndex', 'getElasticaQuery', 'getOptionsForElastica',
                    'checkResponseOfSearchResult', 'getItemsFromSearchResult',
                ]
            )
            ->getMock();
        $service->expects($this->any())
            ->method('getIndex')
            ->will($this->returnValue($index));

        $fields = ['fields'];
        $options = ['options'];

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
        // "s" modifier, damit auf der CLI alle Zeilen in Betracht gezogen werden. Sonst
        // wird nur die Zeile genommen, mit dem ersten Treffer.
        $regularExpression = '/.*(debug.+=>.+TRUE).*/s';
        $this->expectOutputRegex($regularExpression);

        $lastRequest = $this->getMockBuilder('stdClass')
            ->addMethods(['getPath', 'getQuery', 'getData'])
            ->getMock();
        $client = $this->getMockBuilder('stdClass')
            ->addMethods(['getLastRequest'])
            ->getMock();
        $client->expects($this->once())
            ->method('getLastRequest')
            ->will($this->returnValue($lastRequest));

        $index = $this->getMockBuilder('stdClass')
            ->addMethods(['search', 'getClient'])
            ->getMock();
        $index->expects($this->once())
            ->method('getClient')
            ->will($this->returnValue($client));

        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(
                [
                    'getIndex', 'getElasticaQuery', 'getOptionsForElastica',
                    'checkResponseOfSearchResult', 'getItemsFromSearchResult',
                ]
            )
            ->getMock();
        $service->expects($this->any())
            ->method('getIndex')
            ->will($this->returnValue($index));

        $fields = ['fields'];
        $options = ['debug' => true];

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
