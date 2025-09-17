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
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_indexer_BaseMediaTest extends tx_mksearch_tests_Testcase
{
    public function testPrepareSearchDataCallsStopIndexing(): void
    {
        $indexer = $this->getIndexerMock();

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
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

    public function testPrepareSearchDataReturnsNullIfStopIndexing(): void
    {
        $indexer = $this->getIndexerMock();

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            'core',
            'tt_content'
        );
        $options = [];
        $sourceRecord = ['uid' => 1, 'test_field_1' => 'test value 1'];

        $indexer->expects($this->once())
            ->method('stopIndexing')
            ->willReturn(true);

        self::assertNull($indexer->prepareSearchData(
            'tt_content',
            $sourceRecord,
            $indexDoc,
            $options
        ));
    }

    public function testPrepareSearchDataReturnsNotNullIfNotStopIndexing(): void
    {
        $indexer = $this->getIndexerMock();

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            'core',
            'tt_content'
        );
        $options = [];
        $sourceRecord = ['uid' => 1, 'test_field_1' => 'test value 1'];

        $indexer->expects($this->once())
            ->method('stopIndexing')
            ->willReturn(false);

        self::assertNotNull($indexer->prepareSearchData(
            'tt_content',
            $sourceRecord,
            $indexDoc,
            $options
        ));
    }

    public function testGetIndexerUtility(): void
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

    public function testStopIndexingCallsIndexerUtility(): void
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

    public function testPrepareSearchDataCallsIsIndexableRecordNotIfRecordIsDeleted(): void
    {
        $indexer = $this->getIndexerMock([
            'getBaseTableName', 'getFileExtension',
            'getFilePath', 'getRelFileName', 'stopIndexing', 'isIndexableRecord',
        ]);

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
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

    public function testPrepareSearchDataCallsIsIndexableRecordNotIfRecordIsHidden(): void
    {
        $indexer = $this->getIndexerMock([
            'getBaseTableName', 'getFileExtension',
            'getFilePath', 'getRelFileName', 'stopIndexing', 'isIndexableRecord',
        ]);

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
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

    public function testPrepareSearchDataSetsDocDeletedIfRecordIsDeleted(): void
    {
        $indexer = $this->getIndexerMock([
            'getBaseTableName', 'getFileExtension',
            'getFilePath', 'getRelFileName', 'stopIndexing', 'isIndexableRecord',
        ]);

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
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

    public function testPrepareSearchDataSetsDocDeletedIfRecordIsHidden(): void
    {
        $indexer = $this->getIndexerMock([
            'getBaseTableName', 'getFileExtension',
            'getFilePath', 'getRelFileName', 'stopIndexing', 'isIndexableRecord',
        ]);

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
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

    #[PHPUnit\Framework\Attributes\DataProvider('providerHasDocToBeDeleted')]
    public function testHasDocToBeDeleted(array $sourceRecord, bool $expected): void
    {
        $indexer = $this->getIndexerMock();
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
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

    public static function providerHasDocToBeDeleted(): array
    {
        return [
            1 => [
                'sourceRecord' => ['uid' => 7],
                'expected' => false,
            ],
            2 => [
                'sourceRecord' => ['uid' => 7, 'deleted' => 1],
                'expected' => true,
            ],
            3 => [
                'sourceRecord' => ['uid' => 7, 'hidden' => 1],
                'expected' => true,
            ],
            4 => [
                'sourceRecord' => ['uid' => 7, 'hidden' => 1, 'deleted' => 1],
                'expected' => true,
            ],
        ];
    }

    public function testGroupFieldIsAdded(): void
    {
        $indexer = $this->getIndexerMock([
            'getBaseTableName', 'getFileExtension',
            'getFilePath', 'getRelFileName', 'stopIndexing', 'isIndexableRecord',
        ]);
        $indexer->expects(self::once())
            ->method('isIndexableRecord')
            ->willReturn(true);

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', 'core', 'file');

        $rawData = ['uid' => 123];

        $indexDoc = $indexer->prepareSearchData('doesnt_matter', $rawData, $indexDoc, []);

        $indexedData = $indexDoc->getData();
        self::assertEquals('core:file:123', $indexedData['group_s']->getValue());
    }

    public function testContentAndAbstractFromSourceRecordAreIndexedCorrect(): void
    {
        $indexer = $this->getIndexerMock([
            'getBaseTableName', 'getFileExtension',
            'getFilePath', 'getRelFileName', 'stopIndexing', 'isIndexableRecord',
        ]);
        $indexer->expects(self::any())
            ->method('isIndexableRecord')
            ->willReturn(true);

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', 'core', 'file');

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
     * @return PHPUnit_Framework_MockObject_MockObject|tx_mksearch_indexer_BaseMedia
     */
    private function getIndexerMock(
        array $mockedMethods = [
            'getBaseTableName', 'getFileExtension',
            'getFilePath', 'getRelFileName', 'stopIndexing',
        ],
    ): PHPUnit\Framework\MockObject\MockObject {
        if (!in_array('getContentType', $mockedMethods, true)) {
            $mockedMethods[] = 'getContentType';
        }

        $mock = $this->getMockBuilder('tx_mksearch_indexer_BaseMedia')
            ->onlyMethods($mockedMethods)
            ->getMock();

        $mock->expects(self::any())
            ->method('getFilePath')
            ->willReturn('');

        return $mock;
    }
}
