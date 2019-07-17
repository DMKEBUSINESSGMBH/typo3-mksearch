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
require_once tx_rnbase_util_Extensions::extPath('mksearch', 'lib/Apache/Solr/Document.php');

/**
 * tx_mksearch_tests_indexer_IrfaqTest.
 *
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_indexer_IrfaqTest extends tx_mksearch_tests_Testcase
{
    /**
     * {@inheritdoc}
     *
     * @see tx_mksearch_tests_Testcase::setUp()
     */
    protected function setUp()
    {
        if (!tx_rnbase_util_Extensions::isLoaded('irfaq')) {
            self::markTestSkipped('irfaq nicht installiert');
        }

        parent::setUp();
    }

    /**
     * @group unit
     */
    public function testCreateModel()
    {
        $irfaqModel = $this->callInaccessibleMethod(
            tx_rnbase::makeInstance('tx_mksearch_indexer_Irfaq'),
            'createModel',
            array('uid' => 123)
        );
        self::assertInstanceOf('tx_mksearch_model_irfaq_Question', $irfaqModel);
        self::assertEquals(123, $irfaqModel->getUid());
    }

    /**
     * @group unit
     */
    public function testGetIrfaqExpertService()
    {
        self::assertInstanceOf(
            'tx_mksearch_service_irfaq_Expert',
            $this->callInaccessibleMethod(tx_rnbase::makeInstance('tx_mksearch_indexer_Irfaq'), 'getIrfaqExpertService')
        );
    }

    /**
     * @group unit
     */
    public function testGetIrfaqCategoryService()
    {
        self::assertInstanceOf(
            'tx_mksearch_service_irfaq_Category',
            $this->callInaccessibleMethod(tx_rnbase::makeInstance('tx_mksearch_indexer_Irfaq'), 'getIrfaqCategoryService')
        );
    }

    /**
     * @group unit
     */
    public function testPrepareSearchDataIndexesQuestionDataWhenTableQuestionsWithExpert()
    {
        $record = array(
            'uid' => 1,
            'sorting' => 87,
            'q' => '1. FAQ',
            'cat' => 789,
            'a' => '<span>You have to know this and this</span>',
            'expert' => 456,
            'related' => 'something related',
            'related_links' => 'some related links',
            'faq_files' => 'some files',
            'tstamp' => 123,
        );

        $irfaqExpertService = $this->getMock('tx_mksearch_service_irfaq_Expert', array('get'));
        $irfaqExpertService->expects(self::once())
            ->method('get')
            ->will(self::returnValue(tx_rnbase::makeInstance('tx_mksearch_model_irfaq_Expert', array())));

        $irfaqCategoryService = $this->getMock('tx_mksearch_service_irfaq_Category', array('getByQuestion'));
        $irfaqCategoryService->expects(self::exactly(2))
            ->method('getByQuestion')
            ->will(self::returnValue(array()));

        $indexer = $this->getMock('tx_mksearch_indexer_Irfaq', array('getIrfaqCategoryService', 'getIrfaqExpertService'));
        $indexer->expects(self::exactly(2))
            ->method('getIrfaqCategoryService')
            ->will(self::returnValue($irfaqCategoryService));
        $indexer->expects(self::once())
            ->method('getIrfaqExpertService')
            ->will(self::returnValue($irfaqExpertService));

        $indexDocData = $this->prepareSearchData($indexer, $record)->getData();

        self::assertEquals(
            87,
            $indexDocData['sorting_i']->getValue(),
            'Es wurde nicht die richtige Sortierung indiziert!'
        );
        self::assertEquals(
            '1. FAQ',
            $indexDocData['q_s']->getValue(),
            'Es wurde nicht die richtige Frage indiziert!'
        );
        self::assertEquals(
            'You have to know this and this',
            $indexDocData['a_s']->getValue(),
            'Es wurde nicht der richtige Fragetext indiziert!'
        );
        self::assertEquals(
            '<span>You have to know this and this</span>',
            $indexDocData['a_with_html_s']->getValue(),
            'Es wurde nicht der richtige Fragetext mit HTML indiziert!'
        );
        self::assertEquals(
            'something related',
            $indexDocData['related_s']->getValue(),
            'Es wurde nicht das richtige related indiziert!'
        );
        self::assertEquals(
            'some related links',
            $indexDocData['related_links_s']->getValue(),
            'Es wurden nicht die richtigen related Links indiziert!'
        );
        self::assertEquals(
            'some files',
            $indexDocData['faq_files_s']->getValue(),
            'Es wurde nicht der richtige Dateien indiziert!'
        );
        self::assertEquals(
            123,
            $indexDocData['tstamp']->getValue(),
            'Es wurde nicht der richtige tstamp indiziert!'
        );
    }

    /**
     * @group unit
     */
    public function testPrepareSearchDataIndexesExpertDataWhenTableQuestionsWithExpert()
    {
        $record = array(
            'uid' => 1,
            'expert' => 456,
        );

        $irfaqExpertService = $this->getMock('tx_mksearch_service_irfaq_Expert', array('get'));
        $irfaqExpertService->expects(self::once())
            ->method('get')
            ->with(456)
            ->will(self::returnValue(tx_rnbase::makeInstance(
                'tx_mksearch_model_irfaq_Expert',
                array(
                    'uid' => 456,
                    'name' => '1. Expert',
                    'email' => 'mail@expert.de',
                    'url' => 'some url',
                )
            )));

        $irfaqCategoryService = $this->getMock('tx_mksearch_service_irfaq_Category', array('getByQuestion'));
        $irfaqCategoryService->expects(self::exactly(2))
            ->method('getByQuestion')
            ->will(self::returnValue(array()));

        $indexer = $this->getMock('tx_mksearch_indexer_Irfaq', array('getIrfaqCategoryService', 'getIrfaqExpertService'));
        $indexer->expects(self::exactly(2))
            ->method('getIrfaqCategoryService')
            ->will(self::returnValue($irfaqCategoryService));
        $indexer->expects(self::once())
            ->method('getIrfaqExpertService')
            ->will(self::returnValue($irfaqExpertService));

        $indexDocData = $this->prepareSearchData($indexer, $record)->getData();

        self::assertEquals(
            456,
            $indexDocData['expert_i']->getValue(),
            'Es wurde nicht der richtige Experte indiziert!'
        );
        self::assertEquals(
            '1. Expert',
            $indexDocData['expert_name_s']->getValue(),
            'Es wurde nicht der richtige Expertenname indiziert!'
        );
        self::assertEquals(
            'mail@expert.de',
            $indexDocData['expert_email_s']->getValue(),
            'Es wurde nicht die richtige Expertenmail indiziert!'
        );
        self::assertEquals(
            'some url',
            $indexDocData['expert_url_s']->getValue(),
            'Es wurde nicht die richtige Expertenurl indiziert!'
        );
    }

    /**
     * @group unit
     */
    public function testPrepareSearchDataIndexesCategoryDataWhenTableQuestionsWithExpert()
    {
        $record = array(
            'uid' => 1,
        );

        $irfaqExpertService = $this->getMock('tx_mksearch_service_irfaq_Expert', array('get'));
        $irfaqExpertService->expects(self::once())
            ->method('get')
            ->will(self::returnValue(tx_rnbase::makeInstance('tx_mksearch_model_irfaq_Expert', array())));

        $irfaqCategoryService = $this->getMock('tx_mksearch_service_irfaq_Category', array('getByQuestion'));
        $irfaqCategoryService->expects(self::exactly(2))
            ->method('getByQuestion')
            ->with(tx_rnbase::makeInstance('tx_mksearch_model_irfaq_Question', $record))
            ->will(self::returnValue(array(
                0 => tx_rnbase::makeInstance(
                    'tx_mksearch_model_irfaq_Category',
                    array(
                        'uid' => 1,
                        'sorting' => 47,
                        'title' => '1. FAQ Category',
                        'shortcut' => '1. Shortcut',
                    )
                ),
                1 => tx_rnbase::makeInstance(
                    'tx_mksearch_model_irfaq_Category',
                    array(
                        'uid' => 2,
                        'sorting' => 48,
                        'title' => '2. FAQ Category',
                        'shortcut' => '2. Shortcut',
                    )
                ),
            )));

        $indexer = $this->getMock('tx_mksearch_indexer_Irfaq', array('getIrfaqCategoryService', 'getIrfaqExpertService'));
        $indexer->expects(self::exactly(2))
            ->method('getIrfaqCategoryService')
            ->will(self::returnValue($irfaqCategoryService));
        $indexer->expects(self::once())
            ->method('getIrfaqExpertService')
            ->will(self::returnValue($irfaqExpertService));

        $indexDocData = $this->prepareSearchData($indexer, $record)->getData();

        self::assertEquals(
            array(0 => '1', 1 => '2'),
            $indexDocData['category_mi']->getValue(),
            'Es wurden nicht die richtigen Kategorie-Uids indiziert!'
        );
        self::assertEquals(
            array(0 => '47', 1 => '48'),
            $indexDocData['category_sorting_mi']->getValue(),
            'Es wurden nicht die richtigen Kategorie-Sortierungen indiziert!'
        );
        self::assertEquals(
            array(0 => '1. FAQ Category', 1 => '2. FAQ Category'),
            $indexDocData['category_title_ms']->getValue(),
            'Es wurden nicht die richtigen Kategorie-Titel indiziert!'
        );
        self::assertEquals(
            array(0 => '1. Shortcut', 1 => '2. Shortcut'),
            $indexDocData['category_shortcut_ms']->getValue(),
            'Es wurden nicht die richtigen Kategorie-Shortcuts indiziert!'
        );
        self::assertEquals(
            '1. Shortcut',
            $indexDocData['category_first_shortcut_s']->getValue(),
            'Es wurden nicht der richtige erste Kategorie-Shortcut indiziert!'
        );
    }

    /**
     * @group unit
     */
    public function testPrepareSearchDataWhenTableExpertsStopsIndexing()
    {
        $record = array('uid' => 123, 'some_field' => 456);

        $indexer = $this->getMock('tx_mksearch_indexer_Irfaq', array('handleRelatedTableChanged'));
        $indexer->expects(self::once())
            ->method('handleRelatedTableChanged')
            ->with($record, 'Expert');

        self::assertNull($this->prepareSearchData($indexer, $record, 'tx_irfaq_expert'));
    }

    /**
     * @group unit
     */
    public function testPrepareSearchDataWhenTableCategoryStopsIndexing()
    {
        $record = array('uid' => 123, 'some_field' => 456);

        $indexer = $this->getMock('tx_mksearch_indexer_Irfaq', array('handleRelatedTableChanged'));
        $indexer->expects(self::once())
            ->method('handleRelatedTableChanged')
            ->with($record, 'Category');

        self::assertNull($this->prepareSearchData($indexer, $record, 'tx_irfaq_cat'));
    }

    /**
     * @group unit
     */
    public function testGetIrfaqQuestionService()
    {
        self::assertInstanceOf(
            'tx_mksearch_service_irfaq_Question',
            $this->callInaccessibleMethod(tx_rnbase::makeInstance('tx_mksearch_indexer_Irfaq'), 'getIrfaqQuestionService')
        );
    }

    /**
     * @group unit
     */
    public function testHandleRelatedTableChanged()
    {
        $questionService = $this->getMock('tx_mksearch_service_irfaq_Question', array('getByMyTestMethod'));
        $questionService->expects(self::once())
            ->method('getByMyTestMethod')
            ->will(self::returnValue(array(1, 2, 3)));

        $indexer = $this->getMock('tx_mksearch_indexer_Irfaq', array('getIrfaqQuestionService', 'addModelsToIndex'));
        $indexer->expects(self::once())
            ->method('getIrfaqQuestionService')
            ->will(self::returnValue($questionService));

        $indexer->expects(self::once())
            ->method('addModelsToIndex')
            ->with(array(1, 2, 3), 'tx_irfaq_q');

        $this->callInaccessibleMethod($indexer, 'handleRelatedTableChanged', array('uid' => 123), 'MyTestMethod');
    }

    /**
     * @group unit
     * @dataProvider getIncludeOptions
     *
     * @param array $options
     * @param bool  $isDeleted
     */
    public function testPrepareSearchDataSetsDocDeletedDependingOnIncludeOptions(array $options, $isDeleted, $noCategories = false)
    {
        $record = array('uid' => 1, 'pid' => 2);

        $irfaqExpertService = $this->getMock('tx_mksearch_service_irfaq_Expert', array('get'));
        $irfaqExpertService->expects(self::any())
            ->method('get')
            ->will(self::returnValue(tx_rnbase::makeInstance('tx_mksearch_model_irfaq_Expert', array())));

        $irfaqCategoryService = $this->getMock('tx_mksearch_service_irfaq_Category', array('getByQuestion'));

        if ($noCategories) {
            $categories = array();
        } else {
            $categories = array(
                0 => tx_rnbase::makeInstance(
                    'tx_mksearch_model_irfaq_Category',
                    array(
                        'uid' => 1,
                    )
                ),
                1 => tx_rnbase::makeInstance(
                    'tx_mksearch_model_irfaq_Category',
                    array(
                        'uid' => 2,
                    )
                ),
            );
        }

        $irfaqCategoryService->expects(self::any())
            ->method('getByQuestion')
            ->with(tx_rnbase::makeInstance('tx_mksearch_model_irfaq_Question', $record))
            ->will(self::returnValue($categories));

        $indexer = $this->getMock('tx_mksearch_indexer_Irfaq', array('getIrfaqCategoryService', 'getIrfaqExpertService'));
        $indexer->expects(self::any())
            ->method('getIrfaqCategoryService')
            ->will(self::returnValue($irfaqCategoryService));
        $indexer->expects(self::any())
            ->method('getIrfaqExpertService')
            ->will(self::returnValue($irfaqExpertService));

        self::assertSame($isDeleted, $this->prepareSearchData($indexer, $record, 'tx_irfaq_q', $options)->getDeleted());
    }

    /**
     * @return array
     */
    public function getIncludeOptions()
    {
        return array(
            array(array('include.' => array('categories.' => array(0 => 1, 1 => 2))), false),
            array(array('include.' => array('categories' => '1,3')), false),
            array(array('include.' => array('categories.' => array(0 => 3))), true),
            array(array('include.' => array('categories' => '3')), true),
            array(array('include.' => array('pageTrees' => '2'), 'deleteIfNotIndexable' => 1), false, true),
            array(array('include.' => array('pageTrees' => '3'), 'deleteIfNotIndexable' => 1), true, true),
            array(array('include.' => array('pageTrees' => '2'), 'deleteIfNotIndexable' => 1), false),
            array(array('include.' => array('pageTrees' => '3'), 'deleteIfNotIndexable' => 1), true),
        );
    }

    /**
     * @param tx_mksearch_indexer_Irfaq $indexer
     * @param array                     $record
     * @param string                    $table
     * @param array                     $options
     *
     * @return tx_mksearch_interface_IndexerField[]
     */
    protected function prepareSearchData(
        tx_mksearch_indexer_Irfaq $indexer,
        array $record,
        $table = 'tx_irfaq_q',
        array $options = array()
    ) {
        list($extKey, $cType) = $indexer->getContentType();
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);

        return $indexer->prepareSearchData($table, $record, $indexDoc, $options);
    }
}
