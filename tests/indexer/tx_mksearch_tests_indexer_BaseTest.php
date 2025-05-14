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
 * Wir müssen in diesem Fall mit der DB testen da wir die pages
 * Tabelle benötigen.
 *
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_indexer_BaseTest extends tx_mksearch_tests_Testcase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->destroyFrontend();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->destroyFrontend();
    }

    protected function destroyFrontend()
    {
        if (isset($GLOBALS['TSFE'])) {
            unset($GLOBALS['TSFE']);
        }
    }

    /**
     * Check if the uid is set correct.
     */
    public function testGetPrimarKey(): void
    {
        self::markTestSkipped('Test needs refactoring.');

        /* @var $indexer tx_mksearch_tests_fixtures_indexer_Dummy */
        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        [$extKey, $cType] = $indexer->getContentType();
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $options = [];

        $aRawData = ['uid' => 1, 'test_field_1' => 'test value 1'];

        $oIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);

        self::assertInstanceOf(\TX_MKSEARCH_INTERFACE_INDEXERDOCUMENT, $oIndexDoc);

        $aPrimaryKey = $oIndexDoc->getPrimaryKey();
        self::assertEquals('mksearch', $aPrimaryKey['extKey']->getValue(), 'Es wurde nicht der richtige extKey gesetzt!');
        self::assertEquals('dummy', $aPrimaryKey['contentType']->getValue(), 'Es wurde nicht der richtige contentType gesetzt!');
        self::assertEquals(1, $aPrimaryKey['uid']->getValue(), 'Es wurde nicht die richtige Uid gesetzt!');
    }

    public function testCheckOptionsIncludeDeletesDocs(): void
    {
        self::markTestSkipped('Test needs refactoring.');

        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        [$extKey, $cType] = $indexer->getContentType();
        $options = [
            'include.' => ['categories.' => [3]],
        ];
        $options2 = [
            'include.' => ['categories' => 3],
        ];

        $aRawData = ['uid' => 1, 'pid' => 1];
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNull($aIndexDoc, 'Das Element wurde indiziert! Option 1');

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options2);
        self::assertNull($aIndexDoc, 'Das Element wurde indiziert! Option 2');
    }

    public function testCheckOptionsIncludeDoesNotDeleteDocs(): void
    {
        self::markTestSkipped('Test needs refactoring.');

        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        [$extKey, $cType] = $indexer->getContentType();
        $options = [
            'include.' => ['categories.' => [2]],
        ];
        $options2 = [
            'include.' => ['categories' => 2],
        ];
        $aRawData = ['uid' => 1, 'pid' => 2];
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNotNull($aIndexDoc, 'Das Element wurde nicht indiziert! Option 1');

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options2);
        self::assertNotNull($aIndexDoc, 'Das Element wurde nicht indiziert! Option 2');
    }

    public function testCheckOptionsExcludeDoesNotDeleteDocs(): void
    {
        self::markTestSkipped('Test needs refactoring.');

        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        [$extKey, $cType] = $indexer->getContentType();
        $options = [
            'exclude.' => ['categories.' => [3]],
        ];
        $options2 = [
            'exclude.' => ['categories' => 3],
        ];

        $aRawData = ['uid' => 1, 'pid' => 1];
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNotNull($aIndexDoc, 'Das Element wurde nicht indiziert! Element 1 Option 1');

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options2);
        self::assertNotNull($aIndexDoc, 'Das Element wurde nicht indiziert! Element 1 Option 2');
    }

    public function testCheckOptionsExcludeDeletesDocs(): void
    {
        self::markTestSkipped('Test needs refactoring.');

        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        [$extKey, $cType] = $indexer->getContentType();
        $options = [
            'exclude.' => ['categories.' => [2]],
        ];
        $options2 = [
            'exclude.' => ['categories' => 2],
        ];
        $aRawData = ['uid' => 1, 'pid' => 2];
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNull($aIndexDoc, 'Das Element wurde doch indiziert! Element 2 Option 1');

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options2);
        self::assertNull($aIndexDoc, 'Das Element wurde doch indiziert! Element 2 Option 2');
    }

    public function testCheckOptionsIncludeReturnsCorrectDefaultValueWithEmptyCategory(): void
    {
        self::markTestSkipped('Test needs refactoring.');

        $indexer = $this->getMock('tx_mksearch_tests_fixtures_indexer_Dummy', ['getTestCategories']);

        $indexer->expects($this->once())
            ->method('getTestCategories')
            ->willReturn([]);

        [$extKey, $cType] = $indexer->getContentType();
        $options = [
            'include.' => ['categories.' => [3]],
        ];

        $aRawData = ['uid' => 1, 'pid' => 1];
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNull($aIndexDoc, 'Das Element wurde indiziert! Option 1');
    }

    public function testCheckOptionsExcludeReturnsCorrectDefaultValueWithEmptyCategory(): void
    {
        self::markTestSkipped('Test needs refactoring.');

        $indexer = $this->getMock('tx_mksearch_tests_fixtures_indexer_Dummy', ['getTestCategories']);

        $indexer->expects($this->once())
            ->method('getTestCategories')
            ->willReturn([]);

        [$extKey, $cType] = $indexer->getContentType();
        $options = [
            'exclude.' => ['categories.' => [3]],
        ];

        $aRawData = ['uid' => 1, 'pid' => 1];
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNotNull($aIndexDoc, 'Das Element wurde nicht indiziert! Option 1');
    }

    public function testIndexEnableColumns(): void
    {
        self::markTestSkipped('Test needs refactoring.');

        $this->setTcaEnableColumnsForMyTestTable1();

        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        [$extKey, $cType] = $indexer->getContentType();

        $aRawData = ['uid' => 1, 'hidden' => 0, 'startdate' => 2, 'enddate' => 3, 'fe_groups' => 4];
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $indexDocData = $indexer->prepareSearchData('mytesttable_1', $aRawData, $indexDoc, [])->getData();

        // empty values are ignored
        self::assertEquals('1970-01-01T00:00:02Z', $indexDocData['starttime_dt']->getValue());
        self::assertEquals('1970-01-01T00:00:03Z', $indexDocData['endtime_dt']->getValue());
        self::assertEquals([0 => 4], $indexDocData['fe_group_mi']->getValue());
    }

    public function testIndexEnableColumnsIfTableHasNoEnableColumns(): void
    {
        self::markTestSkipped('Test needs refactoring.');

        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        [$extKey, $cType] = $indexer->getContentType();

        $aRawData = ['uid' => 1];
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $indexDocData = $indexer->prepareSearchData('doesn_t_matter', $aRawData, $indexDoc, [])->getData();

        self::assertEquals(
            2,
            count($indexDocData),
            'es sollte nur 1 Feld (content_ident_s) indiziert werden.'
        );
        self::assertArrayHasKey(
            'content_ident_s',
            $indexDocData,
            'content_ident_s nicht enthalten.'
        );
        self::assertArrayHasKey(
            'group_s',
            $indexDocData,
            'group_s nicht enthalten.'
        );
    }

    public function testIndexEnableColumnsWithEmptyStarttime(): void
    {
        self::markTestSkipped('Test needs refactoring.');

        $this->setTcaEnableColumnsForMyTestTable1();

        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        [$extKey, $cType] = $indexer->getContentType();

        $aRawData = ['uid' => 1, 'hidden' => 0, 'startdate' => 0, 'enddate' => 3, 'fe_groups' => 4];
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $indexDocData = $indexer->prepareSearchData('mytesttable_1', $aRawData, $indexDoc, [])->getData();

        // empty values are ignored
        self::assertFalse(isset($indexDocData['starttime_dt']));
        self::assertEquals('1970-01-01T00:00:03Z', $indexDocData['endtime_dt']->getValue());
        self::assertEquals([0 => 4], $indexDocData['fe_group_mi']->getValue());
    }

    public function testIndexEnableColumnsWithSeveralFeGroups(): void
    {
        self::markTestSkipped('Test needs refactoring.');

        $this->setTcaEnableColumnsForMyTestTable1();

        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        [$extKey, $cType] = $indexer->getContentType();

        $aRawData = ['uid' => 1, 'hidden' => 0, 'startdate' => 0, 'enddate' => 3, 'fe_groups' => '4,5'];
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $indexDocData = $indexer->prepareSearchData('mytesttable_1', $aRawData, $indexDoc, [])->getData();

        // empty values are ignored
        self::assertFalse(isset($indexDocData['starttime_dt']));
        self::assertEquals('1970-01-01T00:00:03Z', $indexDocData['endtime_dt']->getValue());
        self::assertEquals([0 => 4, 1 => 5], $indexDocData['fe_group_mi']->getValue());
    }

    public function testIndexEnableColumnsDoesNotChangeRecordInPassedModel(): void
    {
        self::markTestSkipped('Test needs refactoring.');

        $this->setTcaEnableColumnsForMyTestTable1();

        $indexer = $this->getAccessibleMock('tx_mksearch_tests_fixtures_indexer_Dummy', ['dummy']);
        [$extKey, $cType] = $indexer->getContentType();

        $record = ['uid' => 1, 'startdate' => 2];
        $model = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Sys25\RnBase\Domain\Model\BaseModel::class, $record);
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);

        $indexer->_set('modelToIndex', $model);

        $this->callInaccessibleMethod($indexer, 'indexEnableColumns', $model, 'mytesttable_1', $indexDoc);

        self::assertSame(2, $model->getProperty('startdate'));
    }

    public function testIndexModelByMapping(): void
    {
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $model = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Domain\Model\BaseModel::class,
            ['recordField' => 123]
        );

        $this->callInaccessibleMethod(
            $indexer,
            'indexModelByMapping',
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
        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $model = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Domain\Model\BaseModel::class,
            ['recordField' => 123, 'hidden' => 1]
        );

        $this->callInaccessibleMethod(
            $indexer,
            'indexModelByMapping',
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
        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $model = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Domain\Model\BaseModel::class,
            ['recordField' => 123, 'hidden' => 1]
        );

        $this->callInaccessibleMethod(
            $indexer,
            'indexModelByMapping',
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
        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $model = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Domain\Model\BaseModel::class,
            ['recordField' => 123]
        );

        $this->callInaccessibleMethod(
            $indexer,
            'indexModelByMapping',
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
        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $model = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Domain\Model\BaseModel::class,
            ['recordField' => '']
        );

        $this->callInaccessibleMethod(
            $indexer,
            'indexModelByMapping',
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
        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $model = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Domain\Model\BaseModel::class,
            ['recordField' => '']
        );

        $this->callInaccessibleMethod(
            $indexer,
            'indexModelByMapping',
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
        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
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

        $this->callInaccessibleMethod(
            $indexer,
            'indexArrayOfModelsByMapping',
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

    public function testIndexArrayOfModelsByMappingDoesNotIndexHiddenModelsByDefault(): void
    {
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
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

        $this->callInaccessibleMethod(
            $indexer,
            'indexArrayOfModelsByMapping',
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
        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
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

        $this->callInaccessibleMethod(
            $indexer,
            'indexArrayOfModelsByMapping',
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
        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
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

        $this->callInaccessibleMethod(
            $indexer,
            'indexArrayOfModelsByMapping',
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
        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
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

        $this->callInaccessibleMethod(
            $indexer,
            'indexArrayOfModelsByMapping',
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

    private function setTcaEnableColumnsForMyTestTable1(): void
    {
        global $TCA;
        $TCA['mytesttable_1']['ctrl']['enablecolumns'] = [
            'starttime' => 'startdate',
            'endtime' => 'enddate',
            'fe_group' => 'fe_groups',
        ];
    }

    public function testHasDocToBeDeletedCallsgetRootlineByPidCorrect(): void
    {
        $indexerUtility = $this->getMock(
            'tx_mksearch_util_Indexer',
            ['getRootlineByPid']
        );
        $indexerUtility->expects($this->once())
            ->method('getRootlineByPid')
            ->with(123)
            ->willReturn([]);

        $indexer = $this->getAccessibleMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            ['getIndexerUtility']
        );
        $indexer->expects($this->once())
            ->method('getIndexerUtility')
            ->willReturn($indexerUtility);

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $model = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Domain\Model\BaseModel::class,
            ['pid' => 123]
        );
        $indexer->_set('modelToIndex', $model);

        $this->callInaccessibleMethod($indexer, 'hasDocToBeDeleted', $model, $indexDoc);
    }

    public function testHasDocToBeDeletedReturnsTrueIfOnePageInRootlineIsHidden(): void
    {
        $indexerUtility = $this->getMock(
            'tx_mksearch_util_Indexer',
            ['getRootlineByPid']
        );
        $indexerUtility->expects($this->once())
            ->method('getRootlineByPid')
            ->willReturn([
                0 => ['uid' => 1, 'hidden' => 1],
                1 => ['uid' => 2, 'hidden' => 0],
            ]);

        $indexer = $this->getAccessibleMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            ['getIndexerUtility']
        );
        $indexer->expects($this->once())
            ->method('getIndexerUtility')
            ->willReturn($indexerUtility);

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $model = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Domain\Model\BaseModel::class,
            ['pid' => 123]
        );
        $indexer->_set('modelToIndex', $model);

        self::assertTrue(
            $this->callInaccessibleMethod($indexer, 'hasDocToBeDeleted', $model, $indexDoc)
        );
    }

    public function testHasDocToBeDeletedReturnsTrueIfOnePageInRootlineIsBackendUserSection(): void
    {
        $indexerUtility = $this->getMock(
            'tx_mksearch_util_Indexer',
            ['getRootlineByPid']
        );
        $indexerUtility->expects($this->once())
            ->method('getRootlineByPid')
            ->willReturn([
                0 => ['uid' => 1, 'doktype' => 6],
                1 => ['uid' => 2, 'doktype' => 1],
            ]);

        $indexer = $this->getAccessibleMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            ['getIndexerUtility']
        );
        $indexer->expects($this->once())
            ->method('getIndexerUtility')
            ->willReturn($indexerUtility);

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $model = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Domain\Model\BaseModel::class,
            ['pid' => 123]
        );
        $indexer->_set('modelToIndex', $model);

        self::assertTrue(
            $this->callInaccessibleMethod($indexer, 'hasDocToBeDeleted', $model, $indexDoc)
        );
    }

    public function testHasDocToBeDeletedReturnsTrueIfOnePageInRootlineHasNoSearchFlag(): void
    {
        $indexerUtility = $this->getMock(
            'tx_mksearch_util_Indexer',
            ['getRootlineByPid']
        );
        $indexerUtility->expects($this->once())
            ->method('getRootlineByPid')
            ->willReturn([
                0 => ['uid' => 1, 'no_search' => 1],
                1 => ['uid' => 2, 'no_search' => 0],
            ]);

        $indexer = $this->getAccessibleMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            ['getIndexerUtility']
        );
        $indexer->expects($this->once())
            ->method('getIndexerUtility')
            ->willReturn($indexerUtility);

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $model = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Domain\Model\BaseModel::class,
            ['pid' => 123]
        );
        $indexer->_set('modelToIndex', $model);

        self::assertTrue(
            $this->callInaccessibleMethod(
                $indexer,
                'hasDocToBeDeleted',
                $model,
                $indexDoc,
                ['respectNoSearchFlagInRootline' => 1]
            )
        );
    }

    public function testHasDocToBeDeletedReturnsTrueIfOnePageInRootlineHasNoSearchFlagButFlagShoudNotBeRespected(): void
    {
        $indexerUtility = $this->getMock(
            'tx_mksearch_util_Indexer',
            ['getRootlineByPid']
        );
        $indexerUtility->expects($this->once())
            ->method('getRootlineByPid')
            ->willReturn([
                0 => ['uid' => 1, 'no_search' => 1],
                1 => ['uid' => 2, 'no_search' => 0],
            ]);

        $indexer = $this->getAccessibleMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            ['getIndexerUtility']
        );
        $indexer->expects($this->once())
            ->method('getIndexerUtility')
            ->willReturn($indexerUtility);

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $model = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Domain\Model\BaseModel::class,
            ['pid' => 123]
        );
        $indexer->_set('modelToIndex', $model);

        self::assertFalse(
            $this->callInaccessibleMethod(
                $indexer,
                'hasDocToBeDeleted',
                $model,
                $indexDoc
            )
        );
    }

    public function testHasDocToBeDeletedReturnsFalseIfAllPagesInRootlineAreOkay(): void
    {
        $indexerUtility = $this->getMock(
            'tx_mksearch_util_Indexer',
            ['getRootlineByPid']
        );
        $indexerUtility->expects($this->once())
            ->method('getRootlineByPid')
            ->willReturn([
                0 => ['uid' => 1, 'doktype' => 2],
                1 => ['uid' => 2, 'doktype' => 1],
            ]);

        $indexer = $this->getAccessibleMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            ['getIndexerUtility']
        );
        $indexer->expects($this->once())
            ->method('getIndexerUtility')
            ->willReturn($indexerUtility);

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $model = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Domain\Model\BaseModel::class,
            ['pid' => 123]
        );
        $indexer->_set('modelToIndex', $model);

        self::assertFalse(
            $this->callInaccessibleMethod($indexer, 'hasDocToBeDeleted', $model, $indexDoc)
        );
    }

    public function testGetIndexerUtility(): void
    {
        $indexer = $this->getMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            ['getContentType']
        );

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
        $indexer = $this->getMockBuilder('tx_mksearch_indexer_Base')
            ->disableOriginalConstructor()
            ->onlyMethods(['indexData', 'getIndexerUtility', 'getContentType'])
            ->getMock();
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

    public function testIndexSiteRootPageIndexesRootPageCorrectly(): void
    {
        $indexerUtility = $this->getMock(
            'tx_mksearch_util_Indexer',
            ['getSiteRootPage']
        );
        $indexerUtility->expects($this->once())
            ->method('getSiteRootPage')
            ->with(123)
            ->willReturn(['uid' => 3]);

        $options = ['indexSiteRootPage' => 1];
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            ['getIndexerUtility', 'shouldIndexSiteRootPage']
        );
        $indexer->expects($this->once())
            ->method('getIndexerUtility')
            ->willReturn($indexerUtility);

        $indexer->expects($this->once())
            ->method('shouldIndexSiteRootPage')
            ->with($options)
            ->willReturn(true);

        $indexDoc = $this->getMock(
            'tx_mksearch_model_IndexerDocumentBase',
            ['addField'],
            ['', '']
        );
        $model = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Domain\Model\BaseModel::class,
            ['pid' => 123]
        );
        $indexDoc->expects($this->once())
            ->method('addField')
            ->with('siteRootPage', 3);
        $indexer->_set('modelToIndex', $model);

        $this->callInaccessibleMethod($indexer, 'indexSiteRootPage', $model, 'tt_content', $indexDoc, $options);
    }

    public function testIndexSiteRootPageDoesntIndexSiteRootPageWhenConfigMissing(): void
    {
        $options = ['indexSiteRootPage' => 0];
        $indexer = $this->getMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            ['getIndexerUtility', 'shouldIndexSiteRootPage']
        );
        $indexer->expects($this->never())
            ->method('getIndexerUtility');

        $indexer->expects($this->once())
            ->method('shouldIndexSiteRootPage')
            ->with($options)
            ->willReturn(false);

        $indexDoc = $this->getMock(
            'tx_mksearch_model_IndexerDocumentBase',
            ['addField'],
            ['', '']
        );
        $model = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Domain\Model\BaseModel::class,
            ['pid' => 123]
        );
        $indexDoc->expects($this->never())
            ->method('addField');

        $this->callInaccessibleMethod($indexer, 'indexSiteRootPage', $model, 'tt_content', $indexDoc, $options);
    }

    #[PHPUnit\Framework\Attributes\DataProvider('getTestDataForShouldIndexSiteRootPageTest')]
    public function testShouldIndexSiteRootPage(array $options, bool $expected): void
    {
        $indexer = $this->getMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            []
        );

        self::assertEquals(
            $expected,
            $this->callInaccessibleMethod(
                $indexer,
                'shouldIndexSiteRootPage',
                $options
            )
        );
    }

    public static function getTestDataForShouldIndexSiteRootPageTest(): array
    {
        return [
            1 => [
                'options' => ['indexSiteRootPage' => 1],
                'expected' => true,
            ],
            2 => [
                'options' => ['indexSiteRootPage' => 0],
                'expected' => false,
            ],
            3 => [
                'options' => [],
                'expected' => false,
            ],
        ];
    }

    public function testAddModelsToIndex(): void
    {
        $models = ['some models'];
        $tableName = 'test_table';
        $prefer = 'prefer_me';
        $resolver = 'test_resolver';
        $data = ['test data'];
        $options = ['test options'];

        $indexerUtility = $this->getMock('tx_mksearch_util_Indexer', ['addModelsToIndex']);
        $indexerUtility->expects(self::once())
            ->method('addModelsToIndex')
            ->with($models, $tableName, $prefer, $resolver, $data, $options);

        $indexer = $this->getMock('tx_mksearch_tests_fixtures_indexer_Dummy', ['getIndexerUtility']);
        $indexer->expects(self::once())
            ->method('getIndexerUtility')
            ->willReturn($indexerUtility);

        $this->callInaccessibleMethod($indexer, 'addModelsToIndex', $models, $tableName, $prefer, $resolver, $data, $options);
    }

    public function testGroupFieldIsAdded(): void
    {
        self::markTestSkipped('Test needs refactoring.');

        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        [$extKey, $contentType] = $indexer->getContentType();
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $contentType);

        $rawData = ['uid' => 123];

        $indexDoc = $indexer->prepareSearchData('doesnt_matter', $rawData, $indexDoc, []);

        $indexedData = $indexDoc->getData();
        self::assertEquals('mksearch:dummy:123', $indexedData['group_s']->getValue());
    }

    #[PHPUnit\Framework\Attributes\DataProvider('getHandleCharBrowserFieldsData')]
    public function testHandleCharBrowserFields(
        string $title,
        string $firstChar,
    ): void {
        $indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $indexDoc = $this->getIndexDocMock($indexer);

        $indexDoc->setTitle($title);

        $this->callInaccessibleMethod(
            [$indexer, 'handleCharBrowserFields'],
            [$indexDoc]
        );

        $this->assertIndexDocHasField($indexDoc, 'first_letter_s', $firstChar);
    }

    public static function getHandleCharBrowserFieldsData(): array
    {
        return [
            1 => ['Titel', 'T'],
            2 => ['lower', 'L'],
            3 => ['Ölpreis', 'O'],
            4 => ['über', 'U'],
            5 => ['0815', '0-9'],
        ];
    }

    #[PHPUnit\Framework\Attributes\DataProvider('dataProviderPrepareSearchDataLoadsFrontendIfDesiredAndNeeded')]
    public function testPrepareSearchDataLoadsFrontendIfDesiredAndNeeded(
        int $languageUid,
        bool $loadFrontendForLocalization,
        bool $frontendLoaded,
    ): void {
        self::markTestSkipped('Test needs refactoring.');

        $indexer = $this->getMockForAbstractClass(
            $this->buildAccessibleProxy('tx_mksearch_indexer_Base'),
            [],
            '',
            false,
            false,
            false,
            ['stopIndexing']
        );
        $indexer
            ->expects(self::any())
            ->method('stopIndexing')
            ->willReturn(true);

        $indexer->_set('loadFrontendForLocalization', $loadFrontendForLocalization);

        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', 'na', 'na');
        $options = ['lang' => $languageUid];

        self::assertArrayNotHasKey('TSFE', $GLOBALS);

        $indexer->prepareSearchData('doesnt_matter', [], $indexDoc, $options);

        if (!$frontendLoaded) {
            self::assertArrayNotHasKey('TSFE', $GLOBALS);
        } else {
            self::assertTrue(is_object($GLOBALS['TSFE']));
            self::assertEquals($languageUid, Sys25\RnBase\Utility\FrontendControllerUtility::getLanguageContentId());
        }
    }

    /**
     * @return number[][]|bool[][]
     */
    public static function dataProviderPrepareSearchDataLoadsFrontendIfDesiredAndNeeded(): array
    {
        return [
            [0, false, false],
            [1, false, false],
            [0, true, false],
            [1, true, true],
            [123, true, true],
        ];
    }
}
