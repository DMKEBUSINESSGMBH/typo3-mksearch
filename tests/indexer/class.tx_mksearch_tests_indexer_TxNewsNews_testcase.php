<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2017 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Indexer for News from tx_news.
 *
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_indexer_TxNewsNews_testcase extends tx_mksearch_tests_Testcase
{
    /**
     * Set up testcase.
     */
    protected function setUp()
    {
        if (!tx_rnbase_util_Extensions::isLoaded('news')) {
            $this->markTestSkipped('tx_news is not installed!');
        }
        parent::setUp();
    }

    /**
     * Testet die indexData Methode.
     *
     *
     * @group unit
     * @test
     */
    public function testGetContentType()
    {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_TxNewsNews',
            array('getDefaultTSConfig')
        );

        $this->assertEquals(
            array('tx_news', 'news'),
            $indexer->getContentType()
        );
    }

    /**
     * Testet die indexData Methode.
     *
     *
     * @group unit
     * @test
     */
    public function testHandleCategoryChanged()
    {
        $connection = $this->getMock(
            'Tx_Rnbase_Database_Connection',
            array('doSelect')
        );
        ($connection
            ->expects(self::once())
            ->method('doSelect')
            ->with(
                $this->equalTo('NEWS.uid AS uid'),
                $this->equalTo(
                    array(
                        'tx_news_domain_model_news AS NEWS'.
                        ' JOIN sys_category_record_mm AS CATMM ON NEWS.uid = CATMM.uid_foreign',
                        'tx_news_domain_model_news',
                        'NEWS',
                    )
                ),
                $this->equalTo(
                    array(
                        'where' => 'CATMM.uid_local = 5',
                        'orderby' => 'sorting_foreign DESC',
                    )
                )
            )
            ->will(
                self::returnValue(
                    array(
                        array('uid' => '6'),
                        array('uid' => '8'),
                        array('uid' => '10'),
                    )
                )
            )
        );

        $indexSrv = $this->getMock(
            'tx_mksearch_service_internal_Index',
            array('addRecordToIndex')
        );
        ($indexSrv
            ->expects(self::exactly(3))
            ->method('addRecordToIndex')
            ->with(
                $this->equalTo('tx_news_domain_model_news'),
                $this->logicalOr(6, 8, 10)
            )
        );

        $indexer = $this->getMock(
            'tx_mksearch_indexer_TxNewsNews',
            array('getDatabaseConnection', 'getIntIndexService')
        );
        ($indexer
            ->expects(self::once())
            ->method('getDatabaseConnection')
            ->will(self::returnValue($connection))
        );
        ($indexer
            ->expects(self::once())
            ->method('getIntIndexService')
            ->will(self::returnValue($indexSrv))
        );

        $stopIndexing = $this->callInaccessibleMethod(
            array($indexer, 'stopIndexing'),
            array(
                'sys_category',
                array('uid' => '5'),
                $this->getIndexDocMock($indexer),
                array(),
            )
        );

        $this->assertTrue($stopIndexing);
    }

    /**
     * Testet die indexData Methode.
     *
     *
     * @group unit
     * @test
     */
    public function testIndexData()
    {
        $model = $this->getNewsModel();

        $indexer = $this->getIndexerMock(
            $model->getRecord()
        );

        $indexDoc = $this->callInaccessibleMethod(
            array($indexer, 'indexData'),
            array(
                $model,
                'tx_news_domain_model_news',
                $model->getRecord(),
                $this->getIndexDocMock($indexer),
                array(),
            )
        );

        $this->assertIndexDocHasFields(
            $indexDoc,
            array(
                // base indexer data
                'content_ident_s' => 'tx_news.news',
                'pid' => '7',
                'tstamp' => (string) $GLOBALS['EXEC_TIME'],
                // news text ata
                'title' => 'first news',
                'content' => 'the first news  html in body text  description',
                'abstract' => 'the first news',
                'news_text_s' => 'html in body text',
                'news_text_t' => 'html in body text',
                // dates
                'datetime_dt' => tx_mksearch_util_Misc::getIsoDate(
                    new \DateTime('@'.$GLOBALS['EXEC_TIME'])
                ),
                // tags
                'keywords_ms' => array('Tag1', 'Tag2'),
                // category data
                'categorySinglePid_i' => '14',
                'categories_mi' => array('71', '72', '73'),
                'categoriesTitle_ms' => array('Cat1', 'Cat2', 'Cat3'),
                'categories_dfs_ms' => array('71<[DFS]>Cat1', '72<[DFS]>Cat2', '73<[DFS]>Cat3'),
                'related_links_title_ms' => array('first link', 'second link'),
                'related_links_title_mt' => array('first link', 'second link'),
                'related_links_description_ms' => array('first link description', 'second link description'),
                'related_links_description_mt' => array('first link description', 'second link description'),
                'related_links_uri_ms' => array('www.example.com', 'example.com'),
            )
        );
    }

    /**
     * A model Mock containing news data.
     *
     * @return tx_rnbase_model_base|PHPUnit_Framework_MockObject_MockObject
     */
    protected function getNewsModel()
    {
        return $this->getModel(
            array(
                'uid' => '5',
                'pid' => '7',
                'tstamp' => $GLOBALS['EXEC_TIME'],
                'datetime' => new \DateTime('@'.$GLOBALS['EXEC_TIME']),
                'title' => 'first news',
                'teaser' => 'the first news',
                'bodytext' => '<span>html in body text</span>',
                'description' => 'description',
                'tags' => array(
                    $this->getModel(
                        array(
                            'uid' => '51',
                            'title' => 'Tag1',
                        )
                    ),
                    $this->getModel(
                        array(
                            'uid' => '52',
                            'title' => 'Tag2',
                        )
                    ),
                ),
                'categories' => array(
                    $this->getModel(
                        array(
                            'uid' => '71',
                            'title' => 'Cat1',
                            'single_pid' => '0',
                        )
                    ),
                    $this->getModel(
                        array(
                            'uid' => '72',
                            'title' => 'Cat2',
                            'single_pid' => '14',
                        )
                    ),
                    $this->getModel(
                        array(
                            'uid' => '73',
                            'title' => 'Cat3',
                            'single_pid' => '0',
                        )
                    ),
                ),
                'related_links' => array(
                    $this->getModel(
                        array(
                            'uri' => 'www.example.com',
                            'title' => 'first link',
                            'description' => 'first link description',
                        )
                    ),
                    $this->getModel(
                        array(
                            'uri' => 'example.com',
                            'title' => 'second link',
                            'description' => 'second link description',
                        )
                    ),
                ),
            )
        );
    }

    /**
     * Creates a Mock of the indexer object.
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function getIndexerMock(array $newsRecord)
    {
        $model = $this->getNewsModel();

        $indexer = $this->getMock(
            'tx_mksearch_indexer_TxNewsNews',
            array('createLocalizedExtbaseDomainModel')
        );

        ($indexer
            ->expects(self::once())
            ->method('createLocalizedExtbaseDomainModel')
            ->with(
                $newsRecord,
                'tx_news_domain_model_news',
                'GeorgRinger\\News\\Domain\\Repository\\NewsRepository'
            )
            ->will(self::returnValue($model))
        );

        return $indexer;
    }
}

if ((
    defined('TYPO3_MODE') &&
    $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TxNewsNews_testcase.php']
)) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TxNewsNews_testcase.php'];
}
