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
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_indexer_ttcontent_NormalTest extends tx_mksearch_tests_Testcase
{
    /**
     * @group unit
     */
    public function testPrepareSearchDataIncludesPageMetaKeywords()
    {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['getPageContent', 'isIndexableRecord', 'hasDocToBeDeleted']
        );
        $indexer->expects($this->once())
            ->method('isIndexableRecord')
            ->will($this->returnValue(true));
        $indexer->expects($this->once())
            ->method('hasDocToBeDeleted')
            ->will($this->returnValue(false));
        $indexer->expects($this->any())
            ->method('getPageContent')
            ->with(456)
            ->will($this->returnValue(['keywords' => 'first,second']));

        list($extKey, $cType) = $indexer->getContentType();

        $record = ['uid' => 123, 'pid' => 456, 'CType' => 'list', 'bodytext' => 'lorem'];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $options = self::getDefaultOptions();
        $options['addPageMetaData'] = 1;
        $options['addPageMetaData.']['separator'] = ',';
        $options['includeCTypes.'] = ['search', 'mailform', 'list'];
        $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        $indexDocData = $indexDoc->getData();

        self::assertEquals(['first', 'second'], $indexDocData['keywords_ms']->getValue());
    }

    /**
     * @group unit
     */
    public function testIndexDataCallsIndexPageDataIfConfigured()
    {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['indexPageData', 'getModelToIndex']
        );

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', 'mksearch', 'test');

        $record = ['uid' => 123];
        $model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Domain\Model\BaseModel::class, $record);
        $model->setTableName('tt_Content');
        $options = self::getDefaultOptions();
        $options['indexPageData'] = 1;

        $indexer->expects($this->once())
            ->method('indexPageData')
            ->with($indexDoc, $options);
        $indexer
            ->expects($this->once())
            ->method('getModelToIndex')
            ->will(
                $this->returnValue(
                    $this->getModel($record)
                )
            );

        $indexer->indexData($model, 'tt_content', $record, $indexDoc, $options);
    }

    /**
     * @group unit
     */
    public function testIndexDataCallsIndexPageDataNotIfNotConfigured()
    {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['indexPageData', 'getModelToIndex']
        );

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', 'mksearch', 'test');

        $record = ['uid' => 123];
        $model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Domain\Model\BaseModel::class, $record);
        $model->setTableName('tt_Content');
        $options = self::getDefaultOptions();
        $options['indexPageData'] = 0;

        $indexer
        ->expects($this->never())
            ->method('indexPageData');
        $indexer
            ->expects($this->once())
            ->method('getModelToIndex')
            ->will(
                $this->returnValue(
                    $this->getModel($record)
                )
            );

        $indexer->indexData($model, 'tt_content', $record, $indexDoc, $options);
    }

    /**
     * @group unit
     */
    public function testIndexPageData()
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['getPageContent']
        );

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', 'mksearch', 'test');

        $model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Domain\Model\BaseModel::class, ['uid' => 123]);
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
            ->will($this->returnValue([
                'title' => 'Homepage',
                'description' => 'Starting point',
                'subtitle' => 'not to be indexed',
            ]));

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
    private static function getDefaultOptions()
    {
        $options = [];
        $options['CType.']['_default_.']['indexedFields.'] = [
            'bodytext', 'imagecaption', 'altText', 'titleText',
        ];

        return $options;
    }

    /**
     * @group unit
     */
    public function testIsPageSetIncludeInSearchDisableIfPageIsSetDisable()
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['shouldRespectIncludeInSearchDisable', 'getPageContent']
        );
        $options = [];
        $model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Domain\Model\BaseModel::class, ['pid' => 123]);

        $indexer->expects($this->once())
            ->method('shouldRespectIncludeInSearchDisable')
            ->with($options)
            ->will($this->returnValue([true]));

        $indexer->expects($this->once())
            ->method('getPageContent')
            ->with(123)
            ->will($this->returnValue(['no_search' => 1]));

        self::assertTrue(
            $this->callInaccessibleMethod(
                $indexer,
                'isPageSetIncludeInSearchDisable',
                $model,
                $options
            )
        );
    }

    /**
     * @group unit
     */
    public function testIsPageSetIncludeInSearchDisableIfPageIsNotSetDisable()
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['shouldRespectIncludeInSearchDisable', 'getPageContent']
        );
        $options = [];
        $model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Domain\Model\BaseModel::class, ['pid' => 123]);

        $indexer->expects($this->once())
            ->method('shouldRespectIncludeInSearchDisable')
            ->with($options)
            ->will($this->returnValue([true]));

        $indexer->expects($this->once())
            ->method('getPageContent')
            ->with(123)
            ->will($this->returnValue(['no_search' => 0]));

        self::assertFalse(
            $this->callInaccessibleMethod(
                $indexer,
                'isPageSetIncludeInSearchDisable',
                $model,
                $options
            )
        );
    }

    /**
     * @group unit
     */
    public function testIsPageSetIncludeInSearchDisableIfPageIsNotValid()
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['shouldRespectIncludeInSearchDisable', 'getPageContent']
        );
        $options = [];
        $model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Domain\Model\BaseModel::class, ['pid' => 123]);

        $indexer->expects($this->once())
            ->method('shouldRespectIncludeInSearchDisable')
            ->with($options)
            ->will($this->returnValue([true]));

        $indexer->expects($this->once())
            ->method('getPageContent')
            ->with(123)
            ->will($this->returnValue([]));

        self::assertFalse(
            $this->callInaccessibleMethod(
                $indexer,
                'isPageSetIncludeInSearchDisable',
                $model,
                $options
            )
        );
    }

    /**
     * @group unit
     * @dataProvider getTestDataForShouldRespectIncludeInSearchDisable
     */
    public function testShouldRespectIncludeInSearchDisable($options, $expected)
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

    public function getTestDataForShouldRespectIncludeInSearchDisable()
    {
        return [
            __LINE__ => [
                'options' => ['respectIncludeInSearchDisable' => '1'],
                'expected' => true,
            ],
            __LINE__ => [
                'options' => ['respectIncludeInSearchDisable' => '0'],
                'expected' => false,
            ],
            __LINE__ => [
                'options' => [],
                'expected' => false,
            ],
        ];
    }

    /**
     * @group unit
     */
    public function testIsIndexableRecordWithIsOnIndexablePage()
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['isOnIndexablePage', 'checkCTypes', 'isIndexableColumn']
        );
        $reflection = new Reflection('tx_mksearch_indexer_ttcontent_Normal');
        $class = new ReflectionClass('tx_mksearch_indexer_ttcontent_Normal');

        $sourceRecord = [];
        $options = self::getDefaultOptions();
        $options['include.']['columns'] = '0,1';

        $indexer->expects(self::once())
            ->method('isOnIndexablePage')
            ->with($sourceRecord, $options)
            ->will($this->returnValue(false));

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

    /**
     * @group unit
     */
    public function testIsIndexableRecordWithIsOnIndexablePageAndCheckCTypes()
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['isOnIndexablePage', 'checkCTypes', 'isIndexableColumn']
        );

        $sourceRecord = [];
        $options = self::getDefaultOptions();
        $options['include.']['columns'] = '0,1';

        $indexer->expects(self::once())
            ->method('isOnIndexablePage')
            ->with($sourceRecord, $options)
            ->will($this->returnValue(true));

        $indexer->expects(self::once())
            ->method('checkCTypes')
            ->with($sourceRecord, $options)
            ->will($this->returnValue(false));

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

    /**
     * @group unit
     */
    public function testIsIndexableRecordWithIsOnIndexablePageAndCheckCTypesAndIsIndexableColumn()
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['isOnIndexablePage', 'checkCTypes', 'isIndexableColumn']
        );

        $sourceRecord = [];
        $options = self::getDefaultOptions();
        $options['include.']['columns'] = '0,1';

        $indexer->expects(self::once())
            ->method('isOnIndexablePage')
            ->with($sourceRecord, $options)
            ->will($this->returnValue(true));

        $indexer->expects(self::once())
            ->method('checkCTypes')
            ->with($sourceRecord, $options)
            ->will($this->returnValue(true));

        $indexer->expects(self::once())
            ->method('isIndexableColumn')
            ->with($sourceRecord, $options)
            ->will($this->returnValue(true));

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

    /**
     * @group unit
     * @dataProvider getTestDataForIsIndexableRecordWithAllMethodPossibilities
     */
    public function testIsIndexableRecordWithAllMethodPossibilities($sourceRecord, $expected)
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['isOnIndexablePage', 'checkCTypes', 'isIndexableColumn']
        );

        $options = self::getDefaultOptions();
        $options['include.']['columns'] = '0,1';

        $indexer->expects($expected['isOnIndexablePage']['expects'])
            ->method('isOnIndexablePage')
            ->with($sourceRecord, $options)
            ->will($this->returnValue($expected['isOnIndexablePage']['value']));

        if ($expected['checkCTypes']['expects'] == self::never()) {
            $indexer->expects(self::never())
                ->method('checkCTypes');
        } else {
            $indexer->expects($expected['checkCTypes']['expects'])
                ->method('checkCTypes')
                ->with($sourceRecord, $options)
                ->will($this->returnValue($expected['checkCTypes']['value']));
        }

        if ($expected['isIndexableColumn']['expects'] == self::never()) {
            $indexer->expects(self::never())
                ->method('isIndexableColumn');
        } else {
            $indexer->expects($expected['isIndexableColumn']['expects'])
                ->method('isIndexableColumn')
                ->with($sourceRecord, $options)
                ->will($this->returnValue($expected['isIndexableColumn']['value']));
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

    public function getTestDataForIsIndexableRecordWithAllMethodPossibilities()
    {
        return [
            __LINE__ => [
                'sourceRecord' => ['colPos' => -1],
                'expected' => [
                    'isIndexableRecord' => false,
                    'isOnIndexablePage' => ['expects' => self::once(), 'value' => true],
                    'checkCTypes' => ['expects' => self::once(), 'value' => false],
                    'isIndexableColumn' => ['expects' => self::never(), 'value' => false],
                ],
            ],
            __LINE__ => [
                'sourceRecord' => ['colPos' => -1],
                'expected' => [
                    'isIndexableRecord' => false,
                    'isOnIndexablePage' => ['expects' => self::once(), 'value' => true],
                    'checkCTypes' => ['expects' => self::once(), 'value' => true],
                    'isIndexableColumn' => ['expects' => self::once(), 'value' => false],
                ],
            ],
            __LINE__ => [
                'sourceRecord' => ['colPos' => 0],
                'expected' => [
                    'isIndexableRecord' => true,
                    'isOnIndexablePage' => ['expects' => self::once(), 'value' => true],
                    'checkCTypes' => ['expects' => self::once(), 'value' => true],
                    'isIndexableColumn' => ['expects' => self::once(), 'value' => true],
                ],
            ],
            __LINE__ => [
                'sourceRecord' => ['colPos' => 1],
                'expected' => [
                    'isIndexableRecord' => false,
                    'isOnIndexablePage' => ['expects' => self::once(), 'value' => false],
                    'checkCTypes' => ['expects' => self::never(), 'value' => true],
                    'isIndexableColumn' => ['expects' => self::never(), 'value' => false],
                ],
            ],
            __LINE__ => [
                'sourceRecord' => ['colPos' => 0],
                'expected' => [
                    'isIndexableRecord' => false,
                    'isOnIndexablePage' => ['expects' => self::once(), 'value' => false],
                    'checkCTypes' => ['expects' => self::never(), 'value' => true],
                    'isIndexableColumn' => ['expects' => self::never(), 'value' => true],
                ],
            ],
            __LINE__ => [
                'sourceRecord' => ['colPos' => 0],
                'expected' => [
                    'isIndexableRecord' => false,
                    'isOnIndexablePage' => ['expects' => self::once(), 'value' => false],
                    'checkCTypes' => ['expects' => self::never(), 'value' => false],
                    'isIndexableColumn' => ['expects' => self::never(), 'value' => true],
                ],
            ],
            __LINE__ => [
                'sourceRecord' => ['colPos' => -1],
                'expected' => [
                    'isIndexableRecord' => false,
                    'isOnIndexablePage' => ['expects' => self::once(), 'value' => false],
                    'checkCTypes' => ['expects' => self::never(), 'value' => false],
                    'isIndexableColumn' => ['expects' => self::never(), 'value' => false],
                ],
            ],
            __LINE__ => [
                'sourceRecord' => ['colPos' => -1, 'tx_mksearch_is_indexable' => tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION],
                'expected' => [
                    'isIndexableRecord' => false,
                    'isOnIndexablePage' => ['expects' => self::once(), 'value' => true],
                    'checkCTypes' => ['expects' => self::once(), 'value' => false],
                    'isIndexableColumn' => ['expects' => self::never(), 'value' => false],
                ],
            ],
            __LINE__ => [
                'sourceRecord' => ['colPos' => -1, 'tx_mksearch_is_indexable' => tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION],
                'expected' => [
                    'isIndexableRecord' => false,
                    'isOnIndexablePage' => ['expects' => self::once(), 'value' => true],
                    'checkCTypes' => ['expects' => self::once(), 'value' => true],
                    'isIndexableColumn' => ['expects' => self::once(), 'value' => false],
                ],
            ],
            __LINE__ => [
                'sourceRecord' => ['colPos' => 0, 'tx_mksearch_is_indexable' => tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION],
                'expected' => [
                    'isIndexableRecord' => true,
                    'isOnIndexablePage' => ['expects' => self::once(), 'value' => true],
                    'checkCTypes' => ['expects' => self::once(), 'value' => true],
                    'isIndexableColumn' => ['expects' => self::once(), 'value' => true],
                ],
            ],
            __LINE__ => [
                'sourceRecord' => ['colPos' => 1, 'tx_mksearch_is_indexable' => tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION],
                'expected' => [
                    'isIndexableRecord' => false,
                    'isOnIndexablePage' => ['expects' => self::once(), 'value' => false],
                    'checkCTypes' => ['expects' => self::never(), 'value' => true],
                    'isIndexableColumn' => ['expects' => self::never(), 'value' => false],
                ],
            ],
            __LINE__ => [
                'sourceRecord' => ['colPos' => 0, 'tx_mksearch_is_indexable' => tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION],
                'expected' => [
                    'isIndexableRecord' => false,
                    'isOnIndexablePage' => ['expects' => self::once(), 'value' => false],
                    'checkCTypes' => ['expects' => self::never(), 'value' => true],
                    'isIndexableColumn' => ['expects' => self::never(), 'value' => true],
                ],
            ],
            __LINE__ => [
                'sourceRecord' => ['colPos' => 0, 'tx_mksearch_is_indexable' => tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION],
                'expected' => [
                    'isIndexableRecord' => false,
                    'isOnIndexablePage' => ['expects' => self::once(), 'value' => false],
                    'checkCTypes' => ['expects' => self::never(), 'value' => false],
                    'isIndexableColumn' => ['expects' => self::never(), 'value' => true],
                ],
            ],
            __LINE__ => [
                'sourceRecord' => ['colPos' => -1, 'tx_mksearch_is_indexable' => tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION],
                'expected' => [
                    'isIndexableRecord' => false,
                    'isOnIndexablePage' => ['expects' => self::once(), 'value' => false],
                    'checkCTypes' => ['expects' => self::never(), 'value' => false],
                    'isIndexableColumn' => ['expects' => self::never(), 'value' => false],
                ],
            ],
        ];
    }

    /**
     * @group unit
     */
    public function testIsIndexableRecordWithoutDefinedColumnsAndColPos()
    {
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['isOnIndexablePage', 'checkCTypes', 'isIndexableColumn']
        );

        $sourceRecord = [];

        $options = self::getDefaultOptions();

        $indexer->expects($this->once())
            ->method('isOnIndexablePage')
            ->with($sourceRecord, $options)
            ->will($this->returnValue(true));

        $indexer->expects($this->once())
            ->method('checkCTypes')
            ->with($sourceRecord, $options)
            ->will($this->returnValue(true));

        $indexer->expects($this->once())
            ->method('isIndexableColumn')
            ->with($sourceRecord, $options)
            ->will($this->returnValue(true));

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

    /**
     * @group unit
     * @dataProvider getTestDataForIsIndexableRecordWithIndexableAndWithoutMethods
     */
    public function testIsIndexableRecordWithIndexableAndWithoutMethods($sourceRecord, $options, $expected)
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

    public function getTestDataForIsIndexableRecordWithIndexableAndWithoutMethods()
    {
        return [
            __LINE__ => [
                'sourceRecord' => ['tx_mksearch_is_indexable' => tx_mksearch_indexer_ttcontent_Normal::IS_INDEXABLE],
                'options' => [],
                'expected' => true,
            ],
            __LINE__ => [
                'sourceRecord' => ['tx_mksearch_is_indexable' => tx_mksearch_indexer_ttcontent_Normal::IS_NOT_INDEXABLE],
                'options' => [],
                'expected' => false,
            ],
        ];
    }

    /**
     * @group unit
     */
    public function testIsIndexableColumnWithColPosAndColumns()
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

    /**
     * @group unit
     */
    public function testIsIndexableColumnWithColPosAndWithoutColumns()
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

    /**
     * @group unit
     */
    public function testIsIndexableColumnWithoutColPosAndWithColumns()
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

    /**
     * @group unit
     */
    public function testIsIndexableColumnWithoutColPosAndColumns()
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

    /**
     * @group unit
     */
    public function testHasDocToBeDeletedIfNoPageContent()
    {
        $indexDoc = $this->getIndexDocMock('core', 'tt_content');
        $ttContentModel = $this->getModel(['pid' => 123]);

        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['getPageContent']
        );
        $sysPage = \Sys25\RnBase\Utility\TYPO3::getSysPage();
        $indexer
            ->expects(self::once())
            ->method('getPageContent')
            ->with(123)
            ->will(self::returnValue([]));

        self::assertTrue(
            $this->callInaccessibleMethod(
                [$indexer, 'hasDocToBeDeleted'],
                [$ttContentModel, $indexDoc]
            )
        );
    }

    /**
     * @group unit
     * @dataProvider dataProviderDokTypes
     */
    public function testHasDocToBeDeletedDependentOnDokType($dokType, $hasToBeDeleted = true, $options = [])
    {
        $indexDoc = $this->getIndexDocMock('core', 'tt_content');
        $ttContentModel = $this->getModel(['pid' => 123]);

        $indexer = $this->getMock(
            'tx_mksearch_indexer_ttcontent_Normal',
            ['getPageContent']
        );
        $sysPage = \Sys25\RnBase\Utility\TYPO3::getSysPage();
        $indexer
            ->expects(self::any())
            ->method('getPageContent')
            ->with(123)
            ->will(self::returnValue(['doktype' => $dokType]));

        self::assertSame(
            $hasToBeDeleted,
            $this->callInaccessibleMethod(
                [$indexer, 'hasDocToBeDeleted'],
                [$ttContentModel, $indexDoc, $options]
            )
        );
    }

    /**
     * @return array
     */
    public function dataProviderDokTypes()
    {
        self::markTestIncomplete('The requested database connection named "Default" has not been configured.');

        $sysPage = \Sys25\RnBase\Utility\TYPO3::getSysPage();

        return [
            [$sysPage::DOKTYPE_SYSFOLDER],
            [$sysPage::DOKTYPE_RECYCLER],
            [$sysPage::DOKTYPE_LINK],
            ['test'],
            [0],
            [$sysPage::DOKTYPE_DEFAULT, false],
            [$sysPage::DOKTYPE_DEFAULT, true, ['supportedDokTypes' => '123,456']],
            [123, false, ['supportedDokTypes' => '123,456']],
        ];
    }
}
