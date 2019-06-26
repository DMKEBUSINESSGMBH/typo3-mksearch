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
class tx_mksearch_tests_indexer_FAL_testcase extends tx_mksearch_tests_Testcase
{
    private static $oFALTest = null;

    /**
     * Constructs a test case with the given name.
     *
     * @param string $name
     * @param array  $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
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
            self::$oFALTest->testIsIndexableRecord('sys_file', $aSourceRecord, array('sys_file.' => $aOptions))
        );
    }

    public function providerIsIndexableRecord()
    {
        return array(
                'Line: '.__LINE__ => array(
                    array(
                        'identifier' => 'unterordner/test.html',
                        'extension' => 'html',
                    ), array(
                        'byFileExtension' => 'pdf, html',
                        'byDirectory' => '/^fileadmin\/.*\//',
                    ), true,
                ),
                'Line: '.__LINE__ => array(
                    array(
                        'identifier' => 'unterordner/test.html',
                        'extension' => 'html',
                    ), array(
                        'byFileExtension' => 'pdf, html',
                        'byDirectory' => '/^fileadmin\/unterordner.*\//',
                    ), true,
                ),
                'Line: '.__LINE__ => array(
                    array(
                        'identifier' => 'unterordner/test.txt',
                        'extension' => 'txt',
                    ), array(
                        'byFileExtension' => 'pdf, html',
                        'byDirectory' => '/^fileadmin\/unterordner.*\//',
                    ), false,
                ),
                'Line: '.__LINE__ => array(
                    array(
                        'identifier' => 'denied/test.txt',
                        'extension' => 'txt',
                    ), array(
                        'byFileExtension' => 'pdf, html',
                        'byDirectory' => '/^fileadmin\/unterordner.*\//',
                    ), false,
                ),
                'Line: '.__LINE__ => array(
                    array(
                        'identifier' => 'allowed/test.pdf',
                        'extension' => 'pdf',
                    ), array(
                        'byFileExtension.' => array('pdf', 'txt'),
                        'byDirectory.' => array('fileadmin/unterordner/', 'fileadmin/allowed/'),
                    ), true,
                ),
                'Line: '.__LINE__ => array(
                    array(
                        'identifier' => 'denied/test.pdf',
                        'extension' => 'pdf',
                    ), array(
                        'byFileExtension' => 'pdf, html',
                        'byDirectory.' => array('fileadmin/unterordner/', 'fileadmin/allowed/'),
                    ), false,
                ),
                'Line: '.__LINE__ => array(
                    array(
                        'identifier' => 'unterordner/test.txt',
                        'extension' => 'txt',
                    ), array(
                        'byFileExtension' => 'html, xhtml',
                        'byFileExtension.' => array('pdf', 'txt'),
                        'byDirectory' => '/^fileadmin\/.*\//',
                        'byDirectory.' => array('fileadmin/unterordner/', 'fileadmin/allowed/'),
                    ), true,
                ),
                'Line: '.__LINE__ => array(
                    array(
                        'identifier' => 'unterordner/subfolder/test.txt',
                        'extension' => 'txt',
                    ), array(
                        'byFileExtension' => 'html, xhtml',
                        'byFileExtension.' => array('pdf', 'txt'),
                        'byDirectory' => '/^fileadmin\/.*\//',
                        'byDirectory.' => array('fileadmin/unterordner/', 'fileadmin/allowed/'),
                    ), false,
                ),
                'Line: '.__LINE__ => array(
                    array(
                        'identifier' => 'unterordner/subfolder/test.txt',
                        'extension' => 'txt',
                    ), array(
                        'byFileExtension' => 'html, xhtml',
                        'byFileExtension.' => array('pdf', 'txt'),
                        'byDirectory' => '/^fileadmin\/.*\//',
                        'byDirectory.' => array('checkSubFolder' => 1, 'fileadmin/unterordner/', 'fileadmin/allowed/'),
                    ), true,
                ),
                // spezieller eternit fall
                'Line: '.__LINE__ => array(
                    array(
                        'identifier' => 'downloads/tx_eternitdownload/test.txt',
                        'extension' => 'txt',
                    ), array(
                        'byFileExtension' => 'html, xhtml',
                        'byFileExtension.' => array('pdf', 'txt'),
                        'byDirectory' => '/^fileadmin\/.*\//',
                        'byDirectory.' => array('checkSubFolder' => '1', 'fileadmin/downloads/', '10' => 'fileadmin/downloads/tx_eternitdownload/', '10.' => array('disallow' => 1)),
                    ), false,
                ),
            );
    }

    public function testIsIndexableRecordWithoutDeleteIfNotIndexableOption()
    {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_FAL',
            array('hasDocToBeDeleted')
        );
        $indexer->expects($this->once())
            ->method('hasDocToBeDeleted')
            ->will($this->returnValue(false));

        list($extKey, $cType) = $indexer->getContentType();
        $options = array(
            'filter.' => array(
                'sys_file.' => array('byFileExtension' => 'pdf, html'),
            ),
            'deleteIfNotIndexable' => 0,
        );

        $aRawData = array('uid' => 0, 'extension' => 'something_else');
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $oIndexDoc = $indexer->prepareSearchData('sys_file', $aRawData, $indexDoc, $options);
        self::assertNull($oIndexDoc, 'Es wurde nicht null geliefert!');
    }

    public function testIsIndexableRecordWithDeleteIfNotIndexableOption()
    {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_FAL',
            array('hasDocToBeDeleted')
        );
        $indexer->expects($this->once())
            ->method('hasDocToBeDeleted')
            ->will($this->returnValue(false));

        list($extKey, $cType) = $indexer->getContentType();
        $options = array(
            'filter.' => array(
                'sys_file.' => array('byFileExtension' => 'pdf, html'),
            ),
            'deleteIfNotIndexable' => 1,
        );

        $aRawData = array('uid' => 1, 'extension' => 'something_else');
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
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
            array('getIndexerUtility')
        );
        $indexerUtility = $this->getMock(
            'tx_mksearch_util_Indexer',
            array('stopIndexing')
        );

        $tableName = 'some_table';
        $sourceRecord = array('some_record');
        $options = array('some_options');
        $indexDoc = tx_rnbase::makeInstance(
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
        $indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_FAL');

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
            array('getInternalIndexService')
        );
        $indexerService = $this->getMock(
            'tx_mksearch_service_internal_Index',
            array('addRecordToIndex')
        );

        $tableName = 'sys_file_metadata';
        $sourceRecord = array('file' => 123);
        $options = array('some_options');
        $indexDoc = tx_rnbase::makeInstance(
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
        $fileRecord = array('identifier' => 'FileWithUmalutsÄÖÜ.txt', 'uid' => 123, 'storage' => 1);
        $indexer = $this->getMock('tx_mksearch_indexer_FAL', array('getResourceStorage'));
        /**
         * @var TYPO3\\CMS\\Core\\Resource\\ResourceFactory'
         */
        $fileFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
        $driver = $fileFactory->getDriverObject('Local', array('basePath' => 'fileadmin', 'pathType' => 'relative'));
        $driver->processConfiguration();
        $storage = tx_rnbase::makeInstance(
            'TYPO3\\CMS\\Core\\Resource\\ResourceStorage',
            $driver,
            array('is_public' => 2, 'driver' => 'Local')
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
            array('getFilePath')
        );
        $indexer->expects(self::any())
            ->method('getFilePath')
            ->will(self::returnValue(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mksearch')));

        $indexDoc = tx_rnbase::makeInstance(
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
        return array(
            __LINE__ => array(
                'sourceRecord' => array('name' => 'ext_icon.gif'),
                'expected' => false,
            ),
            __LINE__ => array(
                'sourceRecord' => array('name' => 'sdfuhsdfjkhk.gif'),
                'expected' => true,
            ),
            __LINE__ => array(
                'sourceRecord' => array('deleted' => 1, 'name' => 'ext_icon.gif'),
                'expected' => true,
            ),
            __LINE__ => array(
                'sourceRecord' => array('missing' => 1, 'name' => 'ext_icon.gif'),
                'expected' => true,
            ),
        );
    }
}
