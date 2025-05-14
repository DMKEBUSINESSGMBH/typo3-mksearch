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
class tx_mksearch_tests_util_IndexerTest extends Sys25\RnBase\Testing\BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TCA']['tt_content'] = [
            'ctrl' => ['languageField' => 'sys_language_uid'],
        ];
    }

    public function testGetDateTimeWithTimestamp(): void
    {
        $dateTime = tx_mksearch_util_Indexer::getInstance()
            ->getDateTime('@2');
        self::assertEquals('1970-01-01T00:00:02Z', $dateTime);
    }

    public function testIsOnIndexablePageReturnsTrueIfNoInOrExcludesSet(): void
    {
        self::markTestIncomplete('Access to database is made.');
        $sourceRecord = ['pid' => 2];
        $options = [];

        $isOnIndexablePage = tx_mksearch_util_Indexer::getInstance()
            ->isOnIndexablePage($sourceRecord, $options);

        self::assertTrue($isOnIndexablePage, 'Seite nicht indizierbar');
    }

    public function testIsOnIndexablePageReturnsTrueIfPidIsInIncludePages(): void
    {
        $sourceRecord = ['pid' => 1];
        $options = [
            'include.' => [
                'pages' => 1,
            ],
        ];

        $isOnIndexablePage = tx_mksearch_util_Indexer::getInstance()
            ->isOnIndexablePage($sourceRecord, $options);

        self::assertTrue($isOnIndexablePage, 'Seite nicht indizierbar');
    }

    public function testIsOnIndexablePageReturnsFalseIfPidIsNotInIncludePages(): void
    {
        self::markTestIncomplete('Access to database is made.');
        $sourceRecord = ['pid' => 2];
        $options = [
            'include.' => [
                'pages' => 1,
            ],
        ];

        $isOnIndexablePage = tx_mksearch_util_Indexer::getInstance()
            ->isOnIndexablePage($sourceRecord, $options);

        self::assertFalse($isOnIndexablePage, 'Seite indizierbar');
    }

    public function testIsOnIndexablePageReturnsFalseIfPidIsInExcludePageTrees(): void
    {
        $sourceRecord = ['pid' => 3];
        $options = [
            'exclude.' => [
                'pageTrees' => 1,
            ],
        ];

        $rootline = [
            2 => ['uid' => 3, 'pid' => 2],
            1 => ['uid' => 2, 'pid' => 1],
            0 => ['uid' => 1, 'pid' => 0],
        ];
        $utilIndexer = $this->getMockClassForIsOnIndexablePageTests($rootline);

        $isOnIndexablePage = $utilIndexer->isOnIndexablePage($sourceRecord, $options);

        self::assertFalse($isOnIndexablePage, 'Seite indizierbar');
    }

    public function testIsOnIndexablePageReturnsTrueIfPidIsInIncludePageTrees(): void
    {
        $sourceRecord = ['pid' => 3];
        $options = [
            'include.' => [
                'pageTrees' => 2,
            ],
        ];

        $rootline = [
            1 => ['uid' => 3, 'pid' => 2],
            0 => ['uid' => 2, 'pid' => 0],
        ];
        $utilIndexer = $this->getMockClassForIsOnIndexablePageTests($rootline);

        $isOnIndexablePage = $utilIndexer->isOnIndexablePage($sourceRecord, $options);

        self::assertTrue($isOnIndexablePage, 'Seite nicht indizierbar');
    }

    public function testIsOnIndexablePageReturnsFalseIfPidIsNotInIncludePageTrees(): void
    {
        $sourceRecord = ['pid' => 3];
        $options = [
            'include.' => [
                'pageTrees' => 1,
            ],
        ];

        $rootline = [
            1 => ['uid' => 3, 'pid' => 2],
            0 => ['uid' => 2, 'pid' => 0],
        ];
        $utilIndexer = $this->getMockClassForIsOnIndexablePageTests($rootline);

        $isOnIndexablePage = $utilIndexer->isOnIndexablePage($sourceRecord, $options);

        self::assertFalse($isOnIndexablePage, 'Seite indizierbar');
    }

    public function testIsOnIndexablePageReturnsFalseIfPidIsInExcludePages(): void
    {
        self::markTestIncomplete('Access to database is made.');
        $sourceRecord = ['pid' => 1];
        $options = [
            'exclude.' => [
                'pages' => 1,
            ],
        ];

        $isOnIndexablePage = tx_mksearch_util_Indexer::getInstance()
            ->isOnIndexablePage($sourceRecord, $options);

        self::assertFalse($isOnIndexablePage, 'Seite indizierbar');
    }

    public function testIsOnIndexablePageReturnsTrueIfPidIsNotInExcludePages(): void
    {
        self::markTestIncomplete('Access to database is made.');
        $sourceRecord = ['pid' => 2];
        $options = [
            'exclude.' => [
                'pages' => 1,
            ],
        ];

        $isOnIndexablePage = tx_mksearch_util_Indexer::getInstance()
            ->isOnIndexablePage($sourceRecord, $options);

        self::assertTrue($isOnIndexablePage, 'Seite nicht indizierbar');
    }

    public function testIsOnIndexablePageReturnsFalseIfPidIsIncludePageTreesButAlsoInExcludePages(): void
    {
        $sourceRecord = ['pid' => 3];
        $options = [
            'include.' => [
                'pageTrees' => 1,
            ],
            'exclude.' => [
                'pages' => 3,
            ],
        ];

        $rootline = [
            2 => ['uid' => 3, 'pid' => 2],
            1 => ['uid' => 2, 'pid' => 1],
            0 => ['uid' => 1, 'pid' => 0],
        ];
        $utilIndexer = $this->getMockClassForIsOnIndexablePageTests($rootline);

        $isOnIndexablePage = $utilIndexer->isOnIndexablePage($sourceRecord, $options);

        self::assertFalse($isOnIndexablePage, 'Seite indizierbar');
    }

    public function testIsOnIndexablePageReturnsTrueIfPidIsIncludePageTreesAndNotExcludePages(): void
    {
        $sourceRecord = ['pid' => 3];
        $options = [
            'include.' => [
                'pageTrees' => 1,
            ],
            'exclude.' => [
                'pages' => 2,
            ],
        ];

        $rootline = [
            2 => ['uid' => 3, 'pid' => 2],
            1 => ['uid' => 2, 'pid' => 1],
            0 => ['uid' => 1, 'pid' => 0],
        ];
        $utilIndexer = $this->getMockClassForIsOnIndexablePageTests($rootline);

        $isOnIndexablePage = $utilIndexer->isOnIndexablePage($sourceRecord, $options);

        self::assertTrue($isOnIndexablePage, 'Seite indizierbar');
    }

    public function testIsOnIndexablePageReturnsFalseIfPidIsIncludePageTreesButAnExcludePageTreesIndexIsCloserToThePid(): void
    {
        $sourceRecord = ['pid' => 3];
        $options = [
            'include.' => [
                'pageTrees' => 1,
            ],
            'exclude.' => [
                'pageTrees' => 2,
            ],
        ];

        $rootline = [
            2 => ['uid' => 3, 'pid' => 2],
            1 => ['uid' => 2, 'pid' => 1],
            0 => ['uid' => 1, 'pid' => 0],
        ];
        $utilIndexer = $this->getMockClassForIsOnIndexablePageTests($rootline);

        $isOnIndexablePage = $utilIndexer->isOnIndexablePage($sourceRecord, $options);

        self::assertFalse($isOnIndexablePage, 'Seite indizierbar');
    }

    public function testIsOnIndexablePageReturnsTrueIfPidIsMinus1ButIsExplicitlyIncluded(): void
    {
        $sourceRecord = ['pid' => -1];
        $options = [
            'include.' => [
                'pages' => -1,
            ],
        ];

        $isOnIndexablePage = tx_mksearch_util_Indexer::getInstance()
            ->isOnIndexablePage($sourceRecord, $options);

        self::assertTrue($isOnIndexablePage, 'Seite nicht indizierbar');
    }

    public function testIsOnIndexablePageReturnsFalseIfPidIsMinus1AndNotExplicitlyIncluded(): void
    {
        $sourceRecord = ['pid' => -1];
        $options = [];

        $isOnIndexablePage = tx_mksearch_util_Indexer::getInstance()
            ->isOnIndexablePage($sourceRecord, $options);

        self::assertFalse($isOnIndexablePage, 'Seite nicht indizierbar');
    }

    public function testIndexModelByMapping(): void
    {
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $model = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Domain\Model\BaseModel::class,
            ['recordField' => 123]
        );

        tx_mksearch_util_Indexer::getInstance()->indexModelByMapping(
            $model,
            ['recordField' => 'documentField'],
            $indexDoc
        );
        $docData = $indexDoc->getData();

        self::assertEquals(
            123,
            $docData['documentField']->getValue(),
            'model falsch indiziert'
        );
    }

    public function testIndexModelByMappingDoesNotIndexHiddenModelsByDefault(): void
    {
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $model = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Domain\Model\BaseModel::class,
            ['recordField' => 123, 'hidden' => 1]
        );

        tx_mksearch_util_Indexer::getInstance()->indexModelByMapping(
            $model,
            ['recordField' => 'documentField'],
            $indexDoc
        );
        $docData = $indexDoc->getData();

        self::assertFalse(
            isset($docData['documentField']),
            'model doch indiziert'
        );
    }

    public function testIndexModelByMappingIndexesHiddenModelsIfSet(): void
    {
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $model = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Domain\Model\BaseModel::class,
            ['recordField' => 123, 'hidden' => 1]
        );

        tx_mksearch_util_Indexer::getInstance()->indexModelByMapping(
            $model,
            ['recordField' => 'documentField'],
            $indexDoc,
            '',
            [],
            false
        );
        $docData = $indexDoc->getData();

        self::assertEquals(
            123,
            $docData['documentField']->getValue(),
            'model falsch indiziert'
        );
    }

    public function testIndexModelByMappingWithPrefix(): void
    {
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $model = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Domain\Model\BaseModel::class,
            ['recordField' => 123]
        );

        tx_mksearch_util_Indexer::getInstance()->indexModelByMapping(
            $model,
            ['recordField' => 'documentField'],
            $indexDoc,
            'test_'
        );
        $docData = $indexDoc->getData();

        self::assertEquals(
            123,
            $docData['test_documentField']->getValue(),
            'model falsch indiziert'
        );
    }

    public function testIndexModelByMappingMapsNotEmptyFields(): void
    {
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $model = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Domain\Model\BaseModel::class,
            ['recordField' => '']
        );

        tx_mksearch_util_Indexer::getInstance()->indexModelByMapping(
            $model,
            ['recordField' => 'documentField'],
            $indexDoc
        );
        $docData = $indexDoc->getData();

        self::assertFalse(
            isset($docData['documentField']),
            'model doch indiziert'
        );
    }

    public function testIndexModelByMappingMapsEmptyFieldsIfKeepEmptyOption(): void
    {
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $model = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Domain\Model\BaseModel::class,
            ['recordField' => '']
        );

        tx_mksearch_util_Indexer::getInstance()->indexModelByMapping(
            $model,
            ['recordField' => 'documentField'],
            $indexDoc,
            '',
            ['keepEmpty' => 1]
        );
        $docData = $indexDoc->getData();

        self::assertEquals(
            '',
            $docData['documentField']->getValue(),
            'model falsch indiziert'
        );
    }

    public function testIndexArrayOfModelsByMapping(): void
    {
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $models = [
            TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                Sys25\RnBase\Domain\Model\BaseModel::class,
                ['recordField' => 123]
            ),
            TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                Sys25\RnBase\Domain\Model\BaseModel::class,
                ['recordField' => 456]
            ),
        ];

        tx_mksearch_util_Indexer::getInstance()->indexArrayOfModelsByMapping(
            $models,
            ['recordField' => 'documentField'],
            $indexDoc
        );
        $docData = $indexDoc->getData();

        self::assertEquals(
            [123, 456],
            $docData['documentField']->getValue(),
            'models falsch indiziert'
        );
    }

    public function testIndexArrayOfModelsByMappingWithFieldConversion(): void
    {
        self::markTestIncomplete('Access to database is made.');
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $models = [
            TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                Sys25\RnBase\Domain\Model\BaseModel::class,
                ['recordField' => 1440668593]
            ),
            TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                Sys25\RnBase\Domain\Model\BaseModel::class,
                ['recordField' => 1439034300]
            ),
        ];
        $options = [
            'fieldsConversion.' => ['documentField.' => [
                'unix2isodate' => 1,
            ]],
        ];

        tx_mksearch_util_Indexer::getInstance()->indexArrayOfModelsByMapping(
            $models,
            ['recordField' => 'documentField'],
            $indexDoc,
            '',
            $options
        );
        $docData = $indexDoc->getData();

        self::assertEquals(
            ['2015-08-27T09:43:13Z', '2015-08-08T11:45:00Z'],
            $docData['documentField']->getValue(),
            'models falsch indiziert'
        );
    }

    public function testIndexArrayOfModelsByMappingWithMoreFields(): void
    {
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $models = [
            TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                Sys25\RnBase\Domain\Model\BaseModel::class,
                ['recordField' => 123]
            ),
            TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                Sys25\RnBase\Domain\Model\BaseModel::class,
                ['recordField' => 456]
            ),
        ];

        tx_mksearch_util_Indexer::getInstance()->indexArrayOfModelsByMapping(
            $models,
            ['recordField' => 'documentField,documentField2'],
            $indexDoc
        );
        $docData = $indexDoc->getData();

        self::assertEquals(
            [123, 456],
            $docData['documentField']->getValue(),
            'models falsch indiziert'
        );
        self::assertEquals(
            [123, 456],
            $docData['documentField2']->getValue(),
            'second field is wrong'
        );
    }

    public function testIndexArrayOfModelsByMappingDoesNotIndexHiddenModelsByDefault(): void
    {
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $models = [
            TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                Sys25\RnBase\Domain\Model\BaseModel::class,
                ['recordField' => 123]
            ),
            TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                Sys25\RnBase\Domain\Model\BaseModel::class,
                ['recordField' => 456, 'hidden' => 1]
            ),
        ];

        tx_mksearch_util_Indexer::getInstance()->indexArrayOfModelsByMapping(
            $models,
            ['recordField' => 'documentField'],
            $indexDoc
        );
        $docData = $indexDoc->getData();

        self::assertEquals(
            [123],
            $docData['documentField']->getValue(),
            'models falsch indiziert'
        );
    }

    public function testIndexArrayOfModelsByMappingIndexesHiddenModelsIfSet(): void
    {
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $models = [
            TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                Sys25\RnBase\Domain\Model\BaseModel::class,
                ['recordField' => 123]
            ),
            TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                Sys25\RnBase\Domain\Model\BaseModel::class,
                ['recordField' => 456, 'hidden' => 1]
            ),
        ];

        tx_mksearch_util_Indexer::getInstance()->indexArrayOfModelsByMapping(
            $models,
            ['recordField' => 'documentField'],
            $indexDoc,
            '',
            [],
            false
        );
        $docData = $indexDoc->getData();

        self::assertEquals(
            [123, 456],
            $docData['documentField']->getValue(),
            'models falsch indiziert'
        );
    }

    public function testIndexArrayOfModelsByMappingWithPrefix(): void
    {
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $models = [
            TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                Sys25\RnBase\Domain\Model\BaseModel::class,
                ['recordField' => 123]
            ),
            TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                Sys25\RnBase\Domain\Model\BaseModel::class,
                ['recordField' => 456]
            ),
        ];

        tx_mksearch_util_Indexer::getInstance()->indexArrayOfModelsByMapping(
            $models,
            ['recordField' => 'documentField'],
            $indexDoc,
            'test_'
        );
        $docData = $indexDoc->getData();

        self::assertEquals(
            [123, 456],
            $docData['test_documentField']->getValue(),
            'model falsch indiziert'
        );
    }

    public function testIndexArrayOfModelsByMappingMapsNotEmptyFields(): void
    {
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $models = [
            TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                Sys25\RnBase\Domain\Model\BaseModel::class,
                ['recordField' => '']
            ),
            TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                Sys25\RnBase\Domain\Model\BaseModel::class,
                ['recordField' => '']
            ),
        ];

        tx_mksearch_util_Indexer::getInstance()->indexArrayOfModelsByMapping(
            $models,
            ['recordField' => 'documentField'],
            $indexDoc
        );
        $docData = $indexDoc->getData();

        self::assertFalse(
            isset($docData['documentField']),
            'model doch indiziert'
        );
    }

    /**
     * @return tx_mksearch_util_Indexer
     */
    protected function getMockClassForIsOnIndexablePageTests(array $rootline)
    {
        $utilIndexer = $this->getMock('tx_mksearch_util_Indexer', ['getRootlineByPid']);

        $utilIndexer->expects($this->any())
            ->method('getRootlineByPid')
            ->with(3)
            ->willReturn($rootline);

        return $utilIndexer;
    }

    public function testStopIndexingReturnsFalseIfNoSysLanguageUidField(): void
    {
        /* @var $utility tx_mksearch_util_Indexer */
        $utility = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_util_Indexer');
        $tableName = 'tx_mksearch_queue';
        $sourceRecord = ['some_record'];
        $options = ['some_options'];
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );

        self::assertFalse(
            $utility->stopIndexing($tableName, $sourceRecord, $indexDoc, $options)
        );
    }

    public function testStopIndexingReturnsFalseIfSysLanguageUidFieldMatchesLanguageFromOptions(): void
    {
        /* @var $utility tx_mksearch_util_Indexer */
        $utility = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_util_Indexer');
        $tableName = 'tt_content';
        $sourceRecord = ['sys_language_uid' => 123];
        $options = ['lang' => 123];
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );

        self::assertFalse(
            $utility->stopIndexing($tableName, $sourceRecord, $indexDoc, $options)
        );
    }

    public function testStopIndexingReturnsFalseIfSysLanguageUidFieldMatchesLanguageFromOptionsWithMultipleValues(): void
    {
        /* @var $utility tx_mksearch_util_Indexer */
        $utility = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_util_Indexer');
        $tableName = 'tt_content';
        $sourceRecord = ['sys_language_uid' => 222];
        $options = ['lang' => '0,222,666'];
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );

        self::assertFalse(
            $utility->stopIndexing($tableName, $sourceRecord, $indexDoc, $options)
        );
    }

    public function testStopIndexingReturnsTrueIfSysLanguageUidFieldMatchesNotLanguageFromOptions(): void
    {
        /* @var $utility tx_mksearch_util_Indexer */
        $utility = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_util_Indexer');
        $tableName = 'tt_content';
        $sourceRecord = ['sys_language_uid' => 123];
        $options = ['lang' => 456];
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );

        self::assertTrue(
            $utility->stopIndexing($tableName, $sourceRecord, $indexDoc, $options)
        );
    }

    public function testStopIndexingReturnsTrueIfSysLanguageUidFieldMatchesNotLanguageFromOptionsWithMultipleValues(): void
    {
        /* @var $utility tx_mksearch_util_Indexer */
        $utility = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_util_Indexer');
        $tableName = 'tt_content';
        $sourceRecord = ['sys_language_uid' => 123];
        $options = ['lang' => '0,456,789'];
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );

        self::assertTrue(
            $utility->stopIndexing($tableName, $sourceRecord, $indexDoc, $options)
        );
    }

    public function testStopIndexingReturnsFalseIfSysLanguageUidFieldIsSetToAllLanguages(): void
    {
        /* @var $utility tx_mksearch_util_Indexer */
        $utility = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_util_Indexer');
        $tableName = 'tt_content';
        $sourceRecord = ['sys_language_uid' => '-1'];
        $options = ['lang' => '456'];
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );

        self::assertFalse(
            $utility->stopIndexing($tableName, $sourceRecord, $indexDoc, $options)
        );
    }

    public function testAddModelsToIndex(): void
    {
        $models = [
            TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Sys25\RnBase\Domain\Model\BaseModel::class, ['uid' => 1]),
            TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Sys25\RnBase\Domain\Model\BaseModel::class, ['uid' => 2]),
        ];
        $tableName = 'test_table';
        $prefer = 'prefer_me';
        $resolver = 'test_resolver';
        $data = ['test data'];
        $options = ['test options'];

        $utility = $this->getMock('tx_mksearch_util_Indexer', ['addModelToIndex']);

        $matcher = self::exactly(2);
        $utility
            ->expects($matcher)
            ->method('addModelToIndex')
            ->with(
                $this->callback(function (Sys25\RnBase\Domain\Model\BaseModel $model) use ($matcher, $models): bool {
                    self::assertSame(
                        match ($matcher->numberOfInvocations()) {
                            1 => $models[0],
                            2 => $models[1],
                        },
                        $model
                    );

                    return true;
                }),
                $tableName,
                $prefer,
                $resolver,
                $data,
                $options
            );

        $utility->addModelsToIndex($models, $tableName, $prefer, $resolver, $data, $options);
    }

    public function testAddModelToIndexWithInvalidModel(): void
    {
        $utility = $this->getMock('tx_mksearch_util_Indexer', ['getInternalIndexService']);
        $utility->expects(self::never())
            ->method('getInternalIndexService');

        $utility->addModelToIndex(TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Sys25\RnBase\Domain\Model\BaseModel::class, []), 'test_table');
    }

    public function testAddModelToIndexWithValidModel(): void
    {
        $model = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Domain\Model\BaseModel::class, ['uid' => 123, 'dummy' => 'so model is valid']
        );
        $tableName = 'test_table';
        $prefer = 'prefer_me';
        $resolver = 'test_resolver';
        $data = ['test data'];
        $options = ['test options'];

        $internalIndexService = $this->getMock('tx_mksearch_service_internal_Index', ['addRecordToIndex']);
        $internalIndexService->expects(self::once())
            ->method('addRecordToIndex')
            ->with($tableName, 123, $prefer, $resolver, $data, $options);

        $utility = $this->getMock('tx_mksearch_util_Indexer', ['getInternalIndexService']);
        $utility->expects(self::once())
            ->method('getInternalIndexService')
            ->willReturn($internalIndexService);

        $utility->addModelToIndex($model, $tableName, $prefer, $resolver, $data, $options);
    }
}
