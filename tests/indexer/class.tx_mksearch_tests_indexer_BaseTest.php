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
    public function testGetPrimarKey()
    {
        self::markTestSkipped('Test needs refactoring.');

        /* @var $indexer tx_mksearch_tests_fixtures_indexer_Dummy */
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $options = [];

        $aRawData = ['uid' => 1, 'test_field_1' => 'test value 1'];

        $oIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);

        self::assertInstanceOf(tx_mksearch_interface_IndexerDocument, $oIndexDoc);

        $aPrimaryKey = $oIndexDoc->getPrimaryKey();
        self::assertEquals('mksearch', $aPrimaryKey['extKey']->getValue(), 'Es wurde nicht der richtige extKey gesetzt!');
        self::assertEquals('dummy', $aPrimaryKey['contentType']->getValue(), 'Es wurde nicht der richtige contentType gesetzt!');
        self::assertEquals(1, $aPrimaryKey['uid']->getValue(), 'Es wurde nicht die richtige Uid gesetzt!');
    }

    public function testCheckOptionsIncludeDeletesDocs()
    {
        self::markTestSkipped('Test needs refactoring.');

        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();
        $options = [
            'include.' => ['categories.' => [3]],
        ];
        $options2 = [
            'include.' => ['categories' => 3],
        ];

        $aRawData = ['uid' => 1, 'pid' => 1];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNull($aIndexDoc, 'Das Element wurde indiziert! Option 1');

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options2);
        self::assertNull($aIndexDoc, 'Das Element wurde indiziert! Option 2');
    }

    public function testCheckOptionsIncludeDoesNotDeleteDocs()
    {
        self::markTestSkipped('Test needs refactoring.');

        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();
        $options = [
            'include.' => ['categories.' => [2]],
        ];
        $options2 = [
            'include.' => ['categories' => 2],
        ];
        $aRawData = ['uid' => 1, 'pid' => 2];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNotNull($aIndexDoc, 'Das Element wurde nicht indiziert! Option 1');

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options2);
        self::assertNotNull($aIndexDoc, 'Das Element wurde nicht indiziert! Option 2');
    }

    public function testCheckOptionsExcludeDoesNotDeleteDocs()
    {
        self::markTestSkipped('Test needs refactoring.');

        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();
        $options = [
            'exclude.' => ['categories.' => [3]],
        ];
        $options2 = [
            'exclude.' => ['categories' => 3],
        ];

        $aRawData = ['uid' => 1, 'pid' => 1];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNotNull($aIndexDoc, 'Das Element wurde nicht indiziert! Element 1 Option 1');

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options2);
        self::assertNotNull($aIndexDoc, 'Das Element wurde nicht indiziert! Element 1 Option 2');
    }

    public function testCheckOptionsExcludeDeletesDocs()
    {
        self::markTestSkipped('Test needs refactoring.');

        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();
        $options = [
            'exclude.' => ['categories.' => [2]],
        ];
        $options2 = [
            'exclude.' => ['categories' => 2],
        ];
        $aRawData = ['uid' => 1, 'pid' => 2];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNull($aIndexDoc, 'Das Element wurde doch indiziert! Element 2 Option 1');

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options2);
        self::assertNull($aIndexDoc, 'Das Element wurde doch indiziert! Element 2 Option 2');
    }

    public function testCheckOptionsIncludeReturnsCorrectDefaultValueWithEmptyCategory()
    {
        self::markTestSkipped('Test needs refactoring.');

        $indexer = $this->getMock('tx_mksearch_tests_fixtures_indexer_Dummy', ['getTestCategories']);

        $indexer->expects($this->once())
            ->method('getTestCategories')
            ->will($this->returnValue([]));

        list($extKey, $cType) = $indexer->getContentType();
        $options = [
            'include.' => ['categories.' => [3]],
        ];

        $aRawData = ['uid' => 1, 'pid' => 1];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNull($aIndexDoc, 'Das Element wurde indiziert! Option 1');
    }

    public function testCheckOptionsExcludeReturnsCorrectDefaultValueWithEmptyCategory()
    {
        self::markTestSkipped('Test needs refactoring.');

        $indexer = $this->getMock('tx_mksearch_tests_fixtures_indexer_Dummy', ['getTestCategories']);

        $indexer->expects($this->once())
            ->method('getTestCategories')
            ->will($this->returnValue([]));

        list($extKey, $cType) = $indexer->getContentType();
        $options = [
            'exclude.' => ['categories.' => [3]],
        ];

        $aRawData = ['uid' => 1, 'pid' => 1];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNotNull($aIndexDoc, 'Das Element wurde nicht indiziert! Option 1');
    }

    public function testIndexEnableColumns()
    {
        self::markTestSkipped('Test needs refactoring.');

        $this->setTcaEnableColumnsForMyTestTable1();

        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();

        $aRawData = ['uid' => 1, 'hidden' => 0, 'startdate' => 2, 'enddate' => 3, 'fe_groups' => 4];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $indexDocData = $indexer->prepareSearchData('mytesttable_1', $aRawData, $indexDoc, [])->getData();

        // empty values are ignored
        self::assertEquals('1970-01-01T00:00:02Z', $indexDocData['starttime_dt']->getValue());
        self::assertEquals('1970-01-01T00:00:03Z', $indexDocData['endtime_dt']->getValue());
        self::assertEquals([0 => 4], $indexDocData['fe_group_mi']->getValue());
    }

    public function testIndexEnableColumnsIfTableHasNoEnableColumns()
    {
        self::markTestSkipped('Test needs refactoring.');

        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();

        $aRawData = ['uid' => 1];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
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

    public function testIndexEnableColumnsWithEmptyStarttime()
    {
        self::markTestSkipped('Test needs refactoring.');

        $this->setTcaEnableColumnsForMyTestTable1();

        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();

        $aRawData = ['uid' => 1, 'hidden' => 0, 'startdate' => 0, 'enddate' => 3, 'fe_groups' => 4];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $indexDocData = $indexer->prepareSearchData('mytesttable_1', $aRawData, $indexDoc, [])->getData();

        // empty values are ignored
        self::assertFalse(isset($indexDocData['starttime_dt']));
        self::assertEquals('1970-01-01T00:00:03Z', $indexDocData['endtime_dt']->getValue());
        self::assertEquals([0 => 4], $indexDocData['fe_group_mi']->getValue());
    }

    public function testIndexEnableColumnsWithSeveralFeGroups()
    {
        self::markTestSkipped('Test needs refactoring.');

        $this->setTcaEnableColumnsForMyTestTable1();

        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();

        $aRawData = ['uid' => 1, 'hidden' => 0, 'startdate' => 0, 'enddate' => 3, 'fe_groups' => '4,5'];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $indexDocData = $indexer->prepareSearchData('mytesttable_1', $aRawData, $indexDoc, [])->getData();

        // empty values are ignored
        self::assertFalse(isset($indexDocData['starttime_dt']));
        self::assertEquals('1970-01-01T00:00:03Z', $indexDocData['endtime_dt']->getValue());
        self::assertEquals([0 => 4, 1 => 5], $indexDocData['fe_group_mi']->getValue());
    }

    public function testIndexEnableColumnsDoesNotChangeRecordInPassedModel()
    {
        self::markTestSkipped('Test needs refactoring.');

        $this->setTcaEnableColumnsForMyTestTable1();

        $indexer = $this->getAccessibleMock('tx_mksearch_tests_fixtures_indexer_Dummy', ['dummy']);
        list($extKey, $cType) = $indexer->getContentType();

        $record = ['uid' => 1, 'startdate' => 2];
        $model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Domain\Model\BaseModel::class, $record);
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);

        $indexer->_set('modelToIndex', $model);

        $this->callInaccessibleMethod($indexer, 'indexEnableColumns', $model, 'mytesttable_1', $indexDoc);

        self::assertSame(2, $model->getProperty('startdate'));
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

    private function setTcaEnableColumnsForMyTestTable1()
    {
        global $TCA;
        $TCA['mytesttable_1']['ctrl']['enablecolumns'] = [
            'starttime' => 'startdate',
            'endtime' => 'enddate',
            'fe_group' => 'fe_groups',
        ];
    }

    /**
     * @group unit
     */
    public function testHasDocToBeDeletedCallsgetRootlineByPidCorrect()
    {
        $indexerUtility = $this->getMock(
            'stdClass',
            ['getRootlineByPid']
        );
        $indexerUtility->expects($this->once())
            ->method('getRootlineByPid')
            ->with(123)
            ->will($this->returnValue([]));

        $indexer = $this->getAccessibleMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            ['getIndexerUtility']
        );
        $indexer->expects($this->once())
            ->method('getIndexerUtility')
            ->will($this->returnValue($indexerUtility));

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \Sys25\RnBase\Domain\Model\BaseModel::class,
            ['pid' => 123]
        );
        $indexer->_set('modelToIndex', $model);

        $this->callInaccessibleMethod($indexer, 'hasDocToBeDeleted', $model, $indexDoc);
    }

    /**
     * @group unit
     */
    public function testHasDocToBeDeletedReturnsTrueIfOnePageInRootlineIsHidden()
    {
        $indexerUtility = $this->getMock(
            'stdClass',
            ['getRootlineByPid']
        );
        $indexerUtility->expects($this->once())
            ->method('getRootlineByPid')
            ->will($this->returnValue([
                0 => ['uid' => 1, 'hidden' => 1],
                1 => ['uid' => 2, 'hidden' => 0],
            ]));

        $indexer = $this->getAccessibleMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            ['getIndexerUtility']
        );
        $indexer->expects($this->once())
            ->method('getIndexerUtility')
            ->will($this->returnValue($indexerUtility));

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \Sys25\RnBase\Domain\Model\BaseModel::class,
            ['pid' => 123]
        );
        $indexer->_set('modelToIndex', $model);

        self::assertTrue(
            $this->callInaccessibleMethod($indexer, 'hasDocToBeDeleted', $model, $indexDoc)
        );
    }

    /**
     * @group unit
     */
    public function testHasDocToBeDeletedReturnsTrueIfOnePageInRootlineIsBackendUserSection()
    {
        $indexerUtility = $this->getMock(
            'stdClass',
            ['getRootlineByPid']
        );
        $indexerUtility->expects($this->once())
            ->method('getRootlineByPid')
            ->will($this->returnValue([
                0 => ['uid' => 1, 'doktype' => 6],
                1 => ['uid' => 2, 'doktype' => 1],
            ]));

        $indexer = $this->getAccessibleMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            ['getIndexerUtility']
        );
        $indexer->expects($this->once())
            ->method('getIndexerUtility')
            ->will($this->returnValue($indexerUtility));

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \Sys25\RnBase\Domain\Model\BaseModel::class,
            ['pid' => 123]
        );
        $indexer->_set('modelToIndex', $model);

        self::assertTrue(
            $this->callInaccessibleMethod($indexer, 'hasDocToBeDeleted', $model, $indexDoc)
        );
    }

    /**
     * @group unit
     */
    public function testHasDocToBeDeletedReturnsTrueIfOnePageInRootlineHasNoSearchFlag()
    {
        $indexerUtility = $this->getMock(
            'stdClass',
            ['getRootlineByPid']
        );
        $indexerUtility->expects($this->once())
            ->method('getRootlineByPid')
            ->will($this->returnValue([
                0 => ['uid' => 1, 'no_search' => 1],
                1 => ['uid' => 2, 'no_search' => 0],
            ]));

        $indexer = $this->getAccessibleMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            ['getIndexerUtility']
        );
        $indexer->expects($this->once())
            ->method('getIndexerUtility')
            ->will($this->returnValue($indexerUtility));

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \Sys25\RnBase\Domain\Model\BaseModel::class,
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

    /**
     * @group unit
     */
    public function testHasDocToBeDeletedReturnsTrueIfOnePageInRootlineHasNoSearchFlagButFlagShoudNotBeRespected()
    {
        $indexerUtility = $this->getMock(
            'stdClass',
            ['getRootlineByPid']
        );
        $indexerUtility->expects($this->once())
            ->method('getRootlineByPid')
            ->will($this->returnValue([
                0 => ['uid' => 1, 'no_search' => 1],
                1 => ['uid' => 2, 'no_search' => 0],
            ]));

        $indexer = $this->getAccessibleMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            ['getIndexerUtility']
        );
        $indexer->expects($this->once())
            ->method('getIndexerUtility')
            ->will($this->returnValue($indexerUtility));

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \Sys25\RnBase\Domain\Model\BaseModel::class,
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

    /**
     * @group unit
     */
    public function testHasDocToBeDeletedReturnsFalseIfAllPagesInRootlineAreOkay()
    {
        $indexerUtility = $this->getMock(
            'stdClass',
            ['getRootlineByPid']
        );
        $indexerUtility->expects($this->once())
            ->method('getRootlineByPid')
            ->will($this->returnValue([
                0 => ['uid' => 1, 'doktype' => 2],
                1 => ['uid' => 2, 'doktype' => 1],
            ]));

        $indexer = $this->getAccessibleMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            ['getIndexerUtility']
        );
        $indexer->expects($this->once())
            ->method('getIndexerUtility')
            ->will($this->returnValue($indexerUtility));

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \Sys25\RnBase\Domain\Model\BaseModel::class,
            ['pid' => 123]
        );
        $indexer->_set('modelToIndex', $model);

        self::assertFalse(
            $this->callInaccessibleMethod($indexer, 'hasDocToBeDeleted', $model, $indexDoc)
        );
    }

    /**
     * @group unit
     */
    public function testGetIndexerUtility()
    {
        $indexer = $this->getMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            ['dummy']
        );

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
        $indexer = $this->getMockForAbstractClass(
            'tx_mksearch_indexer_Base',
            [],
            '',
            false,
            false,
            false,
            [
                'indexData', 'getIndexerUtility',
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
    public function testIndexSiteRootPageIndexesRootPageCorrectly()
    {
        $indexerUtility = $this->getMock(
            'stdClass',
            ['getSiteRootPage']
        );
        $indexerUtility->expects($this->once())
            ->method('getSiteRootPage')
            ->with(123)
            ->will($this->returnValue(['uid' => 3]));

        $options = ['indexSiteRootPage' => 1];
        $indexer = $this->getAccessibleMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            ['getIndexerUtility', 'shouldIndexSiteRootPage']
        );
        $indexer->expects($this->once())
            ->method('getIndexerUtility')
            ->will($this->returnValue($indexerUtility));

        $indexer->expects($this->once())
            ->method('shouldIndexSiteRootPage')
            ->with($options)
            ->will($this->returnValue(true));

        $indexDoc = $this->getMock(
            'tx_mksearch_model_IndexerDocumentBase',
            ['addField'],
            ['', '']
        );
        $model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \Sys25\RnBase\Domain\Model\BaseModel::class,
            ['pid' => 123]
        );
        $indexDoc->expects($this->once())
            ->method('addField')
            ->with('siteRootPage', 3);
        $indexer->_set('modelToIndex', $model);

        $this->callInaccessibleMethod($indexer, 'indexSiteRootPage', $model, 'tt_content', $indexDoc, $options);
    }

    /**
     * @group unit
     */
    public function testIndexSiteRootPageDoesntIndexSiteRootPageWhenConfigMissing()
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
            ->will($this->returnValue(false));

        $indexDoc = $this->getMock(
            'tx_mksearch_model_IndexerDocumentBase',
            ['addField'],
            ['', '']
        );
        $model = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \Sys25\RnBase\Domain\Model\BaseModel::class,
            ['pid' => 123]
        );
        $indexDoc->expects($this->never())
            ->method('addField');

        $this->callInaccessibleMethod($indexer, 'indexSiteRootPage', $model, 'tt_content', $indexDoc, $options);
    }

    /**
     * @group unit
     *
     * @dataProvider getTestDataForShouldIndexSiteRootPageTest
     */
    public function testShouldIndexSiteRootPage($options, $expected)
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

    public function getTestDataForShouldIndexSiteRootPageTest()
    {
        return [
            __LINE__ => [
                'options' => ['indexSiteRootPage' => 1],
                'expected' => true,
            ],
            __LINE__ => [
                'options' => ['indexSiteRootPage' => 0],
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
    public function testAddModelsToIndex()
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
            ->will(self::returnValue($indexerUtility));

        $this->callInaccessibleMethod($indexer, 'addModelsToIndex', $models, $tableName, $prefer, $resolver, $data, $options);
    }

    /**
     * @group unit
     */
    public function testGroupFieldIsAdded()
    {
        self::markTestSkipped('Test needs refactoring.');

        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $contentType) = $indexer->getContentType();
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $contentType);

        $rawData = ['uid' => 123];

        $indexDoc = $indexer->prepareSearchData('doesnt_matter', $rawData, $indexDoc, []);

        $indexedData = $indexDoc->getData();
        self::assertEquals('mksearch:dummy:123', $indexedData['group_s']->getValue());
    }

    /**
     * @group unit
     *
     * @dataProvider getHandleCharBrowserFieldsData
     */
    public function testHandleCharBrowserFields(
        $title,
        $firstChar
    ) {
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $indexDoc = $this->getIndexDocMock($indexer);

        $indexDoc->setTitle($title);

        $this->callInaccessibleMethod(
            [$indexer, 'handleCharBrowserFields'],
            [$indexDoc]
        );

        $this->assertIndexDocHasField($indexDoc, 'first_letter_s', $firstChar);
    }

    /**
     * @return array
     */
    public function getHandleCharBrowserFieldsData()
    {
        return [
            __LINE__ => ['title' => 'Titel', 'first_char' => 'T'],
            __LINE__ => ['title' => 'lower', 'first_char' => 'L'],
            __LINE__ => ['title' => 'Ölpreis', 'first_char' => 'O'],
            __LINE__ => ['title' => 'über', 'first_char' => 'U'],
            __LINE__ => ['title' => '0815', 'first_char' => '0-9'],
        ];
    }

    /**
     * @param int  $languageUid
     * @param bool $loadFrontendForLocalization
     * @param bool $frontendLoaded
     *
     * @dataProvider dataProviderPrepareSearchDataLoadsFrontendIfDesiredAndNeeded
     */
    public function testPrepareSearchDataLoadsFrontendIfDesiredAndNeeded(
        $languageUid,
        $loadFrontendForLocalization,
        $frontendLoaded
    ) {
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
            ->will(self::returnValue(true));

        $indexer->_set('loadFrontendForLocalization', $loadFrontendForLocalization);

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', 'na', 'na');
        $options = ['lang' => $languageUid];

        self::assertArrayNotHasKey('TSFE', $GLOBALS);

        $indexer->prepareSearchData('doesnt_matter', [], $indexDoc, $options);

        if (!$frontendLoaded) {
            self::assertArrayNotHasKey('TSFE', $GLOBALS);
        } else {
            self::assertTrue(is_object($GLOBALS['TSFE']));
            self::assertEquals($languageUid, \Sys25\RnBase\Utility\FrontendControllerUtility::getLanguageContentId());
        }
    }

    /**
     * @return number[][]|bool[][]
     */
    public function dataProviderPrepareSearchDataLoadsFrontendIfDesiredAndNeeded()
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
