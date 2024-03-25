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
class tx_mksearch_tests_util_IndexerTest extends \Sys25\RnBase\Testing\BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TCA']['tt_content'] = [
            'ctrl' => ['languageField' => 'sys_language_uid'],
        ];
    }

    /**
     * @group unit
     */
    public function testGetDateTimeWithTimestamp()
    {
        $dateTime = tx_mksearch_util_Indexer::getInstance()
            ->getDateTime('@2');
        self::assertEquals('1970-01-01T00:00:02Z', $dateTime);
    }

    /**
     * @group unit
     */
    public function testIsOnIndexablePageReturnsTrueIfNoInOrExcludesSet()
    {
        self::markTestIncomplete('Access to database is made.');
        $sourceRecord = ['pid' => 2];
        $options = [];

        $isOnIndexablePage = tx_mksearch_util_Indexer::getInstance()
            ->isOnIndexablePage($sourceRecord, $options);

        self::assertTrue($isOnIndexablePage, 'Seite nicht indizierbar');
    }

    /**
     * @group unit
     */
    public function testIsOnIndexablePageReturnsTrueIfPidIsInIncludePages()
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

    /**
     * @group unit
     */
    public function testIsOnIndexablePageReturnsFalseIfPidIsNotInIncludePages()
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

    /**
     * @group unit
     */
    public function testIsOnIndexablePageReturnsFalseIfPidIsInExcludePageTrees()
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

    /**
     * @group unit
     */
    public function testIsOnIndexablePageReturnsTrueIfPidIsInIncludePageTrees()
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

    /**
     * @group unit
     */
    public function testIsOnIndexablePageReturnsFalseIfPidIsNotInIncludePageTrees()
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

    /**
     * @group unit
     */
    public function testIsOnIndexablePageReturnsFalseIfPidIsInExcludePages()
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

    /**
     * @group unit
     */
    public function testIsOnIndexablePageReturnsTrueIfPidIsNotInExcludePages()
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

    /**
     * @group unit
     */
    public function testIsOnIndexablePageReturnsFalseIfPidIsIncludePageTreesButAlsoInExcludePages()
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

    /**
     * @group unit
     */
    public function testIsOnIndexablePageReturnsTrueIfPidIsIncludePageTreesAndNotExcludePages()
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

    /**
     * @group unit
     */
    public function testIsOnIndexablePageReturnsFalseIfPidIsIncludePageTreesButAnExcludePageTreesIndexIsCloserToThePid()
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

    /**
     * @group unit
     */
    public function testIsOnIndexablePageReturnsTrueIfPidIsMinus1ButIsExplicitlyIncluded()
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

    /**
     * @group unit
     */
    public function testIsOnIndexablePageReturnsFalseIfPidIsMinus1AndNotExplicitlyIncluded()
    {
        $sourceRecord = ['pid' => -1];
        $options = [];

        $isOnIndexablePage = tx_mksearch_util_Indexer::getInstance()
            ->isOnIndexablePage($sourceRecord, $options);

        self::assertFalse($isOnIndexablePage, 'Seite nicht indizierbar');
    }

    public function testIndexModelByMapping()
    {
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \Sys25\RnBase\Domain\Model\BaseModel::class,
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

    public function testIndexModelByMappingDoesNotIndexHiddenModelsByDefault()
    {
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \Sys25\RnBase\Domain\Model\BaseModel::class,
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

    public function testIndexModelByMappingIndexesHiddenModelsIfSet()
    {
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \Sys25\RnBase\Domain\Model\BaseModel::class,
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

    public function testIndexModelByMappingWithPrefix()
    {
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \Sys25\RnBase\Domain\Model\BaseModel::class,
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

    public function testIndexModelByMappingMapsNotEmptyFields()
    {
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \Sys25\RnBase\Domain\Model\BaseModel::class,
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

    public function testIndexModelByMappingMapsEmptyFieldsIfKeepEmptyOption()
    {
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \Sys25\RnBase\Domain\Model\BaseModel::class,
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

    public function testIndexArrayOfModelsByMapping()
    {
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $models = [
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \Sys25\RnBase\Domain\Model\BaseModel::class,
                ['recordField' => 123]
            ),
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \Sys25\RnBase\Domain\Model\BaseModel::class,
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

    public function testIndexArrayOfModelsByMappingWithFieldConversion()
    {
        self::markTestIncomplete('Access to database is made.');
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $models = [
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \Sys25\RnBase\Domain\Model\BaseModel::class,
                ['recordField' => 1440668593]
            ),
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \Sys25\RnBase\Domain\Model\BaseModel::class,
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

    public function testIndexArrayOfModelsByMappingWithMoreFields()
    {
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $models = [
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \Sys25\RnBase\Domain\Model\BaseModel::class,
                ['recordField' => 123]
            ),
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \Sys25\RnBase\Domain\Model\BaseModel::class,
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

    public function testIndexArrayOfModelsByMappingDoesNotIndexHiddenModelsByDefault()
    {
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $models = [
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \Sys25\RnBase\Domain\Model\BaseModel::class,
                ['recordField' => 123]
            ),
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \Sys25\RnBase\Domain\Model\BaseModel::class,
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

    public function testIndexArrayOfModelsByMappingIndexesHiddenModelsIfSet()
    {
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $models = [
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \Sys25\RnBase\Domain\Model\BaseModel::class,
                ['recordField' => 123]
            ),
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \Sys25\RnBase\Domain\Model\BaseModel::class,
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

    public function testIndexArrayOfModelsByMappingWithPrefix()
    {
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $models = [
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \Sys25\RnBase\Domain\Model\BaseModel::class,
                ['recordField' => 123]
            ),
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \Sys25\RnBase\Domain\Model\BaseModel::class,
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

    public function testIndexArrayOfModelsByMappingMapsNotEmptyFields()
    {
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $models = [
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \Sys25\RnBase\Domain\Model\BaseModel::class,
                ['recordField' => '']
            ),
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \Sys25\RnBase\Domain\Model\BaseModel::class,
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
     * @param array $rootline
     *
     * @return tx_mksearch_util_Indexer
     */
    protected function getMockClassForIsOnIndexablePageTests(array $rootline)
    {
        $utilIndexer = $this->getMock('tx_mksearch_util_Indexer', ['getRootlineByPid']);

        $utilIndexer->expects($this->any())
            ->method('getRootlineByPid')
            ->with(3)
            ->will($this->returnValue($rootline));

        return $utilIndexer;
    }

    /**
     * @group unit
     */
    public function testStopIndexingReturnsFalseIfNoSysLanguageUidField()
    {
        /* @var $utility tx_mksearch_util_Indexer */
        $utility = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_util_Indexer');
        $tableName = 'tx_mksearch_queue';
        $sourceRecord = ['some_record'];
        $options = ['some_options'];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );

        self::assertFalse(
            $utility->stopIndexing($tableName, $sourceRecord, $indexDoc, $options)
        );
    }

    /**
     * @group unit
     */
    public function testStopIndexingReturnsFalseIfSysLanguageUidFieldMatchesLanguageFromOptions()
    {
        /* @var $utility tx_mksearch_util_Indexer */
        $utility = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_util_Indexer');
        $tableName = 'tt_content';
        $sourceRecord = ['sys_language_uid' => 123];
        $options = ['lang' => 123];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );

        self::assertFalse(
            $utility->stopIndexing($tableName, $sourceRecord, $indexDoc, $options)
        );
    }

    /**
     * @group unit
     */
    public function testStopIndexingReturnsFalseIfSysLanguageUidFieldMatchesLanguageFromOptionsWithMultipleValues()
    {
        /* @var $utility tx_mksearch_util_Indexer */
        $utility = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_util_Indexer');
        $tableName = 'tt_content';
        $sourceRecord = ['sys_language_uid' => 222];
        $options = ['lang' => '0,222,666'];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );

        self::assertFalse(
            $utility->stopIndexing($tableName, $sourceRecord, $indexDoc, $options)
        );
    }

    /**
     * @group unit
     */
    public function testStopIndexingReturnsTrueIfSysLanguageUidFieldMatchesNotLanguageFromOptions()
    {
        /* @var $utility tx_mksearch_util_Indexer */
        $utility = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_util_Indexer');
        $tableName = 'tt_content';
        $sourceRecord = ['sys_language_uid' => 123];
        $options = ['lang' => 456];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );

        self::assertTrue(
            $utility->stopIndexing($tableName, $sourceRecord, $indexDoc, $options)
        );
    }

    /**
     * @group unit
     */
    public function testStopIndexingReturnsTrueIfSysLanguageUidFieldMatchesNotLanguageFromOptionsWithMultipleValues()
    {
        /* @var $utility tx_mksearch_util_Indexer */
        $utility = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_util_Indexer');
        $tableName = 'tt_content';
        $sourceRecord = ['sys_language_uid' => 123];
        $options = ['lang' => '0,456,789'];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );

        self::assertTrue(
            $utility->stopIndexing($tableName, $sourceRecord, $indexDoc, $options)
        );
    }

    /**
     * @group unit
     */
    public function testStopIndexingReturnsFalseIfSysLanguageUidFieldIsSetToAllLanguages()
    {
        /* @var $utility tx_mksearch_util_Indexer */
        $utility = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_util_Indexer');
        $tableName = 'tt_content';
        $sourceRecord = ['sys_language_uid' => '-1'];
        $options = ['lang' => '456'];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );

        self::assertFalse(
            $utility->stopIndexing($tableName, $sourceRecord, $indexDoc, $options)
        );
    }

    /**
     * @group unit
     */
    public function testAddModelsToIndex()
    {
        $models = [
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Domain\Model\BaseModel::class, ['uid' => 1]),
            \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Domain\Model\BaseModel::class, ['uid' => 2]),
        ];
        $tableName = 'test_table';
        $prefer = 'prefer_me';
        $resolver = 'test_resolver';
        $data = ['test data'];
        $options = ['test options'];

        $utility = $this->getMock('tx_mksearch_util_Indexer', ['addModelToIndex']);

        $utility->expects(self::exactly(2))
            ->method('addModelToIndex')
            ->withConsecutive(
                [$models[0], $tableName, $prefer, $resolver, $data, $options],
                [$models[1], $tableName, $prefer, $resolver, $data, $options],
            );

        $utility->addModelsToIndex($models, $tableName, $prefer, $resolver, $data, $options);
    }

    /**
     * @group unit
     */
    public function testAddModelToIndexWithInvalidModel()
    {
        $utility = $this->getMock('tx_mksearch_util_Indexer', ['getInternalIndexService']);
        $utility->expects(self::never())
            ->method('getInternalIndexService');

        $utility->addModelToIndex(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Domain\Model\BaseModel::class, []), 'test_table');
    }

    /**
     * @group unit
     */
    public function testAddModelToIndexWithValidModel()
    {
        $model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \Sys25\RnBase\Domain\Model\BaseModel::class, ['uid' => 123, 'dummy' => 'so model is valid']
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
            ->will(self::returnValue($internalIndexService));

        $utility->addModelToIndex($model, $tableName, $prefer, $resolver, $data, $options);
    }
}
