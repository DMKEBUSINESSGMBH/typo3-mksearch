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
class tx_mksearch_tests_indexer_TxNewsNewsTest extends tx_mksearch_tests_Testcase
{
    protected function setUp(): void
    {
        if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('news')) {
            $this->markTestSkipped('tx_news is not installed!');
        }
        parent::setUp();
    }

    /**
     * Testet die indexData Methode.
     *
     * @group unit
     *
     * @test
     */
    public function testGetContentType()
    {
        $indexer = $this->getMock(
            'tx_mksearch_indexer_TxNewsNews',
            ['getDefaultTSConfig']
        );

        $this->assertEquals(
            ['tx_news', 'news'],
            $indexer->getContentType()
        );
    }

    /**
     * Testet die indexData Methode.
     *
     * @group unit
     *
     * @test
     */
    public function testHandleCategoryChanged()
    {
        $connection = $this->getMock(
            \Sys25\RnBase\Database\Connection::class,
            ['doSelect']
        );
        $connection
            ->expects(self::once())
            ->method('doSelect')
            ->with(
                $this->equalTo('NEWS.uid AS uid'),
                $this->equalTo(
                    [
                        'tx_news_domain_model_news AS NEWS'.
                        ' JOIN sys_category_record_mm AS CATMM ON NEWS.uid = CATMM.uid_foreign',
                        'tx_news_domain_model_news',
                        'NEWS',
                    ]
                ),
                $this->equalTo(
                    [
                        'where' => 'CATMM.tablenames = "tx_news_domain_model_news" AND (CATMM.uid_local = 5)',
                        'orderby' => 'sorting_foreign DESC',
                    ]
                )
            )
            ->will(
                self::returnValue(
                    [
                        ['uid' => '6'],
                        ['uid' => '8'],
                        ['uid' => '10'],
                    ]
                )
            )
        ;

        $indexSrv = $this->getMock(
            'tx_mksearch_service_internal_Index',
            ['addRecordToIndex']
        );
        $indexSrv
            ->expects(self::exactly(3))
            ->method('addRecordToIndex')
            ->with(
                $this->equalTo('tx_news_domain_model_news'),
                $this->logicalOr(6, 8, 10)
            )
        ;

        $indexer = $this->getMock(
            'tx_mksearch_indexer_TxNewsNews',
            ['getDatabaseConnection', 'getIntIndexService']
        );
        $indexer
            ->expects(self::once())
            ->method('getDatabaseConnection')
            ->will(self::returnValue($connection))
        ;
        $indexer
            ->expects(self::once())
            ->method('getIntIndexService')
            ->will(self::returnValue($indexSrv))
        ;

        $stopIndexing = $this->callInaccessibleMethod(
            [$indexer, 'stopIndexing'],
            [
                'sys_category',
                ['uid' => '5'],
                $this->getIndexDocMock($indexer),
                [],
            ]
        );

        $this->assertTrue($stopIndexing);
    }

    /**
     * Testet die indexData Methode.
     *
     * @group unit
     *
     * @test
     */
    public function testIndexData()
    {
        $model = $this->getNewsModel();

        $indexer = $this->getIndexerMock(
            $model->getRecord()
        );

        $indexDoc = $this->callInaccessibleMethod(
            [$indexer, 'indexData'],
            [
                $model,
                'tx_news_domain_model_news',
                $model->getRecord(),
                $this->getIndexDocMock($indexer),
                [],
            ]
        );

        $this->assertIndexDocHasFields(
            $indexDoc,
            [
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
                'datetime_dt' => '2023-02-14T16:15:00Z',
                // tags
                'keywords_ms' => ['Tag1', 'Tag2'],
                // category data
                'categorySinglePid_i' => '14',
                'categories_mi' => ['71', '72', '73'],
                'categoriesTitle_ms' => ['Cat1', 'Cat2', 'Cat3'],
                'categories_dfs_ms' => ['71<[DFS]>Cat1', '72<[DFS]>Cat2', '73<[DFS]>Cat3'],
                'related_links_title_ms' => ['first link', 'second link'],
                'related_links_title_mt' => ['first link', 'second link'],
                'related_links_description_ms' => ['first link description', 'second link description'],
                'related_links_description_mt' => ['first link description', 'second link description'],
                'related_links_uri_ms' => ['www.example.com', 'example.com'],
            ]
        );
    }

    /**
     * A model Mock containing news data.
     *
     * @return \Sys25\RnBase\Domain\Model\BaseModel|PHPUnit_Framework_MockObject_MockObject
     */
    protected function getNewsModel()
    {
        return $this->getModel(
            [
                'uid' => '5',
                'pid' => '7',
                'tstamp' => $GLOBALS['EXEC_TIME'],
                'datetime' => new \DateTime('2023-02-14 17:15:00'),
                'title' => 'first news',
                'teaser' => 'the first news',
                'bodytext' => '<span>html in body text</span>',
                'description' => 'description',
                'tags' => [
                    $this->getModel(
                        [
                            'uid' => '51',
                            'title' => 'Tag1',
                        ]
                    ),
                    $this->getModel(
                        [
                            'uid' => '52',
                            'title' => 'Tag2',
                        ]
                    ),
                ],
                'categories' => [
                    $this->getModel(
                        [
                            'uid' => '71',
                            'title' => 'Cat1',
                            'single_pid' => '0',
                        ]
                    ),
                    $this->getModel(
                        [
                            'uid' => '72',
                            'title' => 'Cat2',
                            'single_pid' => '14',
                        ]
                    ),
                    $this->getModel(
                        [
                            'uid' => '73',
                            'title' => 'Cat3',
                            'single_pid' => '0',
                        ]
                    ),
                ],
                'related_links' => [
                    $this->getModel(
                        [
                            'uri' => 'www.example.com',
                            'title' => 'first link',
                            'description' => 'first link description',
                        ]
                    ),
                    $this->getModel(
                        [
                            'uri' => 'example.com',
                            'title' => 'second link',
                            'description' => 'second link description',
                        ]
                    ),
                ],
            ]
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
            ['createLocalizedExtbaseDomainModel']
        );

        $indexer
            ->expects(self::once())
            ->method('createLocalizedExtbaseDomainModel')
            ->with(
                $newsRecord,
                'tx_news_domain_model_news',
                'GeorgRinger\\News\\Domain\\Repository\\NewsRepository'
            )
            ->will(self::returnValue($model))
        ;

        return $indexer;
    }
}
