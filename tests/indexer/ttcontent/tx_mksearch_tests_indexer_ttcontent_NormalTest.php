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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_indexer_ttcontent_NormalTest extends tx_mksearch_tests_Testcase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!GeneralUtility::makeInstance(CacheManager::class)->hasCache('runtime')) {
            GeneralUtility::makeInstance(CacheManager::class)->registerCache(new NullFrontend('runtime'));
        }
    }

    public function testPrepareSearchDataIncludesPageMetaKeywords(): void
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['getPageContent', 'isIndexableRecord', 'hasDocToBeDeleted']
        );
        $indexer->expects($this->once())
            ->method('isIndexableRecord')
            ->willReturn(true);
        $indexer->expects($this->once())
            ->method('hasDocToBeDeleted')
            ->willReturn(false);
        $indexer->expects($this->any())
            ->method('getPageContent')
            ->with(456)
            ->willReturn(['keywords' => 'first,second']);

        [$extKey, $cType] = $indexer->getContentType();

        $record = ['uid' => 123, 'pid' => 456, 'CType' => 'list', 'bodytext' => 'lorem'];
        $indexDoc = GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $options = $this->getDefaultOptions();
        $options['addPageMetaData'] = 1;
        $options['addPageMetaData.']['separator'] = ',';
        $options['includeCTypes.'] = ['search', 'mailform', 'list'];
        $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        $indexDocData = $indexDoc->getData();

        self::assertEquals(['first', 'second'], $indexDocData['keywords_ms']->getValue());
    }

    public function testIndexDataCallsIndexPageDataIfConfigured(): void
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['indexPageData', 'getModelToIndex']
        );

        $indexDoc = GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', 'mksearch', 'test');

        $record = ['uid' => 123];
        $model = GeneralUtility::makeInstance(Sys25\RnBase\Domain\Model\BaseModel::class, $record);
        $model->setTableName('tt_Content');

        $options = $this->getDefaultOptions();
        $options['indexPageData'] = 1;

        $indexer->expects($this->once())
            ->method('indexPageData')
            ->with($indexDoc, $options);
        $indexer
            ->expects($this->once())
            ->method('getModelToIndex')
            ->willReturn(
                $this->getModel($record)
            );

        $this->callInaccessibleMethod($indexer, 'indexData', $model, 'tt_content', $record, $indexDoc, $options);
    }

    public function testIndexDataCallsIndexPageDataNotIfNotConfigured(): void
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['indexPageData', 'getModelToIndex']
        );

        $indexDoc = GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', 'mksearch', 'test');

        $record = ['uid' => 123];
        $model = GeneralUtility::makeInstance(Sys25\RnBase\Domain\Model\BaseModel::class, $record);
        $model->setTableName('tt_Content');

        $options = $this->getDefaultOptions();
        $options['indexPageData'] = 0;

        $indexer
        ->expects($this->never())
            ->method('indexPageData');
        $indexer
            ->expects($this->once())
            ->method('getModelToIndex')
            ->willReturn(
                $this->getModel($record)
            );

        $this->callInaccessibleMethod($indexer, 'indexData', $model, 'tt_content', $record, $indexDoc, $options);
    }

    public function testIndexPageData(): void
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['getPageContent']
        );

        $indexDoc = GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', 'mksearch', 'test');

        $model = GeneralUtility::makeInstance(Sys25\RnBase\Domain\Model\BaseModel::class, ['uid' => 123]);
        $model->setTableName('tt_Content');

        $indexer->_set('modelToIndex', $model);

        $options = [
            'pageDataFieldMapping.' => [
                'title' => 'title_t',
                'description' => 'description_t',
            ],
        ];

        $indexer->expects($this->once())
            ->method('getPageContent')
            ->willReturn([
                'title' => 'Homepage',
                'description' => 'Starting point',
                'subtitle' => 'not to be indexed',
            ]);

        $this->callInaccessibleMethod($indexer, 'indexPageData', $indexDoc, $options);

        $indexedData = $indexDoc->getData();

        self::assertCount(3, $indexedData, 'more fields than expected indexed');
        self::assertEquals(
            'mksearch.test',
            $indexedData['content_ident_s']->getValue(),
            'content_ident_s wrong'
        );
        self::assertEquals(
            'Homepage',
            $indexedData['page_title_t']->getValue(),
            'page title wrong indexed'
        );
        self::assertEquals(
            'Starting point',
            $indexedData['page_description_t']->getValue(),
            'page description wrong indexed'
        );
    }

    /**
     * @return multitype:string
     */
    private function getDefaultOptions(): array
    {
        $options = [];
        $options['CType.']['_default_.']['indexedFields.'] = [
            'bodytext', 'imagecaption', 'altText', 'titleText',
        ];

        return $options;
    }

    public function testIsPageSetIncludeInSearchDisableIfPageIsSetDisable(): void
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['shouldRespectIncludeInSearchDisable', 'getPageContent']
        );
        $options = [];
        $model = GeneralUtility::makeInstance(Sys25\RnBase\Domain\Model\BaseModel::class, ['pid' => 123]);

        $indexer->expects($this->once())
            ->method('shouldRespectIncludeInSearchDisable')
            ->with($options)
            ->willReturn(true);

        $indexer->expects($this->once())
            ->method('getPageContent')
            ->with(123)
            ->willReturn(['no_search' => 1]);

        self::assertTrue(
            $this->callInaccessibleMethod(
                $indexer,
                'isPageSetIncludeInSearchDisable',
                $model,
                $options
            )
        );
    }

    public function testIsPageSetIncludeInSearchDisableIfPageIsNotSetDisable(): void
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['shouldRespectIncludeInSearchDisable', 'getPageContent']
        );
        $options = [];
        $model = GeneralUtility::makeInstance(Sys25\RnBase\Domain\Model\BaseModel::class, ['pid' => 123]);

        $indexer->expects($this->once())
            ->method('shouldRespectIncludeInSearchDisable')
            ->with($options)
            ->willReturn(true);

        $indexer->expects($this->once())
            ->method('getPageContent')
            ->with(123)
            ->willReturn(['no_search' => 0]);

        self::assertFalse(
            $this->callInaccessibleMethod(
                $indexer,
                'isPageSetIncludeInSearchDisable',
                $model,
                $options
            )
        );
    }

    public function testIsPageSetIncludeInSearchDisableIfPageIsNotValid(): void
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['shouldRespectIncludeInSearchDisable', 'getPageContent']
        );
        $options = [];
        $model = GeneralUtility::makeInstance(Sys25\RnBase\Domain\Model\BaseModel::class, ['pid' => 123]);

        $indexer->expects($this->once())
            ->method('shouldRespectIncludeInSearchDisable')
            ->with($options)
            ->willReturn(true);

        $indexer->expects($this->once())
            ->method('getPageContent')
            ->with(123)
            ->willReturn([]);

        self::assertFalse(
            $this->callInaccessibleMethod(
                $indexer,
                'isPageSetIncludeInSearchDisable',
                $model,
                $options
            )
        );
    }

    #[PHPUnit\Framework\Attributes\DataProvider('getTestDataForShouldRespectIncludeInSearchDisable')]
    public function testShouldRespectIncludeInSearchDisable(array $options, bool $expected): void
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['checkCTypes']
        );

        self::assertEquals(
            $expected,
            $this->callInaccessibleMethod(
                $indexer,
                'shouldRespectIncludeInSearchDisable',
                $options
            )
        );
    }

    public static function getTestDataForShouldRespectIncludeInSearchDisable(): array
    {
        return [
            1 => [
                'options' => ['respectIncludeInSearchDisable' => '1'],
                'expected' => true,
            ],
            2 => [
                'options' => ['respectIncludeInSearchDisable' => '0'],
                'expected' => false,
            ],
            3 => [
                'options' => [],
                'expected' => false,
            ],
        ];
    }

    public function testIsIndexableRecordWithIsOnIndexablePage(): void
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['isOnIndexablePage', 'checkCTypes', 'isIndexableColumn']
        );
        new Reflection('tx_mksearch_indexer_ttcontent_Normal');
        new ReflectionClass('tx_mksearch_indexer_ttcontent_Normal');

        $sourceRecord = [];
        $options = $this->getDefaultOptions();
        $options['include.']['columns'] = '0,1';

        $indexer->expects(self::once())
            ->method('isOnIndexablePage')
            ->with($sourceRecord, $options)
            ->willReturn(false);

        $indexer->expects(self::never())
            ->method('checkCTypes');

        $indexer->expects(self::never())
            ->method('isIndexableColumn');

        self::assertEquals(
            false,
            $this->callInaccessibleMethod(
                $indexer,
                'isIndexableRecord',
                $sourceRecord,
                $options
            )
        );
    }

    public function testIsIndexableRecordWithIsOnIndexablePageAndCheckCTypes(): void
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['isOnIndexablePage', 'checkCTypes', 'isIndexableColumn']
        );

        $sourceRecord = [];
        $options = $this->getDefaultOptions();
        $options['include.']['columns'] = '0,1';

        $indexer->expects(self::once())
            ->method('isOnIndexablePage')
            ->with($sourceRecord, $options)
            ->willReturn(true);

        $indexer->expects(self::once())
            ->method('checkCTypes')
            ->with($sourceRecord, $options)
            ->willReturn(false);

        $indexer->expects(self::never())
            ->method('isIndexableColumn');

        self::assertEquals(
            false,
            $this->callInaccessibleMethod(
                $indexer,
                'isIndexableRecord',
                $sourceRecord,
                $options
            )
        );
    }

    public function testIsIndexableRecordWithIsOnIndexablePageAndCheckCTypesAndIsIndexableColumn(): void
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['isOnIndexablePage', 'checkCTypes', 'isIndexableColumn']
        );

        $sourceRecord = [];
        $options = $this->getDefaultOptions();
        $options['include.']['columns'] = '0,1';

        $indexer->expects(self::once())
            ->method('isOnIndexablePage')
            ->with($sourceRecord, $options)
            ->willReturn(true);

        $indexer->expects(self::once())
            ->method('checkCTypes')
            ->with($sourceRecord, $options)
            ->willReturn(true);

        $indexer->expects(self::once())
            ->method('isIndexableColumn')
            ->with($sourceRecord, $options)
            ->willReturn(true);

        self::assertEquals(
            true,
            $this->callInaccessibleMethod(
                $indexer,
                'isIndexableRecord',
                $sourceRecord,
                $options
            )
        );
    }

    #[PHPUnit\Framework\Attributes\DataProvider('getTestDataForIsIndexableRecordWithAllMethodPossibilities')]
    public function testIsIndexableRecordWithAllMethodPossibilities(array $sourceRecord, array $expected): void
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['isOnIndexablePage', 'checkCTypes', 'isIndexableColumn']
        );

        $options = $this->getDefaultOptions();
        $options['include.']['columns'] = '0,1';

        if ('never' == $expected['isOnIndexablePage']['expects']) {
            $indexer->expects(self::never())
                ->method('checkCTypes');
        } else {
            $indexer->expects(self::once())
                ->method('isOnIndexablePage')
                ->with($sourceRecord, $options)
                ->willReturn($expected['isOnIndexablePage']['value']);
        }

        if ('never' == $expected['checkCTypes']['expects']) {
            $indexer->expects(self::never())
                ->method('checkCTypes');
        } else {
            $indexer->expects(self::once())
                ->method('checkCTypes')
                ->with($sourceRecord, $options)
                ->willReturn($expected['checkCTypes']['value']);
        }

        if ('never' == $expected['isIndexableColumn']['expects']) {
            $indexer->expects(self::never())
                ->method('isIndexableColumn');
        } else {
            $indexer->expects(self::once())
                ->method('isIndexableColumn')
                ->with($sourceRecord, $options)
                ->willReturn($expected['isIndexableColumn']['value']);
        }

        self::assertEquals(
            $expected['isIndexableRecord'],
            $this->callInaccessibleMethod(
                $indexer,
                'isIndexableRecord',
                $sourceRecord,
                $options
            )
        );
    }

    public static function getTestDataForIsIndexableRecordWithAllMethodPossibilities(): array
    {
        return [
            1 => [
                'sourceRecord' => ['colPos' => -1],
                'expected' => [
                    'isIndexableRecord' => false,
                    'isOnIndexablePage' => ['expects' => '', 'value' => true],
                    'checkCTypes' => ['expects' => '', 'value' => false],
                    'isIndexableColumn' => ['expects' => 'never', 'value' => false],
                ],
            ],
            2 => [
                'sourceRecord' => ['colPos' => -1],
                'expected' => [
                    'isIndexableRecord' => false,
                    'isOnIndexablePage' => ['expects' => '', 'value' => true],
                    'checkCTypes' => ['expects' => '', 'value' => true],
                    'isIndexableColumn' => ['expects' => '', 'value' => false],
                ],
            ],
            3 => [
                'sourceRecord' => ['colPos' => 0],
                'expected' => [
                    'isIndexableRecord' => true,
                    'isOnIndexablePage' => ['expects' => '', 'value' => true],
                    'checkCTypes' => ['expects' => '', 'value' => true],
                    'isIndexableColumn' => ['expects' => '', 'value' => true],
                ],
            ],
            4 => [
                'sourceRecord' => ['colPos' => 1],
                'expected' => [
                    'isIndexableRecord' => false,
                    'isOnIndexablePage' => ['expects' => '', 'value' => false],
                    'checkCTypes' => ['expects' => 'never', 'value' => true],
                    'isIndexableColumn' => ['expects' => 'never', 'value' => false],
                ],
            ],
            5 => [
                'sourceRecord' => ['colPos' => 0],
                'expected' => [
                    'isIndexableRecord' => false,
                    'isOnIndexablePage' => ['expects' => '', 'value' => false],
                    'checkCTypes' => ['expects' => 'never', 'value' => true],
                    'isIndexableColumn' => ['expects' => 'never', 'value' => true],
                ],
            ],
            6 => [
                'sourceRecord' => ['colPos' => 0],
                'expected' => [
                    'isIndexableRecord' => false,
                    'isOnIndexablePage' => ['expects' => '', 'value' => false],
                    'checkCTypes' => ['expects' => 'never', 'value' => false],
                    'isIndexableColumn' => ['expects' => 'never', 'value' => true],
                ],
            ],
            7 => [
                'sourceRecord' => ['colPos' => -1],
                'expected' => [
                    'isIndexableRecord' => false,
                    'isOnIndexablePage' => ['expects' => '', 'value' => false],
                    'checkCTypes' => ['expects' => 'never', 'value' => false],
                    'isIndexableColumn' => ['expects' => 'never', 'value' => false],
                ],
            ],
            8 => [
                'sourceRecord' => ['colPos' => -1, 'tx_mksearch_is_indexable' => tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION],
                'expected' => [
                    'isIndexableRecord' => false,
                    'isOnIndexablePage' => ['expects' => '', 'value' => true],
                    'checkCTypes' => ['expects' => '', 'value' => false],
                    'isIndexableColumn' => ['expects' => 'never', 'value' => false],
                ],
            ],
            9 => [
                'sourceRecord' => ['colPos' => -1, 'tx_mksearch_is_indexable' => tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION],
                'expected' => [
                    'isIndexableRecord' => false,
                    'isOnIndexablePage' => ['expects' => '', 'value' => true],
                    'checkCTypes' => ['expects' => '', 'value' => true],
                    'isIndexableColumn' => ['expects' => '', 'value' => false],
                ],
            ],
            10 => [
                'sourceRecord' => ['colPos' => 0, 'tx_mksearch_is_indexable' => tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION],
                'expected' => [
                    'isIndexableRecord' => true,
                    'isOnIndexablePage' => ['expects' => '', 'value' => true],
                    'checkCTypes' => ['expects' => '', 'value' => true],
                    'isIndexableColumn' => ['expects' => '', 'value' => true],
                ],
            ],
            11 => [
                'sourceRecord' => ['colPos' => 1, 'tx_mksearch_is_indexable' => tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION],
                'expected' => [
                    'isIndexableRecord' => false,
                    'isOnIndexablePage' => ['expects' => '', 'value' => false],
                    'checkCTypes' => ['expects' => 'never', 'value' => true],
                    'isIndexableColumn' => ['expects' => 'never', 'value' => false],
                ],
            ],
            12 => [
                'sourceRecord' => ['colPos' => 0, 'tx_mksearch_is_indexable' => tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION],
                'expected' => [
                    'isIndexableRecord' => false,
                    'isOnIndexablePage' => ['expects' => '', 'value' => false],
                    'checkCTypes' => ['expects' => 'never', 'value' => true],
                    'isIndexableColumn' => ['expects' => 'never', 'value' => true],
                ],
            ],
            13 => [
                'sourceRecord' => ['colPos' => 0, 'tx_mksearch_is_indexable' => tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION],
                'expected' => [
                    'isIndexableRecord' => false,
                    'isOnIndexablePage' => ['expects' => '', 'value' => false],
                    'checkCTypes' => ['expects' => 'never', 'value' => false],
                    'isIndexableColumn' => ['expects' => 'never', 'value' => true],
                ],
            ],
            14 => [
                'sourceRecord' => ['colPos' => -1, 'tx_mksearch_is_indexable' => tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION],
                'expected' => [
                    'isIndexableRecord' => false,
                    'isOnIndexablePage' => ['expects' => '', 'value' => false],
                    'checkCTypes' => ['expects' => 'never', 'value' => false],
                    'isIndexableColumn' => ['expects' => 'never', 'value' => false],
                ],
            ],
        ];
    }

    public function testIsIndexableRecordWithoutDefinedColumnsAndColPos(): void
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['isOnIndexablePage', 'checkCTypes', 'isIndexableColumn']
        );

        $sourceRecord = [];

        $options = $this->getDefaultOptions();

        $indexer->expects($this->once())
            ->method('isOnIndexablePage')
            ->with($sourceRecord, $options)
            ->willReturn(true);

        $indexer->expects($this->once())
            ->method('checkCTypes')
            ->with($sourceRecord, $options)
            ->willReturn(true);

        $indexer->expects($this->once())
            ->method('isIndexableColumn')
            ->with($sourceRecord, $options)
            ->willReturn(true);

        self::assertEquals(
            true,
            $this->callInaccessibleMethod(
                $indexer,
                'isIndexableRecord',
                $sourceRecord,
                $options
            )
        );
    }

    #[PHPUnit\Framework\Attributes\DataProvider('getTestDataForIsIndexableRecordWithIndexableAndWithoutMethods')]
    public function testIsIndexableRecordWithIndexableAndWithoutMethods(array $sourceRecord, array $options, bool $expected): void
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['isOnIndexablePage', 'checkCTypes', 'isIndexableColumn']
        );

        $indexer->expects($this->never())
            ->method('isOnIndexablePage');

        $indexer->expects($this->never())
            ->method('checkCTypes');

        $indexer->expects($this->never())
            ->method('isIndexableColumn');

        self::assertEquals(
            $expected,
            $this->callInaccessibleMethod(
                $indexer,
                'isIndexableRecord',
                $sourceRecord,
                $options
            )
        );
    }

    public static function getTestDataForIsIndexableRecordWithIndexableAndWithoutMethods(): array
    {
        return [
            1 => [
                'sourceRecord' => ['tx_mksearch_is_indexable' => tx_mksearch_indexer_ttcontent_Normal::IS_INDEXABLE],
                'options' => [],
                'expected' => true,
            ],
            2 => [
                'sourceRecord' => ['tx_mksearch_is_indexable' => tx_mksearch_indexer_ttcontent_Normal::IS_NOT_INDEXABLE],
                'options' => [],
                'expected' => false,
            ],
        ];
    }

    public function testIsIndexableColumnWithColPosAndColumns(): void
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal'
        );

        $sourceRecord = ['colPos' => 1];
        $options = ['include.' => ['columns' => '0,1']];

        self::assertEquals(
            true,
            $this->callInaccessibleMethod(
                $indexer,
                'isIndexableColumn',
                $sourceRecord,
                $options
            )
        );
    }

    public function testIsIndexableColumnWithColPosAndWithoutColumns(): void
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal'
        );

        $sourceRecord = ['colPos' => 1];
        $options = [];

        self::assertEquals(
            true,
            $this->callInaccessibleMethod(
                $indexer,
                'isIndexableColumn',
                $sourceRecord,
                $options
            )
        );
    }

    public function testIsIndexableColumnWithoutColPosAndWithColumns(): void
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal'
        );

        $sourceRecord = [];
        $options = ['include.' => ['columns' => '0,1']];

        self::assertEquals(
            false,
            $this->callInaccessibleMethod(
                $indexer,
                'isIndexableColumn',
                $sourceRecord,
                $options
            )
        );
    }

    public function testIsIndexableColumnWithoutColPosAndColumns(): void
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal'
        );

        $sourceRecord = [];
        $options = [];

        self::assertEquals(
            true,
            $this->callInaccessibleMethod(
                $indexer,
                'isIndexableColumn',
                $sourceRecord,
                $options
            )
        );
    }

    public function testHasDocToBeDeletedIfNoPageContent(): void
    {
        $indexDoc = $this->getIndexDocMock('core', 'tt_content');
        $ttContentModel = $this->getModel(['pid' => 123]);

        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['getPageContent']
        );
        $indexer
            ->expects(self::once())
            ->method('getPageContent')
            ->with(123)
            ->willReturn([]);

        self::assertTrue(
            $this->callInaccessibleMethod(
                [$indexer, 'hasDocToBeDeleted'],
                [$ttContentModel, $indexDoc]
            )
        );
    }

    #[PHPUnit\Framework\Attributes\DataProvider('dataProviderDokTypes')]
    public function testHasDocToBeDeletedDependentOnDokType(int|string $dokType, bool $hasToBeDeleted = true, array $options = []): void
    {
        self::markTestIncomplete('The requested database connection named "Default" has not been configured.');

        $indexDoc = $this->getIndexDocMock('core', 'tt_content');
        $ttContentModel = $this->getModel(['pid' => 123]);

        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['getPageContent']
        );
        $indexer
            ->expects(self::any())
            ->method('getPageContent')
            ->with(123)
            ->willReturn(['doktype' => $dokType]);

        self::assertSame(
            $hasToBeDeleted,
            $this->callInaccessibleMethod(
                [$indexer, 'hasDocToBeDeleted'],
                [$ttContentModel, $indexDoc, $options]
            )
        );
    }

    public static function dataProviderDokTypes(): array
    {
        return [
            [TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_SYSFOLDER],
            [255],
            [TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_LINK],
            ['test'],
            [0],
            [TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_DEFAULT, false],
            [TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_DEFAULT, true, ['supportedDokTypes' => '123,456']],
            [123, false, ['supportedDokTypes' => '123,456']],
        ];
    }
}
