<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 DMK E-Business GmbH <dev@dmk-ebusiness.de>
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
    public function testIsIndexableRecord($tableName, $sourceRecord, $options)
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
    private static $oFALTest = null;

    /**
     * Constructs a test case with the given name.
     *
     * @param string $name
     * @param array  $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        self::$oFALTest = new tx_mksearch_indexer_FALTest();
    }

    /**
     * @dataProvider providerIsIndexableRecord
     */
    public function testIsIndexableRecord($aSourceRecord, $aOptions, $bIndexable)
    {
        self::assertEquals(
            $bIndexable,
            self::$oFALTest->testIsIndexableRecord('sys_file', $aSourceRecord, ['sys_file.' => $aOptions])
        );
    }

    public function providerIsIndexableRecord()
    {
        return [
                'Line: '.__LINE__ => [
                    [
                        'identifier' => 'unterordner/test.html',
                        'extension' => 'html',
                    ], [
                        'byFileExtension' => 'pdf, html',
                        'byDirectory' => '/^fileadmin\/.*\//',
                    ], true,
                ],
                'Line: '.__LINE__ => [
                    [
                        'identifier' => 'unterordner/test.html',
                        'extension' => 'html',
                    ], [
                        'byFileExtension' => 'pdf, html',
                        'byDirectory' => '/^fileadmin\/unterordner.*\//',
                    ], true,
                ],
                'Line: '.__LINE__ => [
                    [
                        'identifier' => 'unterordner/test.txt',
                        'extension' => 'txt',
                    ], [
                        'byFileExtension' => 'pdf, html',
                        'byDirectory' => '/^fileadmin\/unterordner.*\//',
                    ], false,
                ],
                'Line: '.__LINE__ => [
                    [
                        'identifier' => 'denied/test.txt',
                        'extension' => 'txt',
                    ], [
                        'byFileExtension' => 'pdf, html',
                        'byDirectory' => '/^fileadmin\/unterordner.*\//',
                    ], false,
                ],
                'Line: '.__LINE__ => [
                    [
                        'identifier' => 'allowed/test.pdf',
                        'extension' => 'pdf',
                    ], [
                        'byFileExtension.' => ['pdf', 'txt'],
                        'byDirectory.' => ['fileadmin/unterordner/', 'fileadmin/allowed/'],
                    ], true,
                ],
                'Line: '.__LINE__ => [
                    [
                        'identifier' => 'denied/test.pdf',
                        'extension' => 'pdf',
                    ], [
                        'byFileExtension' => 'pdf, html',
                        'byDirectory.' => ['fileadmin/unterordner/', 'fileadmin/allowed/'],
                    ], false,
                ],
                'Line: '.__LINE__ => [
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
                'Line: '.__LINE__ => [
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
                'Line: '.__LINE__ => [
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
                'Line: '.__LINE__ => [
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

    public function testIsIndexableRecordWithoutDeleteIfNotIndexableOption()
    {
        self::markTestSkipped('Test needs refactoring.');
        $indexer = $this->getMock(
            'tx_mksearch_indexer_FAL',
            ['hasDocToBeDeleted']
        );
        $indexer->expects($this->once())
            ->method('hasDocToBeDeleted')
            ->will($this->returnValue(false));

        list($extKey, $cType) = $indexer->getContentType();
        $options = [
            'filter.' => [
                'sys_file.' => ['byFileExtension' => 'pdf, html'],
            ],
            'deleteIfNotIndexable' => 0,
        ];

        $aRawData = ['uid' => 0, 'extension' => 'something_else'];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $oIndexDoc = $indexer->prepareSearchData('sys_file', $aRawData, $indexDoc, $options);
        self::assertNull($oIndexDoc, 'Es wurde nicht null geliefert!');
    }

    public function testIsIndexableRecordWithDeleteIfNotIndexableOption()
    {
        self::markTestSkipped('Test needs refactoring.');
        $indexer = $this->getMock(
            'tx_mksearch_indexer_FAL',
            ['hasDocToBeDeleted']
        );
        $indexer->expects($this->once())
            ->method('hasDocToBeDeleted')
            ->will($this->returnValue(false));

        list($extKey, $cType) = $indexer->getContentType();
        $options = [
            'filter.' => [
                'sys_file.' => ['byFileExtension' => 'pdf, html'],
            ],
            'deleteIfNotIndexable' => 1,
        ];

        $aRawData = ['uid' => 1, 'extension' => 'something_else'];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $oIndexDoc = $indexer->prepareSearchData('sys_file', $aRawData, $indexDoc, $options);
        self::assertTrue($oIndexDoc->getDeleted(), 'Das Element wurde nich auf gelöscht gesetzt!');
    }

    /**
     * @group unit
     */
    public function testStopIndexingCallsIndexerUtility()
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
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexerUtility->expects($this->once())
            ->method('stopIndexing')
            ->with($tableName, $sourceRecord, $indexDoc, $options)
            ->will($this->returnValue('return'));

        $indexer->expects($this->once())
            ->method('getIndexerUtility')
            ->will($this->returnValue($indexerUtility));

        self::assertEquals(
            'return',
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

    /**
     * @group unit
     */
    public function testGetInternalIndexService()
    {
        self::markTestSkipped('Test needs refactoring.');

        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_FAL');

        self::assertInstanceOf(
            'tx_mksearch_service_internal_Index',
            $this->callInaccessibleMethod(
                $indexer,
                'getInternalIndexService'
            )
        );
    }

    /**
     * @group unit
     */
    public function testStopIndexingPutsCorrectRecordToIndexIfSysFileMetadatahasChangedAndReturnsTrue()
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
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexerService->expects($this->once())
            ->method('addRecordToIndex')
            ->with('sys_file', 123);

        $indexer->expects($this->once())
            ->method('getInternalIndexService')
            ->will($this->returnValue($indexerService));

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

    /**
     * @group unit
     */
    public function testGetRelFileName()
    {
        self::markTestSkipped('Test needs refactoring.');
        $fileRecord = ['identifier' => 'FileWithUmalutsÄÖÜ.txt', 'uid' => 123, 'storage' => 1];
        $indexer = $this->getMock('tx_mksearch_indexer_FAL', ['getResourceStorage']);
        /**
         * @var TYPO3\\CMS\\Core\\Resource\\ResourceFactory'
         */
        $fileFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
        $driver = $fileFactory->getDriverObject('Local', ['basePath' => 'fileadmin', 'pathType' => 'relative']);
        $driver->processConfiguration();
        $storage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'TYPO3\\CMS\\Core\\Resource\\ResourceStorage',
            $driver,
            ['is_public' => 2, 'driver' => 'Local']
        );
        $isOnline = new ReflectionProperty(get_class($storage), 'isOnline');
        $isOnline->setAccessible(true);
        $isOnline->setValue($storage, true);
        $indexer->expects(self::once())
            ->method('getResourceStorage')
            ->will(self::returnValue($storage));

        self::assertEquals(
            'fileadmin/FileWithUmalutsÄÖÜ.txt',
            $this->callInaccessibleMethod($indexer, 'getRelFileName', 'sys_file', $fileRecord)
        );
    }

    /**
     * @group unit
     * @dataProvider providerHasDocToBeDeleted
     */
    public function testHasDocToBeDeletedWithRecordWithDeleteFlag($sourceRecord, $expected)
    {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_FAL',
            ['getFilePath']
        );
        $indexer->expects(self::any())
            ->method('getFilePath')
            ->will(self::returnValue(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mksearch')));

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
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

    public function providerHasDocToBeDeleted()
    {
        return [
            __LINE__ => [
                'sourceRecord' => ['name' => 'Resources/Public/Icons/Extension.gif'],
                'expected' => false,
            ],
            __LINE__ => [
                'sourceRecord' => ['name' => 'sdfuhsdfjkhk.gif'],
                'expected' => true,
            ],
            __LINE__ => [
                'sourceRecord' => ['deleted' => 1, 'name' => 'ext_icon.gif'],
                'expected' => true,
            ],
            __LINE__ => [
                'sourceRecord' => ['missing' => 1, 'name' => 'ext_icon.gif'],
                'expected' => true,
            ],
        ];
    }
}
