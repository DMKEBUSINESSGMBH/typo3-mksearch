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

/**
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_indexer_BaseMediaTest extends tx_mksearch_tests_Testcase
{
    /**
     * @group unit
     */
    public function testPrepareSearchDataCallsStopIndexing()
    {
        $indexer = $this->getIndexerMock();

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            'core',
            'tt_content'
        );
        $options = [];
        $sourceRecord = ['uid' => 1, 'test_field_1' => 'test value 1'];

        $indexer->expects($this->once())
            ->method('stopIndexing')
            ->with('tt_content', $sourceRecord, $indexDoc, $options);

        $indexer->prepareSearchData(
            'tt_content',
            $sourceRecord,
            $indexDoc,
            $options
        );
    }

    /**
     * @group unit
     */
    public function testPrepareSearchDataReturnsNullIfStopIndexing()
    {
        $indexer = $this->getIndexerMock();

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            'core',
            'tt_content'
        );
        $options = [];
        $sourceRecord = ['uid' => 1, 'test_field_1' => 'test value 1'];

        $indexer->expects($this->once())
            ->method('stopIndexing')
            ->will($this->returnValue(true));

        self::assertNull($indexer->prepareSearchData(
            'tt_content',
            $sourceRecord,
            $indexDoc,
            $options
        ));
    }

    /**
     * @group unit
     */
    public function testPrepareSearchDataReturnsNotNullIfNotStopIndexing()
    {
        $indexer = $this->getIndexerMock();

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            'core',
            'tt_content'
        );
        $options = [];
        $sourceRecord = ['uid' => 1, 'test_field_1' => 'test value 1'];

        $indexer->expects($this->once())
            ->method('stopIndexing')
            ->will($this->returnValue(false));

        self::assertNotNull($indexer->prepareSearchData(
            'tt_content',
            $sourceRecord,
            $indexDoc,
            $options
        ));
    }

    /**
     * @group unit
     */
    public function testGetIndexerUtility()
    {
        $indexer = $this->getIndexerMock();

        self::assertInstanceOf(
            'tx_mksearch_util_Indexer',
            $this->callInaccessibleMethod(
                $indexer,
                'getIndexerUtility'
            )
        );
    }

    /**
     * @group unit
     */
    public function testStopIndexingCallsIndexerUtility()
    {
        $indexer = $this->getIndexerMock(
            [
                'getBaseTableName', 'getFileExtension',
                'getFilePath', 'getRelFileName', 'getIndexerUtility',
            ]
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
    public function testPrepareSearchDataCallsIsIndexableRecordNotIfRecordIsDeleted()
    {
        $indexer = $this->getIndexerMock([
            'getBaseTableName', 'getFileExtension',
            'getFilePath', 'getRelFileName', 'stopIndexing', 'isIndexableRecord',
        ]);

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            'core',
            'tt_content'
        );
        $options = [];
        $sourceRecord = ['uid' => 1, 'deleted' => 1];

        $indexer->expects($this->never())
            ->method('isIndexableRecord');

        $indexer->prepareSearchData(
            'tt_content',
            $sourceRecord,
            $indexDoc,
            $options
        );
    }

    /**
     * @group unit
     */
    public function testPrepareSearchDataCallsIsIndexableRecordNotIfRecordIsHidden()
    {
        $indexer = $this->getIndexerMock([
            'getBaseTableName', 'getFileExtension',
            'getFilePath', 'getRelFileName', 'stopIndexing', 'isIndexableRecord',
        ]);

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            'core',
            'tt_content'
        );
        $options = [];
        $sourceRecord = ['uid' => 1, 'hidden' => 1];

        $indexer->expects($this->never())
            ->method('isIndexableRecord');

        $indexer->prepareSearchData(
            'tt_content',
            $sourceRecord,
            $indexDoc,
            $options
        );
    }

    /**
     * @group unit
     */
    public function testPrepareSearchDataSetsDocDeletedIfRecordIsDeleted()
    {
        $indexer = $this->getIndexerMock([
            'getBaseTableName', 'getFileExtension',
            'getFilePath', 'getRelFileName', 'stopIndexing', 'isIndexableRecord',
        ]);

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            'core',
            'tt_content'
        );
        $options = [];
        $sourceRecord = ['uid' => 1, 'deleted' => 1];

        $indexDoc = $indexer->prepareSearchData(
            'tt_content',
            $sourceRecord,
            $indexDoc,
            $options
        );

        self::assertTrue($indexDoc->getDeleted());
    }

    /**
     * @group unit
     */
    public function testPrepareSearchDataSetsDocDeletedIfRecordIsHidden()
    {
        $indexer = $this->getIndexerMock([
            'getBaseTableName', 'getFileExtension',
            'getFilePath', 'getRelFileName', 'stopIndexing', 'isIndexableRecord',
        ]);

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            'core',
            'tt_content'
        );
        $options = [];
        $sourceRecord = ['uid' => 1, 'hidden' => 1];

        $indexDoc = $indexer->prepareSearchData(
            'tt_content',
            $sourceRecord,
            $indexDoc,
            $options
        );

        self::assertTrue($indexDoc->getDeleted());
    }

    /**
     * @group unit
     *
     * @dataProvider providerHasDocToBeDeleted
     */
    public function testHasDocToBeDeleted($sourceRecord, $expected)
    {
        $indexer = $this->getIndexerMock();
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            'core',
            'tt_content'
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
                'sourceRecord' => ['uid' => 7],
                'expected' => false,
            ],
            __LINE__ => [
                'sourceRecord' => ['uid' => 7, 'deleted' => 1],
                'expected' => true,
            ],
            __LINE__ => [
                'sourceRecord' => ['uid' => 7, 'hidden' => 1],
                'expected' => true,
            ],
            __LINE__ => [
                'sourceRecord' => ['uid' => 7, 'hidden' => 1, 'deleted' => 1],
                'expected' => true,
            ],
        ];
    }

    /**
     * @group unit
     */
    public function testGroupFieldIsAdded()
    {
        $indexer = $this->getIndexerMock([
            'getBaseTableName', 'getFileExtension',
            'getFilePath', 'getRelFileName', 'stopIndexing', 'isIndexableRecord',
        ]);
        $indexer->expects(self::once())
            ->method('isIndexableRecord')
            ->will(self::returnValue(true));

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', 'core', 'file');

        $rawData = ['uid' => 123];

        $indexDoc = $indexer->prepareSearchData('doesnt_matter', $rawData, $indexDoc, []);

        $indexedData = $indexDoc->getData();
        self::assertEquals('core:file:123', $indexedData['group_s']->getValue());
    }

    /**
     * @group unit
     */
    public function testContentAndAbstractFromSourceRecordAreIndexedCorrect()
    {
        $indexer = $this->getIndexerMock([
            'getBaseTableName', 'getFileExtension',
            'getFilePath', 'getRelFileName', 'stopIndexing', 'isIndexableRecord',
        ]);
        $indexer->expects(self::any())
            ->method('isIndexableRecord')
            ->will(self::returnValue(true));

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', 'core', 'file');

        $indexDoc = $indexer->prepareSearchData(
            'doesnt_matter',
            ['uid' => 123, 'title' => 'title', 'description' => 'content'],
            $indexDoc,
            ['indexMode' => 'none']
        );

        $indexedData = $indexDoc->getData();
        self::assertEquals('content', $indexedData['content']->getValue());
        self::assertEquals('content', $indexedData['abstract']->getValue());

        $indexDoc = $indexer->prepareSearchData(
            'doesnt_matter',
            ['uid' => 123, 'title' => 'title', 'alternative' => 'content'],
            $indexDoc,
            ['indexMode' => 'none']
        );

        $indexedData = $indexDoc->getData();
        self::assertEquals('content', $indexedData['content']->getValue());
        self::assertEquals('content', $indexedData['abstract']->getValue());

        $indexDoc = $indexer->prepareSearchData(
            'doesnt_matter',
            ['uid' => 123, 'title' => 'title'],
            $indexDoc,
            ['indexMode' => 'none']
        );

        $indexedData = $indexDoc->getData();
        self::assertEmpty($indexedData['content']->getValue());
        self::assertEmpty($indexedData['abstract']->getValue());
    }

    /**
     * @param array $mockedMethods
     *
     * @return Ambigous <PHPUnit_Framework_MockObject_MockObject, tx_mksearch_indexer_BaseMedia>
     */
    private function getIndexerMock(
        array $mockedMethods = [
            'getBaseTableName', 'getFileExtension',
            'getFilePath', 'getRelFileName', 'stopIndexing',
        ]
    ) {
        return $this->getMockForAbstractClass(
            'tx_mksearch_indexer_BaseMedia',
            [],
            '',
            false,
            false,
            true,
            $mockedMethods
        );
    }
}
