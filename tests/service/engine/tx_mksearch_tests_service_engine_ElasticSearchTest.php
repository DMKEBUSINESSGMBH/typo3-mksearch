<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of the "mksearch" Extension for TYPO3 CMS.
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GNU Lesser General Public License can be found at
 * www.gnu.org/licenses/lgpl.html
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
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
        self::markTestSkipped("Loading of the Elastica library doesn't work since using the TYPOe testing framework because the PackageManager is not available.");
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['mksearch']['useInternalElasticaLib'] = 1;
    }

    public function testGetElasticIndex(): void
    {
        $service = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch');
        $indexName = new ReflectionProperty(
            'tx_mksearch_service_engine_ElasticSearch',
            'indexName'
        );
        $indexName->setValue($service, 'unknown');

        $elasticaClient = $this->callInaccessibleMethod(
            $service,
            'getElasticaIndex',
            []
        );

        self::assertInstanceOf(
            Elastica\Index::class,
            $elasticaClient,
            'Client hat falsche Klasse'
        );
    }

    public function testGetLogger(): void
    {
        $loggerClass = $this->callInaccessibleMethod(
            TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch'),
            'getLogger'
        );

        self::assertEquals(
            Sys25\RnBase\Utility\Logger::class,
            $loggerClass,
            'falsche Logger-Klasse'
        );
    }

    #[PHPUnit\Framework\Attributes\DataProvider('getServerStatus')]
    public function testIsServerAvailable(int $returnCode, bool $expectedReturn): void
    {
        $response = $this->getMockBuilder('stdClass')
            ->addMethods(['getStatus'])
            ->getMock();
        $response->expects($this->once())
            ->method('getStatus')
            ->willReturn($returnCode);

        $status = $this->getMockBuilder('stdClass')
            ->addMethods(['getResponse'])
            ->getMock();
        $status->expects($this->once())
            ->method('getResponse')
            ->willReturn($response);

        $client = $this->getMockBuilder('stdClass')
            ->addMethods(['getStatus'])
            ->getMock();
        $client->expects($this->once())
            ->method('getStatus')
            ->willReturn($status);

        $index = $this->getMockBuilder('stdClass')
            ->addMethods(['getClient'])
            ->getMock();
        $index->expects($this->once())
            ->method('getClient')
            ->willReturn($client);

        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getIndex'])
            ->getMock();
        $service->expects($this->once())
            ->method('getIndex')
            ->willReturn($index);

        self::assertEquals(
            $expectedReturn,
            $this->callInaccessibleMethod($service, 'isServerAvailable'),
            'falscher return Wert'
        );
    }

    /**
     * @return multitype:multitype:number boolean
     */
    public static function getServerStatus(): array
    {
        return [
            [200, true],
            [300, false],
        ];
    }

    public function testInitElasticSearchConnectionSetsIndexPropertyCreatesIndexIfNotExistsAndOpensIndex(): void
    {
        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getElasticaIndex', 'isServerAvailable', 'getLogger'])
            ->getMock();

        $service->expects($this->once())
            ->method('isServerAvailable')
            ->willReturn(true);

        $credentials = ['someCredentials'];
        $index = $this->getMock('stdClass', ['open', 'exists', 'create']);
        $index->expects($this->once())
            ->method('open');
        $index->expects($this->once())
            ->method('exists')
            ->willReturn(true);
        $index->expects($this->never())
            ->method('create');

        $service->expects($this->once())
            ->method('getElasticaIndex')
            ->with($credentials)
            ->willReturn($index);

        $indexProperty = new ReflectionProperty(
            'tx_mksearch_service_engine_ElasticSearch',
            'index'
        );

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

    public function testInitElasticSearchConnectionSetsIndexPropertyCreatesIndexNotIfExistsAndOpensIndex(): void
    {
        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getElasticaIndex', 'isServerAvailable', 'getLogger'])
            ->getMock();

        $service->expects($this->once())
            ->method('isServerAvailable')
            ->willReturn(true);

        $credentials = ['someCredentials'];
        $index = $this->getMock('stdClass', ['open', 'exists', 'create']);
        $index->expects($this->once())
            ->method('open');
        $index->expects($this->once())
            ->method('exists')
            ->willReturn(false);
        $index->expects($this->once())
            ->method('create');

        $service->expects($this->once())
            ->method('getElasticaIndex')
            ->with($credentials)
            ->willReturn($index);

        $indexProperty = new ReflectionProperty(
            'tx_mksearch_service_engine_ElasticSearch',
            'index'
        );

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

    public function testInitElasticSearchConnectionCallsLoggerNotIfServerAvailable(): void
    {
        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getElasticaIndex', 'isServerAvailable', 'getLogger'])
            ->getMock();

        $index = $this->getMock('stdClass', ['open', 'exists', 'create']);
        $service->expects($this->once())
            ->method('getElasticaIndex')
            ->willReturn($index);

        $service->expects($this->never())
            ->method('getLogger');

        $service->expects($this->once())
            ->method('isServerAvailable')
            ->willReturn(true);

        $this->callInaccessibleMethod(
            $service,
            'initElasticSearchConnection',
            []
        );
    }

    public function testInitElasticSearchConnectionThrowsExceptionAndLogsErrorIfServerNotAvailable(): void
    {
        $this->expectException(Elastica\Exception\ClientException::class);
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
            ->willReturn($logger);

        $service->expects($this->once())
            ->method('isServerAvailable')
            ->willReturn(false);

        $index = $this->getMock('stdClass', ['open', 'exists', 'create']);
        $service->expects($this->once())
            ->method('getElasticaIndex')
            ->willReturn($index);

        $this->callInaccessibleMethod(
            $service,
            'initElasticSearchConnection',
            $credentials
        );
    }

    public function testGetElasticaCredentialsFromCredentialsStringSetsInitialCredentialsStringToProperty(): void
    {
        $service = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch');
        $this->callInaccessibleMethod(
            $service,
            'getElasticaCredentialsFromCredentialsString',
            'index;1,2,3,4'
        );
        $credentialsStringProperty = new ReflectionProperty(
            'tx_mksearch_service_engine_ElasticSearch',
            'credentialsString'
        );
        self::assertEquals(
            'index;1,2,3,4',
            $credentialsStringProperty->getValue($service),
            'property falsch'
        );
    }

    public function testGetElasticaCredentialsFromCredentialsStringSetsIndexNameProperty(): void
    {
        $service = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch');
        $this->callInaccessibleMethod(
            $service,
            'getElasticaCredentialsFromCredentialsString',
            'index;1,2,3,4'
        );
        $indexNameProperty = new ReflectionProperty(
            'tx_mksearch_service_engine_ElasticSearch',
            'indexName'
        );
        self::assertEquals(
            'index',
            $indexNameProperty->getValue($service),
            'property falsch'
        );
    }

    public function testGetElasticaCredentialsFromCredentialsStringForSingleServerConfiguration(): void
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
                TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch'),
                'getElasticaCredentialsFromCredentialsString',
                'index;1,2,3,4'
            ),
            'Konfig falsch für Elastica gebaut'
        );
    }

    public function testGetElasticaCredentialsFromCredentialsStringIfTrailingSemikolon(): void
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
                TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch'),
                'getElasticaCredentialsFromCredentialsString',
                'index;1,2,3,4;'
            ),
            'Konfig falsch für Elastica gebaut'
        );
    }

    public function testGetElasticaCredentialsFromCredentialsStringForClusterSetupConfiguration(): void
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
                TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch'),
                'getElasticaCredentialsFromCredentialsString',
                'index;1,2,3,4;5,6,7,8'
            ),
            'Konfig falsch für Elastica gebaut'
        );
    }

    public function testOpenIndex(): void
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
            ->willReturn('credentialsString');

        $service->expects($this->once())
            ->method('getElasticaCredentialsFromCredentialsString')
            ->with('credentialsString')
            ->willReturn(['credentialsForElastica']);

        $service->expects($this->once())
            ->method('initElasticSearchConnection')
            ->with(['credentialsForElastica']);

        $service->openIndex($index);
    }

    public function testGetIndex(): void
    {
        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['openIndex'])
            ->getMock();
        $indexModelProperty = new ReflectionProperty(
            'tx_mksearch_service_engine_ElasticSearch',
            'mksearchIndexModel'
        );

        $indexModel = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_internal_Index', []);
        $indexModelProperty->setValue($service, $indexModel);

        $service->expects($this->once())
            ->method('openIndex')
            ->with($indexModel);

        $service->getIndex();
    }

    public function testGetIndexIfIndexAlreadySet(): void
    {
        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['openIndex'])
            ->getMock();
        $indexProperty = new ReflectionProperty(
            'tx_mksearch_service_engine_ElasticSearch',
            'index'
        );
        $indexProperty->setValue($service, new stdClass());

        $service->expects($this->never())
            ->method('openIndex');

        $service->getIndex();
    }

    public function testSetIndexModel(): void
    {
        $service = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch');

        $indexModel = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_internal_Index', []);
        $service->setIndexModel($indexModel);

        $indexModelProperty = new ReflectionProperty(
            'tx_mksearch_service_engine_ElasticSearch',
            'mksearchIndexModel'
        );
        self::assertSame(
            $indexModel,
            $indexModelProperty->getValue($service),
            'indexModel falsch gesetzt'
        );
    }

    public function testGetStatusWhenServerIsAvailable(): void
    {
        $response = $this->getMockBuilder('stdClass')
            ->addMethods(['getQueryTime'])
            ->getMock();
        $response->expects($this->once())
            ->method('getQueryTime')
            ->willReturn(123);

        $status = $this->getMockBuilder('stdClass')
            ->addMethods(['getResponse'])
            ->getMock();
        $status->expects($this->once())
            ->method('getResponse')
            ->willReturn($response);

        $client = $this->getMockBuilder('stdClass')
            ->addMethods(['getStatus'])
            ->getMock();
        $client->expects($this->once())
            ->method('getStatus')
            ->willReturn($status);

        $index = $this->getMockBuilder('stdClass')
            ->addMethods(['getClient'])
            ->getMock();
        $index->expects($this->once())
            ->method('getClient')
            ->willReturn($client);

        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getIndex', 'isServerAvailable'])
            ->getMock();

        $service->expects($this->once())
            ->method('getIndex')
            ->willReturn($index);

        $service->expects($this->once())
            ->method('isServerAvailable')
            ->willReturn(true);

        $status = $service->getStatus();

        self::assertEquals(1, $status->getStatus(), 'Status falsch');
        self::assertEquals(
            'Up and running (Ping time: 123 ms)',
            $status->getMessage(),
            'Message falsch'
        );
    }

    public function testGetStatusWhenServerIsNotAvailable(): void
    {
        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['isServerAvailable'])
            ->getMock();
        $service->expects($this->once())
            ->method('isServerAvailable')
            ->willReturn(false);

        $status = $service->getStatus();

        self::assertEquals(-1, $status->getStatus(), 'Status falsch');
        self::assertEquals(
            'Down. Maybe not started?',
            $status->getMessage(),
            'Message falsch'
        );
    }

    public function testGetStatusWhenIsServerAvailableThrowsException(): void
    {
        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['isServerAvailable'])
            ->getMock();
        $service->expects($this->once())
            ->method('isServerAvailable')
            ->will($this->throwException(
                TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Exception', 'Verbindung fehlgeschlagen')
            ));

        $credentialsStringProperty = new ReflectionProperty(
            'tx_mksearch_service_engine_ElasticSearch',
            'credentialsString'
        );
        $credentialsStringProperty->setValue($service, '1,2,3,4');

        $status = $service->getStatus();

        self::assertEquals(-1, $status->getStatus(), 'Status falsch');
        self::assertEquals(
            'Error connecting ElasticSearch: Verbindung fehlgeschlagen. Credentials: 1,2,3,4',
            $status->getMessage(),
            'Message falsch'
        );
    }

    public function testIndexExists(): void
    {
        $status = $this->getMockBuilder('stdClass')
            ->addMethods(['indexExists'])
            ->getMock();
        $status->expects($this->once())
            ->method('indexExists')
            ->with('indexName')
            ->willReturn(true);

        $client = $this->getMockBuilder('stdClass')
            ->addMethods(['getStatus'])
            ->getMock();
        $client->expects($this->once())
            ->method('getStatus')
            ->willReturn($status);

        $index = $this->getMockBuilder('stdClass')
            ->addMethods(['getClient'])
            ->getMock();
        $index->expects($this->once())
            ->method('getClient')
            ->willReturn($client);

        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getIndex'])
            ->getMock();
        $service->expects($this->once())
            ->method('getIndex')
            ->willReturn($index);

        self::assertTrue(
            $service->indexExists('indexName'),
            'falscher return Wert'
        );
    }

    public function testDeleteIndex(): void
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
            ->willReturn($index);

        $service->deleteIndex();
    }

    public function testDeleteIndexIfNameGiven(): void
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
            ->willReturn($index);

        $index->expects($this->once())
            ->method('getClient')
            ->willReturn($client);

        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getIndex'])
            ->getMock();
        $service->expects($this->once())
            ->method('getIndex')
            ->willReturn($index);

        $service->deleteIndex('indexName');
    }

    public function testOptimizeIndex(): void
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
            ->willReturn($index);

        $service->optimizeIndex();
    }

    public function testMakeIndexDocInstance(): void
    {
        $service = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch');
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
        self::assertEquals(
            'mksearch',
            $extKey->getValue($docInstance)->getValue(),
            'extKey falsch'
        );

        $contentType = new ReflectionProperty(
            'tx_mksearch_model_IndexerDocumentBase',
            'contentType'
        );
        self::assertEquals(
            'tt_content',
            $contentType->getValue($docInstance)->getValue(),
            'contentType falsch'
        );

        $fieldClass = new ReflectionProperty(
            'tx_mksearch_model_IndexerDocumentBase',
            'fieldClass'
        );
        self::assertEquals(
            'tx_mksearch_model_IndexerFieldBase',
            $fieldClass->getValue($docInstance),
            'fieldClass falsch'
        );
    }

    public function testIndexUpdateCallsIndexNew(): void
    {
        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['indexNew'])
            ->getMock();
        $doc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            'mksearch',
            'tt_content',
            'tx_mksearch_model_IndexerFieldBase'
        );
        $service->expects($this->once())
            ->method('indexNew')
            ->with($doc)
            ->willReturn(true);

        self::assertTrue($service->indexUpdate($doc));
    }

    public function testGetOpenIndexName(): void
    {
        $service = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch');
        $indexName = new ReflectionProperty(
            'tx_mksearch_service_engine_ElasticSearch',
            'indexName'
        );
        $indexName->setValue($service, 'unknown');

        self::assertEquals('unknown', $service->getOpenIndexName());
    }

    public function testCloseIndex(): void
    {
        $index = $this->getMockBuilder('stdClass')
            ->addMethods(['close'])
            ->getMock();
        $index->expects($this->once())
            ->method('close')
            ->willReturn(true);

        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getIndex'])
            ->getMock();
        $service->expects($this->once())
            ->method('getIndex')
            ->willReturn($index);

        $service->closeIndex();

        self::assertObjectNotHasAttribute(
            'index',
            $service,
            'index property nicht auf NULL gesetzt'
        );
    }

    public function testIndexNew(): void
    {
        /* @var $doc tx_mksearch_model_IndexerDocumentBase */
        $doc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
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
            ->willReturn(true);
        $index->expects($this->once())
            ->method('addDocuments')
            ->with([$elasticaDocument])
            ->willReturn($response);

        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getIndex'])
            ->getMock();
        $service->expects($this->once())
            ->method('getIndex')
            ->willReturn($index);

        self::assertTrue($service->indexNew($doc));
    }

    public function testIndexDeleteByContentUid(): void
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
            ->willReturn(true);
        $index->expects($this->once())
            ->method('deleteDocuments')
            ->with([$elasticaDocument])
            ->willReturn($response);

        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getIndex'])
            ->getMock();
        $service->expects($this->once())
            ->method('getIndex')
            ->willReturn($index);

        self::assertTrue($service->indexDeleteByContentUid(
            123,
            'mksearch',
            'tt_content'
        ));
    }

    public function testSearchThrowsRuntimeExceptionIfElasticaThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
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

    public function testSearchThrowsRuntimeExceptionIfResponseHttpStatusIsNot200(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Exception caught from ElasticSearch: Error requesting ElasticSearch. HTTP status: 201; Path: pfad; Query: query');
        $response = $this->getMockBuilder('stdClass')
            ->addMethods(['getIndex'])
            ->getMock();
        $response->expects($this->once())
            ->method('getStatus')
            ->willReturn(201);

        $searchResult = $this->getMockBuilder(Elastica\ResultSet::class)
            ->onlyMethods(['getResponse'])
            ->disableOriginalConstructor()
            ->getMock();
        $searchResult->expects($this->once())
            ->method('getResponse')
            ->willReturn($response);

        $index = $this->getMockBuilder('stdClass')
            ->addMethods(['search', 'getClient'])
            ->getMock();
        $index->expects($this->once())
            ->method('search')
            ->willReturn($searchResult);

        $lastRequest = $this->getMockBuilder('stdClass')
            ->addMethods(['getPath', 'getQuery', 'getData'])
            ->getMock();
        $lastRequest->expects($this->once())
            ->method('getPath')
            ->willReturn('pfad');
        $lastRequest->expects($this->once())
            ->method('getQuery')
            ->willReturn('query');
        $lastRequest->expects($this->once())
            ->method('getData')
            ->willReturn('data');
        $client = $this->getMockBuilder('stdClass')
            ->addMethods(['getLastRequest'])
            ->getMock();
        $client->expects($this->once())
            ->method('getLastRequest')
            ->willReturn($lastRequest);

        $index->expects($this->once())
            ->method('getClient')
            ->willReturn($client);

        $service = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getIndex'])
            ->getMock();
        $service->expects($this->any())
            ->method('getIndex')
            ->willReturn($index);

        $service->search();
    }

    public function testGetElasticaQuery(): void
    {
        $service = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch');

        $fields['term'] = 'test term';
        $elasticaQuery = $this->callInaccessibleMethod($service, 'getElasticaQuery', $fields, []);
        $expectedQuery = Elastica\Query::create('test term');

        self::assertEquals($expectedQuery->getQuery(), $elasticaQuery->getQuery(), 'query falsch');
    }

    public function testGetElasticaQueryHandlesSorting(): void
    {
        $service = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch');

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

    #[PHPUnit\Framework\Attributes\DataProvider('getOptionsForElastica')]
    public function testGetOptionsForElastica(string $initialOption, ?string $expectedMappedOption): void
    {
        $service = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch');

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
    public static function getOptionsForElastica(): array
    {
        return [
            ['search_type', 'search_type'],
            ['debug', 'explain'],
            ['limit', 'limit'],
            ['offset', 'from'],
            ['unknown', null],
        ];
    }

    public function testGetItemsFromSearchResult(): void
    {
        $resultSetBuilder = new Elastica\ResultSet\DefaultBuilder();

        $searchResult = $resultSetBuilder->buildResultSet(
            new Elastica\Response(''),
            Elastica\Query::create('')
        );

        $resultProperty = new ReflectionProperty(Elastica\ResultSet::class, '_results');
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
        $resultProperty->setValue($searchResult, $results);

        $service = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_ElasticSearch');

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

    public function testSearchWithValidSearchResult(): void
    {
        $lastRequest = $this->getMockBuilder('stdClass')
            ->addMethods(['getPath', 'getQuery', 'getData'])
            ->getMock();
        $lastRequest->expects($this->once())
            ->method('getPath')
            ->willReturn('pfad');
        $lastRequest->expects($this->once())
            ->method('getQuery')
            ->willReturn('query');
        $lastRequest->expects($this->once())
            ->method('getData')
            ->willReturn('data');
        $client = $this->getMockBuilder('stdClass')
            ->addMethods(['getLastRequest'])
            ->getMock();
        $client->expects($this->once())
            ->method('getLastRequest')
            ->willReturn($lastRequest);

        $index = $this->getMockBuilder('stdClass')
            ->addMethods(['search', 'getClient'])
            ->getMock();
        $index->expects($this->once())
            ->method('getClient')
            ->willReturn($client);

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
            ->willReturn($index);

        $fields = ['fields'];
        $options = ['options'];
        $service->expects($this->once())
            ->method('getElasticaQuery')
            ->with($fields, $options)
            ->willReturn('elastica query');
        $service->expects($this->once())
            ->method('getOptionsForElastica')
            ->with($options)
            ->willReturn(123);

        $response = $this->getMock(
            Elastica\Response::class,
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
            ->willReturn('es gab einen Fehler');

        $resultSetBuilder = new Elastica\ResultSet\DefaultBuilder();

        $searchResult = $resultSetBuilder->buildResultSet(
            $response,
            Elastica\Query::create('')
        );

        $index->expects($this->once())
            ->method('search')
            ->with('elastica query', 123)
            ->willReturn($searchResult);

        $service->expects($this->once())
            ->method('checkResponseOfSearchResult')
            ->with($searchResult);

        $service->expects($this->once())
            ->method('getItemsFromSearchResult')
            ->with($searchResult)
            ->willReturn(['search results']);

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

    public function testSearchPrintsNoDebugIfNotSetInOptions(): void
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
            ->willReturn($lastRequest);

        $index = $this->getMockBuilder('stdClass')
            ->addMethods(['search', 'getClient'])
            ->getMock();
        $index->expects($this->once())
            ->method('getClient')
            ->willReturn($client);

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
            ->willReturn($index);

        $fields = ['fields'];
        $options = ['options'];

        $resultSetBuilder = new Elastica\ResultSet\DefaultBuilder();

        $searchResult = $resultSetBuilder->buildResultSet(
            new Elastica\Response(''),
            Elastica\Query::create('')
        );

        $index->expects($this->once())
            ->method('search')
            ->willReturn($searchResult);

        $service->search($fields, $options);
    }

    public function testSearchPrintsDebugIfSetInOptions(): void
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
            ->willReturn($lastRequest);

        $index = $this->getMockBuilder('stdClass')
            ->addMethods(['search', 'getClient'])
            ->getMock();
        $index->expects($this->once())
            ->method('getClient')
            ->willReturn($client);

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
            ->willReturn($index);

        $fields = ['fields'];
        $options = ['debug' => true];

        $resultSetBuilder = new Elastica\ResultSet\DefaultBuilder();

        $searchResult = $resultSetBuilder->buildResultSet(
            new Elastica\Response(''),
            Elastica\Query::create('')
        );

        $index->expects($this->once())
            ->method('search')
            ->willReturn($searchResult);

        $service->search($fields, $options);
    }
}
