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
 * Kindklasse des Indexers, um auf private Methoden zuzugreifen.
 *
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_indexer_FALTest extends tx_mksearch_indexer_FAL
{
    // wir wollen isIndexableRecord nicht erst public machen
    public function testIsIndexableRecord(string $tableName, $sourceRecord, $options)
    {
        return $this->isIndexableRecord($tableName, $sourceRecord, $options);
    }
}

/**
 * Tests für den Dam Media Indexer.
 *
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_indexer_FALTest extends tx_mksearch_tests_Testcase
{
    private static tx_mksearch_indexer_FALTest $oFALTest;

    protected function setUp(): void
    {
        parent::setUp();
        self::$oFALTest = new tx_mksearch_indexer_FALTest();
    }

    #[PHPUnit\Framework\Attributes\DataProvider('providerIsIndexableRecord')]
    public function testIsIndexableRecord($aSourceRecord, $aOptions, $bIndexable): void
    {
        self::assertEquals(
            $bIndexable,
            self::$oFALTest->testIsIndexableRecord('sys_file', $aSourceRecord, ['sys_file.' => $aOptions])
        );
    }

    public static function providerIsIndexableRecord(): array
    {
        return [
            1 => [
                [
                    'identifier' => 'unterordner/test.html',
                    'extension' => 'html',
                ], [
                    'byFileExtension' => 'pdf, html',
                    'byDirectory' => '/^fileadmin\/.*\//',
                ], true,
            ],
            2 => [
                [
                    'identifier' => 'unterordner/test.html',
                    'extension' => 'html',
                ], [
                    'byFileExtension' => 'pdf, html',
                    'byDirectory' => '/^fileadmin\/unterordner.*\//',
                ], true,
            ],
            3 => [
                [
                    'identifier' => 'unterordner/test.txt',
                    'extension' => 'txt',
                ], [
                    'byFileExtension' => 'pdf, html',
                    'byDirectory' => '/^fileadmin\/unterordner.*\//',
                ], false,
            ],
            4 => [
                [
                    'identifier' => 'denied/test.txt',
                    'extension' => 'txt',
                ], [
                    'byFileExtension' => 'pdf, html',
                    'byDirectory' => '/^fileadmin\/unterordner.*\//',
                ], false,
            ],
            5 => [
                [
                    'identifier' => 'allowed/test.pdf',
                    'extension' => 'pdf',
                ], [
                    'byFileExtension.' => ['pdf', 'txt'],
                    'byDirectory.' => ['fileadmin/unterordner/', 'fileadmin/allowed/'],
                ], true,
            ],
            6 => [
                [
                    'identifier' => 'denied/test.pdf',
                    'extension' => 'pdf',
                ], [
                    'byFileExtension' => 'pdf, html',
                    'byDirectory.' => ['fileadmin/unterordner/', 'fileadmin/allowed/'],
                ], false,
            ],
            7 => [
                [
                    'identifier' => 'unterordner/test.txt',
                    'extension' => 'txt',
                ], [
                    'byFileExtension' => 'html, xhtml',
                    'byFileExtension.' => ['pdf', 'txt'],
                    'byDirectory' => '/^fileadmin\/.*\//',
                    'byDirectory.' => ['fileadmin/unterordner/', 'fileadmin/allowed/'],
                ], true,
            ],
            8 => [
                [
                    'identifier' => 'unterordner/subfolder/test.txt',
                    'extension' => 'txt',
                ], [
                    'byFileExtension' => 'html, xhtml',
                    'byFileExtension.' => ['pdf', 'txt'],
                    'byDirectory' => '/^fileadmin\/.*\//',
                    'byDirectory.' => ['fileadmin/unterordner/', 'fileadmin/allowed/'],
                ], false,
            ],
            9 => [
                [
                    'identifier' => 'unterordner/subfolder/test.txt',
                    'extension' => 'txt',
                ], [
                    'byFileExtension' => 'html, xhtml',
                    'byFileExtension.' => ['pdf', 'txt'],
                    'byDirectory' => '/^fileadmin\/.*\//',
                    'byDirectory.' => ['checkSubFolder' => 1, 'fileadmin/unterordner/', 'fileadmin/allowed/'],
                ], true,
            ],
            // spezieller eternit fall
            10 => [
                [
                    'identifier' => 'downloads/tx_eternitdownload/test.txt',
                    'extension' => 'txt',
                ], [
                    'byFileExtension' => 'html, xhtml',
                    'byFileExtension.' => ['pdf', 'txt'],
                    'byDirectory' => '/^fileadmin\/.*\//',
                    'byDirectory.' => ['checkSubFolder' => '1', 'fileadmin/downloads/', '10' => 'fileadmin/downloads/tx_eternitdownload/', '10.' => ['disallow' => 1]],
                ], false,
            ],
        ];
    }

    public function testIsIndexableRecordWithoutDeleteIfNotIndexableOption(): void
    {
        self::markTestSkipped('Test needs refactoring.');
        $indexer = $this->getMock(
            'tx_mksearch_indexer_FAL',
            ['hasDocToBeDeleted']
        );
        $indexer->expects($this->once())
            ->method('hasDocToBeDeleted')
            ->willReturn(false);

        [$extKey, $cType] = $indexer->getContentType();
        $options = [
            'filter.' => [
                'sys_file.' => ['byFileExtension' => 'pdf, html'],
            ],
            'deleteIfNotIndexable' => 0,
        ];

        $aRawData = ['uid' => 0, 'extension' => 'something_else'];
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $oIndexDoc = $indexer->prepareSearchData('sys_file', $aRawData, $indexDoc, $options);
        self::assertNull($oIndexDoc, 'Es wurde nicht null geliefert!');
    }

    public function testIsIndexableRecordWithDeleteIfNotIndexableOption(): void
    {
        self::markTestSkipped('Test needs refactoring.');
        $indexer = $this->getMock(
            'tx_mksearch_indexer_FAL',
            ['hasDocToBeDeleted']
        );
        $indexer->expects($this->once())
            ->method('hasDocToBeDeleted')
            ->willReturn(false);

        [$extKey, $cType] = $indexer->getContentType();
        $options = [
            'filter.' => [
                'sys_file.' => ['byFileExtension' => 'pdf, html'],
            ],
            'deleteIfNotIndexable' => 1,
        ];

        $aRawData = ['uid' => 1, 'extension' => 'something_else'];
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $oIndexDoc = $indexer->prepareSearchData('sys_file', $aRawData, $indexDoc, $options);
        self::assertTrue($oIndexDoc->getDeleted(), 'Das Element wurde nich auf gelöscht gesetzt!');
    }

    public function testStopIndexingCallsIndexerUtility(): void
    {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_FAL',
            ['getIndexerUtility']
        );
        $indexerUtility = $this->getMock(
            'tx_mksearch_util_Indexer',
            ['stopIndexing']
        );

        $tableName = 'some_table';
        $sourceRecord = ['some_record'];
        $options = ['some_options'];
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexerUtility->expects($this->once())
            ->method('stopIndexing')
            ->with($tableName, $sourceRecord, $indexDoc, $options)
            ->willReturn(true);

        $indexer->expects($this->once())
            ->method('getIndexerUtility')
            ->willReturn($indexerUtility);

        self::assertSame(
            true,
            $this->callInaccessibleMethod(
                $indexer,
                'stopIndexing',
                $tableName,
                $sourceRecord,
                $indexDoc,
                $options
            )
        );
    }

    public function testGetInternalIndexService(): void
    {
        self::markTestSkipped('Test needs refactoring.');

        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_FAL');

        self::assertInstanceOf(
            'tx_mksearch_service_internal_Index',
            $this->callInaccessibleMethod(
                $indexer,
                'getInternalIndexService'
            )
        );
    }

    public function testStopIndexingPutsCorrectRecordToIndexIfSysFileMetadatahasChangedAndReturnsTrue(): void
    {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_FAL',
            ['getInternalIndexService']
        );
        $indexerService = $this->getMock(
            'tx_mksearch_service_internal_Index',
            ['addRecordToIndex']
        );

        $tableName = 'sys_file_metadata';
        $sourceRecord = ['file' => 123];
        $options = ['some_options'];
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexerService->expects($this->once())
            ->method('addRecordToIndex')
            ->with('sys_file', 123);

        $indexer->expects($this->once())
            ->method('getInternalIndexService')
            ->willReturn($indexerService);

        self::assertTrue(
            $this->callInaccessibleMethod(
                $indexer,
                'stopIndexing',
                $tableName,
                $sourceRecord,
                $indexDoc,
                $options
            )
        );
    }

    public function testGetRelFileName(): void
    {
        self::markTestSkipped('Test needs refactoring.');
        $fileRecord = ['identifier' => 'FileWithUmalutsÄÖÜ.txt', 'uid' => 123, 'storage' => 1];
        $indexer = $this->getMock('tx_mksearch_indexer_FAL', ['getResourceStorage']);
        /**
         * @var TYPO3\\CMS\\Core\\Resource\\ResourceFactory'
         */
        $fileFactory = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Core\Resource\ResourceFactory::class);
        $driver = $fileFactory->getDriverObject('Local', ['basePath' => 'fileadmin', 'pathType' => 'relative']);
        $driver->processConfiguration();

        $storage = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            TYPO3\CMS\Core\Resource\ResourceStorage::class,
            $driver,
            ['is_public' => 2, 'driver' => 'Local']
        );
        $isOnline = new ReflectionProperty($storage::class, 'isOnline');
        $isOnline->setAccessible(true);
        $isOnline->setValue($storage, true);
        $indexer->expects(self::once())
            ->method('getResourceStorage')
            ->willReturn($storage);

        self::assertEquals(
            'fileadmin/FileWithUmalutsÄÖÜ.txt',
            $this->callInaccessibleMethod($indexer, 'getRelFileName', 'sys_file', $fileRecord)
        );
    }

    #[PHPUnit\Framework\Attributes\DataProvider('providerHasDocToBeDeleted')]
    public function testHasDocToBeDeletedWithRecordWithDeleteFlag($sourceRecord, $expected): void
    {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_FAL',
            ['getFilePath']
        );
        $indexer->expects(self::any())
            ->method('getFilePath')
            ->willReturn(__DIR__.'/../../');

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            'core',
            'file'
        );
        self::assertEquals(
            $expected,
            $this->callInaccessibleMethod(
                $indexer,
                'hasDocToBeDeleted',
                'tt_content',
                $sourceRecord,
                $indexDoc
            )
        );
    }

    public static function providerHasDocToBeDeleted(): array
    {
        return [
            1 => [
                'sourceRecord' => ['name' => 'Resources/Public/Icons/Extension.svg'],
                'expected' => false,
            ],
            2 => [
                'sourceRecord' => ['name' => 'sdfuhsdfjkhk.gif'],
                'expected' => true,
            ],
            3 => [
                'sourceRecord' => ['deleted' => 1, 'name' => 'ext_icon.gif'],
                'expected' => true,
            ],
            4 => [
                'sourceRecord' => ['missing' => 1, 'name' => 'ext_icon.gif'],
                'expected' => true,
            ],
        ];
    }
}
