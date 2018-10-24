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


tx_rnbase::load('tx_mksearch_tests_Testcase');
tx_rnbase::load('tx_mksearch_tests_fixtures_indexer_Dummy');
tx_rnbase::load('tx_mksearch_service_indexer_core_Config');

/**
 * Wir müssen in diesem Fall mit der DB testen da wir die pages
 * Tabelle benötigen
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_tests
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_indexer_Base_testcase extends tx_mksearch_tests_Testcase
{

    /**
     * {@inheritDoc}
     * @see tx_mksearch_tests_Testcase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
        $this->destroyFrontend();
    }

    /**
     * {@inheritDoc}
     * @see tx_mksearch_tests_Testcase::tearDown()
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->destroyFrontend();
    }

    /**
     * @return void
     */
    protected function destroyFrontend()
    {
        if (isset($GLOBALS['TSFE'])){
            unset($GLOBALS['TSFE']);
        }

    }

    /**
     * Check if the uid is set correct
     */
    public function testGetPrimarKey()
    {
        /* @var $indexer tx_mksearch_tests_fixtures_indexer_Dummy */
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $options = array();

        $aRawData = array('uid' => 1, 'test_field_1' => 'test value 1');

        $oIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);

        self::assertInstanceOf(tx_mksearch_interface_IndexerDocument, $oIndexDoc);

        $aPrimaryKey = $oIndexDoc->getPrimaryKey();
        self::assertEquals('mksearch', $aPrimaryKey['extKey']->getValue(), 'Es wurde nicht der richtige extKey gesetzt!');
        self::assertEquals('dummy', $aPrimaryKey['contentType']->getValue(), 'Es wurde nicht der richtige contentType gesetzt!');
        self::assertEquals(1, $aPrimaryKey['uid']->getValue(), 'Es wurde nicht die richtige Uid gesetzt!');
    }

    /**
     *
     */
    public function testCheckOptionsIncludeDeletesDocs()
    {
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();
        $options = array(
            'include.' => array('categories.' => array(3))
        );
        $options2 = array(
            'include.' => array('categories' => 3)
        );

        $aRawData = array('uid' => 1, 'pid' => 1);
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNull($aIndexDoc, 'Das Element wurde indiziert! Option 1');

        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options2);
        self::assertNull($aIndexDoc, 'Das Element wurde indiziert! Option 2');
    }

    /**
     *
     */
    public function testCheckOptionsIncludeDoesNotDeleteDocs()
    {
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();
        $options = array(
            'include.' => array('categories.' => array(2))
        );
        $options2 = array(
            'include.' => array('categories' => 2)
        );
        $aRawData = array('uid' => 1, 'pid' => 2);
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNotNull($aIndexDoc, 'Das Element wurde nicht indiziert! Option 1');

        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options2);
        self::assertNotNull($aIndexDoc, 'Das Element wurde nicht indiziert! Option 2');
    }

    /**
     *
     */
    public function testCheckOptionsExcludeDoesNotDeleteDocs()
    {
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();
        $options = array(
            'exclude.' => array('categories.' => array(3))
        );
        $options2 = array(
            'exclude.' => array('categories' => 3)
        );

        $aRawData = array('uid' => 1, 'pid' => 1);
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNotNull($aIndexDoc, 'Das Element wurde nicht indiziert! Element 1 Option 1');

        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options2);
        self::assertNotNull($aIndexDoc, 'Das Element wurde nicht indiziert! Element 1 Option 2');
    }

/**
 *
 */
    public function testCheckOptionsExcludeDeletesDocs()
    {
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();
        $options = array(
            'exclude.' => array('categories.' => array(2))
        );
        $options2 = array(
            'exclude.' => array('categories' => 2)
        );
        $aRawData = array('uid' => 1, 'pid' => 2);
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNull($aIndexDoc, 'Das Element wurde doch indiziert! Element 2 Option 1');

        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options2);
        self::assertNull($aIndexDoc, 'Das Element wurde doch indiziert! Element 2 Option 2');
    }

    /**
     *
     */
    public function testCheckOptionsIncludeReturnsCorrectDefaultValueWithEmptyCategory()
    {
        $indexer = $this->getMock('tx_mksearch_tests_fixtures_indexer_Dummy', array('getTestCategories'));

        $indexer->expects($this->once())
            ->method('getTestCategories')
            ->will($this->returnValue(array()));

        list($extKey, $cType) = $indexer->getContentType();
        $options = array(
                'include.' => array('categories.' => array(3))
        );

        $aRawData = array('uid' => 1, 'pid' => 1);
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNull($aIndexDoc, 'Das Element wurde indiziert! Option 1');
    }

    /**
     *
     */
    public function testCheckOptionsExcludeReturnsCorrectDefaultValueWithEmptyCategory()
    {
        $indexer = $this->getMock('tx_mksearch_tests_fixtures_indexer_Dummy', array('getTestCategories'));

        $indexer->expects($this->once())
            ->method('getTestCategories')
            ->will($this->returnValue(array()));

        list($extKey, $cType) = $indexer->getContentType();
        $options = array(
                'exclude.' => array('categories.' => array(3))
        );

        $aRawData = array('uid' => 1, 'pid' => 1);
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNotNull($aIndexDoc, 'Das Element wurde nicht indiziert! Option 1');
    }

    /**
     *
     */
    public function testIndexEnableColumns()
    {
        $this->setTcaEnableColumnsForMyTestTable1();

        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();

        $aRawData = array('uid' => 1, 'hidden' => 0,'startdate' => 2,'enddate' => 3,'fe_groups' => 4);
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $indexDocData = $indexer->prepareSearchData('mytesttable_1', $aRawData, $indexDoc, array())->getData();

        //empty values are ignored
        self::assertEquals('1970-01-01T00:00:02Z', $indexDocData['starttime_dt']->getValue());
        self::assertEquals('1970-01-01T00:00:03Z', $indexDocData['endtime_dt']->getValue());
        self::assertEquals(array(0 => 4), $indexDocData['fe_group_mi']->getValue());
    }

    /**
     *
     */
    public function testIndexEnableColumnsIfTableHasNoEnableColumns()
    {
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();

        $aRawData = array('uid' => 1);
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $indexDocData = $indexer->prepareSearchData('doesn_t_matter', $aRawData, $indexDoc, array())->getData();

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

    /**
     *
     */
    public function testIndexEnableColumnsWithEmptyStarttime()
    {
        $this->setTcaEnableColumnsForMyTestTable1();

        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();

        $aRawData = array('uid' => 1, 'hidden' => 0,'startdate' => 0,'enddate' => 3,'fe_groups' => 4);
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $indexDocData = $indexer->prepareSearchData('mytesttable_1', $aRawData, $indexDoc, array())->getData();

        //empty values are ignored
        self::assertFalse(isset($indexDocData['starttime_dt']));
        self::assertEquals('1970-01-01T00:00:03Z', $indexDocData['endtime_dt']->getValue());
        self::assertEquals(array(0 => 4), $indexDocData['fe_group_mi']->getValue());
    }

    /**
     *
     */
    public function testIndexEnableColumnsWithSeveralFeGroups()
    {
        $this->setTcaEnableColumnsForMyTestTable1();

        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();

        $aRawData = array('uid' => 1, 'hidden' => 0,'startdate' => 0,'enddate' => 3,'fe_groups' => '4,5');
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $indexDocData = $indexer->prepareSearchData('mytesttable_1', $aRawData, $indexDoc, array())->getData();

        //empty values are ignored
        self::assertFalse(isset($indexDocData['starttime_dt']));
        self::assertEquals('1970-01-01T00:00:03Z', $indexDocData['endtime_dt']->getValue());
        self::assertEquals(array(0 => 4,1 => 5), $indexDocData['fe_group_mi']->getValue());
    }

    /**
     *
     */
    public function testIndexEnableColumnsDoesNotChangeRecordInPassedModel()
    {
        $this->setTcaEnableColumnsForMyTestTable1();

        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();

        $record = array('uid' => 1, 'startdate' => 2);
        $model = tx_rnbase::makeInstance('tx_rnbase_model_base', $record);
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);

        $this->callInaccessibleMethod($indexer, 'indexEnableColumns', $model, 'mytesttable_1', $indexDoc);

        self::assertSame(2, $model->record['startdate']);
    }

    /**
     *
     */
    public function testIndexModelByMapping()
    {
        $indexDoc = tx_rnbase::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $model = tx_rnbase::makeInstance(
            'tx_rnbase_model_base',
            array('recordField' => 123)
        );

        $this->callInaccessibleMethod(
            $indexer,
            'indexModelByMapping',
            $model,
            array('recordField' => 'documentField'),
            $indexDoc
        );
        $docData = $indexDoc->getData();

        self::assertEquals(
            123,
            $docData['documentField']->getValue(),
            'model falsch indiziert'
        );
    }

    /**
     *
     */
    public function testIndexModelByMappingDoesNotIndexHiddenModelsByDefault()
    {
        $indexDoc = tx_rnbase::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $model = tx_rnbase::makeInstance(
            'tx_rnbase_model_base',
            array('recordField' => 123, 'hidden' => 1)
        );

        $this->callInaccessibleMethod(
            $indexer,
            'indexModelByMapping',
            $model,
            array('recordField' => 'documentField'),
            $indexDoc
        );
        $docData = $indexDoc->getData();

        self::assertFalse(
            isset($docData['documentField']),
            'model doch indiziert'
        );
    }

    /**
     *
     */
    public function testIndexModelByMappingIndexesHiddenModelsIfSet()
    {
        $indexDoc = tx_rnbase::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $model = tx_rnbase::makeInstance(
            'tx_rnbase_model_base',
            array('recordField' => 123, 'hidden' => 1)
        );

        $this->callInaccessibleMethod(
            $indexer,
            'indexModelByMapping',
            $model,
            array('recordField' => 'documentField'),
            $indexDoc,
            '',
            array(),
            false
        );
        $docData = $indexDoc->getData();

        self::assertEquals(
            123,
            $docData['documentField']->getValue(),
            'model falsch indiziert'
        );
    }

    /**
     *
     */
    public function testIndexModelByMappingWithPrefix()
    {
        $indexDoc = tx_rnbase::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $model = tx_rnbase::makeInstance(
            'tx_rnbase_model_base',
            array('recordField' => 123)
        );

        $this->callInaccessibleMethod(
            $indexer,
            'indexModelByMapping',
            $model,
            array('recordField' => 'documentField'),
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

    /**
     *
     */
    public function testIndexModelByMappingMapsNotEmptyFields()
    {
        $indexDoc = tx_rnbase::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $model = tx_rnbase::makeInstance(
            'tx_rnbase_model_base',
            array('recordField' => '')
        );

        $this->callInaccessibleMethod(
            $indexer,
            'indexModelByMapping',
            $model,
            array('recordField' => 'documentField'),
            $indexDoc
        );
        $docData = $indexDoc->getData();

        self::assertFalse(
            isset($docData['documentField']),
            'model doch indiziert'
        );
    }

    /**
     *
     */
    public function testIndexModelByMappingMapsEmptyFieldsIfKeepEmptyOption()
    {
        $indexDoc = tx_rnbase::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $model = tx_rnbase::makeInstance(
            'tx_rnbase_model_base',
            array('recordField' => '')
        );

        $this->callInaccessibleMethod(
            $indexer,
            'indexModelByMapping',
            $model,
            array('recordField' => 'documentField'),
            $indexDoc,
            '',
            array('keepEmpty' => 1)
        );
        $docData = $indexDoc->getData();

        self::assertEquals(
            '',
            $docData['documentField']->getValue(),
            'model falsch indiziert'
        );
    }

    /**
     *
     */
    public function testIndexArrayOfModelsByMapping()
    {
        $indexDoc = tx_rnbase::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $models = array(
            tx_rnbase::makeInstance(
                'tx_rnbase_model_base',
                array('recordField' => 123)
            ),
            tx_rnbase::makeInstance(
                'tx_rnbase_model_base',
                array('recordField' => 456)
            )
        );

        $this->callInaccessibleMethod(
            $indexer,
            'indexArrayOfModelsByMapping',
            $models,
            array('recordField' => 'documentField'),
            $indexDoc
        );
        $docData = $indexDoc->getData();

        self::assertEquals(
            array(123, 456),
            $docData['documentField']->getValue(),
            'models falsch indiziert'
        );
    }

    /**
     *
     */
    public function testIndexArrayOfModelsByMappingDoesNotIndexHiddenModelsByDefault()
    {
        $indexDoc = tx_rnbase::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $models = array(
            tx_rnbase::makeInstance(
                'tx_rnbase_model_base',
                array('recordField' => 123)
            ),
            tx_rnbase::makeInstance(
                'tx_rnbase_model_base',
                array('recordField' => 456, 'hidden' => 1)
            )
        );

        $this->callInaccessibleMethod(
            $indexer,
            'indexArrayOfModelsByMapping',
            $models,
            array('recordField' => 'documentField'),
            $indexDoc
        );
        $docData = $indexDoc->getData();

        self::assertEquals(
            array(123),
            $docData['documentField']->getValue(),
            'models falsch indiziert'
        );
    }


    /**
     *
     */
    public function testIndexArrayOfModelsByMappingIndexesHiddenModelsIfSet()
    {
        $indexDoc = tx_rnbase::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $models = array(
            tx_rnbase::makeInstance(
                'tx_rnbase_model_base',
                array('recordField' => 123)
            ),
            tx_rnbase::makeInstance(
                'tx_rnbase_model_base',
                array('recordField' => 456, 'hidden' => 1)
            )
        );

        $this->callInaccessibleMethod(
            $indexer,
            'indexArrayOfModelsByMapping',
            $models,
            array('recordField' => 'documentField'),
            $indexDoc,
            '',
            array(),
            false
        );
        $docData = $indexDoc->getData();

        self::assertEquals(
            array(123, 456),
            $docData['documentField']->getValue(),
            'models falsch indiziert'
        );
    }

    /**
     *
     */
    public function testIndexArrayOfModelsByMappingWithPrefix()
    {
        $indexDoc = tx_rnbase::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $models = array(
            tx_rnbase::makeInstance(
                'tx_rnbase_model_base',
                array('recordField' => 123)
            ),
            tx_rnbase::makeInstance(
                'tx_rnbase_model_base',
                array('recordField' => 456)
            )
        );

        $this->callInaccessibleMethod(
            $indexer,
            'indexArrayOfModelsByMapping',
            $models,
            array('recordField' => 'documentField'),
            $indexDoc,
            'test_'
        );
        $docData = $indexDoc->getData();

        self::assertEquals(
            array(123, 456),
            $docData['test_documentField']->getValue(),
            'model falsch indiziert'
        );
    }

    /**
     *
     */
    public function testIndexArrayOfModelsByMappingMapsNotEmptyFields()
    {
        $indexDoc = tx_rnbase::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $models = array(
            tx_rnbase::makeInstance(
                'tx_rnbase_model_base',
                array('recordField' => '')
            ),
            tx_rnbase::makeInstance(
                'tx_rnbase_model_base',
                array('recordField' => '')
            )
        );

        $this->callInaccessibleMethod(
            $indexer,
            'indexArrayOfModelsByMapping',
            $models,
            array('recordField' => 'documentField'),
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
        $TCA['mytesttable_1']['ctrl']['enablecolumns'] = array(
            'starttime' => 'startdate',
            'endtime' => 'enddate',
            'fe_group' => 'fe_groups',
        );
    }

    /**
     * @group unit
     */
    public function testGetCoreConfigUtility()
    {
        self::assertEquals(
            'tx_mksearch_service_indexer_core_Config',
            $this->callInaccessibleMethod(
                tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy'),
                'getCoreConfigUtility'
            )
        );
    }

    /**
     * @group unit
     */
    public function testHasDocToBeDeletedCallsGetRootlineCorrect()
    {
        $coreConfigUtility = $this->getMock(
            'stdClass',
            array('getRootline')
        );
        $coreConfigUtility->expects($this->once())
            ->method('getRootline')
            ->with(123)
            ->will($this->returnValue(array()));

        $indexer = $this->getMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            array('getCoreConfigUtility')
        );
        $indexer->expects($this->once())
            ->method('getCoreConfigUtility')
            ->will($this->returnValue($coreConfigUtility));

        $indexDoc = tx_rnbase::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $model = tx_rnbase::makeInstance(
            'tx_rnbase_model_base',
            array('pid' => 123)
        );
        $this->callInaccessibleMethod($indexer, 'hasDocToBeDeleted', $model, $indexDoc);
    }

    /**
     * @group unit
     */
    public function testHasDocToBeDeletedReturnsTrueIfOnePageInRootlineIsHidden()
    {
        $coreConfigUtility = $this->getMock(
            'stdClass',
            array('getRootline')
        );
        $coreConfigUtility->expects($this->once())
            ->method('getRootline')
            ->will($this->returnValue(array(
                0 => array('uid' => 1, 'hidden' => 1),
                1 => array('uid' => 2, 'hidden' => 0)
            )));

        $indexer = $this->getMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            array('getCoreConfigUtility')
        );
        $indexer->expects($this->once())
            ->method('getCoreConfigUtility')
            ->will($this->returnValue($coreConfigUtility));

        $indexDoc = tx_rnbase::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $model = tx_rnbase::makeInstance(
            'tx_rnbase_model_base',
            array('pid' => 123)
        );

        self::assertTrue(
            $this->callInaccessibleMethod($indexer, 'hasDocToBeDeleted', $model, $indexDoc)
        );
    }

    /**
     * @group unit
     */
    public function testHasDocToBeDeletedReturnsTrueIfOnePageInRootlineIsBackendUserSection()
    {
        $coreConfigUtility = $this->getMock(
            'stdClass',
            array('getRootline')
        );
        $coreConfigUtility->expects($this->once())
            ->method('getRootline')
            ->will($this->returnValue(array(
                0 => array('uid' => 1, 'doktype' => 6),
                1 => array('uid' => 2, 'doktype' => 1)
            )));

        $indexer = $this->getMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            array('getCoreConfigUtility')
        );
        $indexer->expects($this->once())
            ->method('getCoreConfigUtility')
            ->will($this->returnValue($coreConfigUtility));

        $indexDoc = tx_rnbase::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $model = tx_rnbase::makeInstance(
            'tx_rnbase_model_base',
            array('pid' => 123)
        );

        self::assertTrue(
            $this->callInaccessibleMethod($indexer, 'hasDocToBeDeleted', $model, $indexDoc)
        );
    }

    /**
     * @group unit
     */
    public function testHasDocToBeDeletedReturnsTrueIfOnePageInRootlineHasNoSearchFlag()
    {
        $coreConfigUtility = $this->getMock(
            'stdClass',
            array('getRootline')
        );
        $coreConfigUtility->expects($this->once())
            ->method('getRootline')
            ->will($this->returnValue(array(
                0 => array('uid' => 1, 'no_search' => 1),
                1 => array('uid' => 2, 'no_search' => 0)
            )));

        $indexer = $this->getMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            array('getCoreConfigUtility')
        );
        $indexer->expects($this->once())
            ->method('getCoreConfigUtility')
            ->will($this->returnValue($coreConfigUtility));

        $indexDoc = tx_rnbase::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $model = tx_rnbase::makeInstance(
            'tx_rnbase_model_base',
            array('pid' => 123)
        );

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
        $coreConfigUtility = $this->getMock(
            'stdClass',
            array('getRootline')
        );
        $coreConfigUtility->expects($this->once())
            ->method('getRootline')
            ->will($this->returnValue(array(
                0 => array('uid' => 1, 'no_search' => 1),
                1 => array('uid' => 2, 'no_search' => 0)
            )));

        $indexer = $this->getMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            array('getCoreConfigUtility')
        );
        $indexer->expects($this->once())
            ->method('getCoreConfigUtility')
            ->will($this->returnValue($coreConfigUtility));

        $indexDoc = tx_rnbase::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $model = tx_rnbase::makeInstance(
            'tx_rnbase_model_base',
            array('pid' => 123)
        );

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
        $coreConfigUtility = $this->getMock(
            'stdClass',
            array('getRootline')
        );
        $coreConfigUtility->expects($this->once())
            ->method('getRootline')
            ->will($this->returnValue(array(
                0 => array('uid' => 1, 'doktype' => 2),
                1 => array('uid' => 2, 'doktype' => 1)
            )));

        $indexer = $this->getMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            array('getCoreConfigUtility')
        );
        $indexer->expects($this->once())
            ->method('getCoreConfigUtility')
            ->will($this->returnValue($coreConfigUtility));

        $indexDoc = tx_rnbase::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            '',
            ''
        );
        $model = tx_rnbase::makeInstance(
            'tx_rnbase_model_base',
            array('pid' => 123)
        );

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
            array('getCoreConfigUtility')
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
            array(),
            '',
            false,
            false,
            false,
            array(
                'indexData', 'getIndexerUtility'
            )
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
    public function testIndexSiteRootPageIndexesRootPageCorrectly()
    {
        $coreConfigUtility = $this->getMock(
            'stdClass',
            array('getSiteRootPage')
        );
        $coreConfigUtility->expects($this->once())
            ->method('getSiteRootPage')
            ->with(123)
            ->will($this->returnValue(array('uid' => 3)));

        $options = array('indexSiteRootPage' => 1);
        $indexer = $this->getMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            array('getCoreConfigUtility', 'shouldIndexSiteRootPage')
        );
        $indexer->expects($this->once())
            ->method('getCoreConfigUtility')
            ->will($this->returnValue($coreConfigUtility));

        $indexer->expects($this->once())
            ->method('shouldIndexSiteRootPage')
            ->with($options)
            ->will($this->returnValue(true));

        $indexDoc = $this->getMock(
            'tx_mksearch_model_IndexerDocumentBase',
            array('addField'),
            array('', '')
        );
        $model = tx_rnbase::makeInstance(
            'tx_rnbase_model_base',
            array('pid' => 123)
        );
        $indexDoc->expects($this->once())
            ->method('addField')
            ->with('siteRootPage', 3);

        $this->callInaccessibleMethod($indexer, 'indexSiteRootPage', $model, 'tt_content', $indexDoc, $options);
    }
    /**
     * @group unit
     */
    public function testIndexSiteRootPageDoesntIndexSiteRootPageWhenConfigMissing()
    {
        $options = array('indexSiteRootPage' => 0);
        $indexer = $this->getMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            array('getCoreConfigUtility', 'shouldIndexSiteRootPage')
        );
        $indexer->expects($this->never())
            ->method('getCoreConfigUtility');

        $indexer->expects($this->once())
            ->method('shouldIndexSiteRootPage')
            ->with($options)
            ->will($this->returnValue(false));

        $indexDoc = $this->getMock(
            'tx_mksearch_model_IndexerDocumentBase',
            array('addField'),
            array('', '')
        );
        $model = tx_rnbase::makeInstance(
            'tx_rnbase_model_base',
            array('pid' => 123)
        );
        $indexDoc->expects($this->never())
            ->method('addField');

        $this->callInaccessibleMethod($indexer, 'indexSiteRootPage', $model, 'tt_content', $indexDoc, $options);
    }

    /**
     * @group unit
     * @dataProvider getTestDataForShouldIndexSiteRootPageTest
     */
    public function testShouldIndexSiteRootPage($options, $expected)
    {
        $indexer = $this->getMock(
            'tx_mksearch_tests_fixtures_indexer_Dummy',
            array()
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
        return array(
            __LINE__ => array(
                'options' => array('indexSiteRootPage' => 1),
                'expected' => true,
            ),
            __LINE__ => array(
                'options' => array('indexSiteRootPage' => 0),
                'expected' => false,
            ),
            __LINE__ => array(
                'options' => array(),
                'expected' => false,
            ),
        );
    }

    /**
     * @group unit
     */
    public function testAddModelsToIndex()
    {
        $models = array('some models');
        $tableName = 'test_table';
        $prefer = 'prefer_me';
        $resolver = 'test_resolver';
        $data = array('test data');
        $options = array('test options');

        $indexerUtility = $this->getMock('tx_mksearch_util_Indexer', array('addModelsToIndex'));
        $indexerUtility->expects(self::once())
            ->method('addModelsToIndex')
            ->with($models, $tableName, $prefer, $resolver, $data, $options);

        $indexer = $this->getMock('tx_mksearch_tests_fixtures_indexer_Dummy', array('getIndexerUtility'));
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
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $contentType) = $indexer->getContentType();
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $contentType);

        $rawData = array('uid' => 123);

        $indexDoc = $indexer->prepareSearchData('doesnt_matter', $rawData, $indexDoc, array());

        $indexedData = $indexDoc->getData();
        self::assertEquals('mksearch:dummy:123', $indexedData['group_s']->getValue());
    }

    /**
     * @group unit
     * @dataProvider getHandleCharBrowserFieldsData
     */
    public function testHandleCharBrowserFields(
        $title,
        $firstChar
    ) {
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $indexDoc = $this->getIndexDocMock($indexer);

        $indexDoc->setTitle($title);

        $this->callInaccessibleMethod(
            array($indexer, 'handleCharBrowserFields'),
            array($indexDoc)
        );

        $this->assertIndexDocHasField($indexDoc, 'first_letter_s', $firstChar);
    }

    /**
     * @return array
     */
    public function getHandleCharBrowserFieldsData()
    {
        return array(
            __LINE__ => array('title' => 'Titel', 'first_char' => 'T'),
            __LINE__ => array('title' => 'lower', 'first_char' => 'L'),
            __LINE__ => array('title' => 'Ölpreis', 'first_char' => 'O'),
            __LINE__ => array('title' => 'über', 'first_char' => 'U'),
            __LINE__ => array('title' => '0815', 'first_char' => '0-9'),
        );
    }

    /**
     * @param int $languageUid
     * @param bool $loadFrontendForLocalization
     * @param bool $frontendLoaded
     * @dataProvider dataProviderPrepareSearchDataLoadsFrontendIfDesiredAndNeeded
     */
    public function testPrepareSearchDataLoadsFrontendIfDesiredAndNeeded(
        $languageUid,
        $loadFrontendForLocalization,
        $frontendLoaded
    ) {
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

        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', 'na', 'na');
        $options = ['lang' => $languageUid];

        self::assertFalse(is_object($GLOBALS['TSFE']));

        $indexer->prepareSearchData('doesnt_matter', [], $indexDoc, $options);

        if (!$frontendLoaded) {
            self::assertFalse($frontendLoaded, is_object($GLOBALS['TSFE']));
        } else {
            self::assertTrue(is_object($GLOBALS['TSFE']));
            self::assertEquals($languageUid, $GLOBALS['TSFE']->sys_language_content);
        }

    }

    /**
     * @return number[][]|boolean[][]
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
