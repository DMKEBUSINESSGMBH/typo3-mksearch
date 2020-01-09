<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011-2014 DMK E-Business GmbH <dev@dmk-ebusiness.de>
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
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_hooks_IndexerAutoUpdateTest extends tx_mksearch_tests_Testcase
{
    /**
     * (non-PHPdoc).
     *
     * @see tx_mksearch_tests_Testcase::setUp()
     */
    protected function setUp()
    {
        tx_mksearch_tests_Util::storeExtConf();
        tx_mksearch_tests_Util::setExtConfVar('enableRnBaseUtilDbHook', 1);
    }

    /**
     * (non-PHPdoc).
     *
     * @see tx_mksearch_tests_Testcase::tearDown()
     */
    protected function tearDown()
    {
        tx_mksearch_tests_Util::restoreExtConf();
    }

    /**
     * @unit
     */
    public function testProcessDatamapAfterAllOperationsWithWorkspace()
    {
        self::markTestIncomplete('A cache with identifier cache_runtime does not exist.');

        $hook = $this->getHookMock(
            $service = $this->getMock('tx_mksearch_service_internal_Index')
        );
        $tce = $this->getTceMock();
        $tce->BE_USER->workspace = 1;
        $tce->datamap = ['123' => []];

        $service
            ->expects($this->once())
            ->method('findAll');

        $hook->processDatamap_afterAllOperations($tce);
    }

    /**
     * @unit
     */
    public function testProcessDatamapAfterAllOperationsWithEmptyDatamap()
    {
        self::markTestIncomplete('A cache with identifier cache_runtime does not exist.');

        $hook = $this->getHookMock(
            $service = $this->getMock('tx_mksearch_service_internal_Index')
        );
        $tce = $this->getTceMock();
        $tce->datamap = [];

        $service
            ->expects($this->never())
            ->method('findAll');

        $hook->processDatamap_afterAllOperations($tce);
    }

    /**
     * @unit
     */
    public function testProcessDatamapAfterAllOperationsWithDataFromHistoryUndo()
    {
        self::markTestIncomplete('A cache with identifier cache_runtime does not exist.');

        $hook = $this->getMock('tx_mksearch_hooks_IndexerAutoUpdate', ['processAutoUpdate']);

        $hook
            ->expects(self::once())
            ->method('processAutoUpdate')
            ->with(['tt_content' => [123]]);

        $tce = $this->getTceMock();
        $tce->datamap = ['tt_content:123' => -1, 'tt_content' => [123 => []]];

        $hook->processDatamap_afterAllOperations($tce);
    }

    /**
     * @unit
     */
    public function testProcessAutoUpdate()
    {
        self::markTestIncomplete('A cache with identifier cache_runtime does not exist.');

        $hook = $this->getHookMock(
            $service = $this->getMock('tx_mksearch_service_internal_Index')
        );
        $tce = $this->getTceMock();

        $indices = [
            $this->getMock(
                'tx_mksearch_model_internal_Index',
                null,
                [
                    ['uid' => 1],
                ]
            ),
        ];

        $hook
            ->expects($this->exactly(count($tce->datamap['tt_content'])))
            ->method('getUidsToIndex')
            ->will($this->returnValueMap(
                [
                    ['tt_content', 1, [1]],
                    ['tt_content', 2, [2]],
                    ['tt_content', 3, [3]],
                    ['tt_content', 5, [5]],
                    ['tt_content', 8, [8]],
                    ['tt_content', 13, [13]],
                ]
            ));

        $service
            ->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue($indices));
        $service
            ->expects($this->once())
            ->method('isIndexerDefined')
            ->will($this->returnValue(true));
        $service
            ->expects($this->exactly(count($tce->datamap['tt_content'])))
            ->method('addRecordToIndex');

        $this->callInaccessibleMethod(
            $hook,
            'processAutoUpdate',
            ['tt_content' => array_keys($tce->datamap['tt_content'])]
        );
    }

    /**
     * @unit
     */
    public function testRnBaseDoInsertPost()
    {
        self::markTestIncomplete('Uncaught require(typo3-mksearch/.Build/Web/typo3conf/LocalConfiguration.php)');

        $hook = $this->getHookMock(
            $service = $this->getMock('tx_mksearch_service_internal_Index')
        );

        $hookParams = ['tablename' => 'tt_content', 'uid' => 1];

        $hook
            ->expects($this->once())
            ->method('getUidsToIndex')
            ->will($this->returnValueMap(
                [
                    ['tt_content', 1, [1]],
                ]
            ));

        $indices = [
            $this->getMock(
                'tx_mksearch_model_internal_Index',
                null,
                [
                    ['uid' => 1],
                ]
            ),
        ];

        $service
            ->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue($indices));
        $service
            ->expects($this->once())
            ->method('isIndexerDefined')
            ->will($this->returnValue(true));
        $service
            ->expects($this->once())
            ->method('addRecordToIndex')
            ->with(
                $this->equalTo($hookParams['tablename']),
                $this->equalTo($hookParams['uid'])
            );

        $hook->rnBaseDoInsertPost($hookParams);
    }

    /**
     * @unit
     */
    public function testRnBaseDoUpdatePost()
    {
        self::markTestIncomplete('Uncaught require(typo3-mksearch/.Build/Web/typo3conf/LocalConfiguration.php)');

        $hook = $this->getHookMock(
            $service = $this->getMock('tx_mksearch_service_internal_Index')
        );

        $hookParams = [
            'tablename' => 'tt_content',
            'where' => 'deleted = 0',
            'values' => ['hidden' => '1'],
            'options' => [],
        ];

        $hook
            ->expects($this->once())
            ->method('getUidsToIndex')
            ->with(
                $this->equalTo(
                    $hookParams['tablename']
                ),
                $this->equalTo(
                    [
                        'type' => 'select',
                        'from' => $hookParams['tablename'],
                        'where' => $hookParams['where'],
                        'options' => $hookParams['options'],
                    ]
                )
            )
            ->will($this->returnValue(['1', '2']));

        $indices = [
            $this->getMock(
                'tx_mksearch_model_internal_Index',
                null,
                [
                    ['uid' => 1],
                ]
            ),
        ];

        $service
            ->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue($indices));
        $service
            ->expects($this->once())
            ->method('isIndexerDefined')
            ->will($this->returnValue(true));
        $service
            ->expects($this->exactly(2))
            ->method('addRecordToIndex')
            ->with(
                $this->equalTo($hookParams['tablename']),
                $this->logicalOr($this->equalTo(1), $this->equalTo(2))
            );

        $hook->rnBaseDoUpdatePost($hookParams);
    }

    /**
     * @unit
     */
    public function testRnBaseDoDeletePre()
    {
        // use the same method as doUpdate
        $this->testRnBaseDoUpdatePost();
    }

    /**
     * @unit
     */
    public function testRnBaseDoInsertPostIfHookDeactivated()
    {
        self::markTestIncomplete('Uncaught require(typo3-mksearch/.Build/Web/typo3conf/LocalConfiguration.php)');

        tx_mksearch_tests_Util::setExtConfVar('enableRnBaseUtilDbHook', 0);

        $hook = $this->getHookMock(
            $service = $this->getMock('tx_mksearch_service_internal_Index')
        );

        $hookParams = ['tablename' => 'tt_content', 'uid' => 1];

        $hook
            ->expects($this->never())
            ->method('getUidsToIndex');

        $service
            ->expects($this->never())
            ->method('findAll');

        $service
            ->expects($this->never())
            ->method('isIndexerDefined');

        $service
            ->expects($this->never())
            ->method('addRecordToIndex');

        $hook->rnBaseDoInsertPost($hookParams);
    }

    /**
     * @unit
     */
    public function testRnBaseDoUpdatePostIfHookDeactivated()
    {
        self::markTestIncomplete('Uncaught require(typo3-mksearch/.Build/Web/typo3conf/LocalConfiguration.php)');

        tx_mksearch_tests_Util::setExtConfVar('enableRnBaseUtilDbHook', 0);

        $hook = $this->getHookMock(
            $service = $this->getMock('tx_mksearch_service_internal_Index')
        );

        $hookParams = [
            'tablename' => 'tt_content',
            'where' => 'deleted = 0',
            'values' => ['hidden' => '1'],
            'options' => [],
        ];

        $hook
            ->expects($this->never())
            ->method('getUidsToIndex');

        $service
            ->expects($this->never())
            ->method('findAll');
        $service
            ->expects($this->never())
            ->method('isIndexerDefined');
        $service
            ->expects($this->never())
            ->method('addRecordToIndex');

        $hook->rnBaseDoUpdatePost($hookParams);
    }

    /**
     * @unit
     */
    public function testGetRnBaseDatabaseUtility()
    {
        self::assertInstanceOf(
            'Tx_Rnbase_Database_Connection',
            $this->callInaccessibleMethod($this->getHookMock(), 'getRnbaseDatabaseUtility')
        );
    }

    /**
     * @unit
     */
    public function testGetUidsToIndexIfDataIsNumeric()
    {
        $hook = $this->getMock(
            'tx_mksearch_hooks_IndexerAutoUpdate',
            ['getRnbaseDatabaseUtility']
        );
        $hook->expects($this->never())->method('getRnbaseDatabaseUtility');

        self::assertEquals(
            [123],
            $this->callInaccessibleMethod($hook, 'getUidsToIndex', '', 123)
        );
    }

    /**
     * @unit
     */
    public function testGetUidsToIndexIfDataIsString()
    {
        $hook = $this->getMock(
            'tx_mksearch_hooks_IndexerAutoUpdate',
            ['getRnbaseDatabaseUtility']
        );
        $hook->expects($this->never())->method('getRnbaseDatabaseUtility');

        self::assertEquals(
            [],
            $this->callInaccessibleMethod($hook, 'getUidsToIndex', '', 'testString')
        );
    }

    /**
     * @unit
     */
    public function testGetUidsToIndexIfDataIsArrayButHasNoTypeKey()
    {
        $hook = $this->getMock(
            'tx_mksearch_hooks_IndexerAutoUpdate',
            ['getRnbaseDatabaseUtility']
        );
        $hook->expects($this->never())->method('getRnbaseDatabaseUtility');

        self::assertEquals(
            [],
            $this->callInaccessibleMethod($hook, 'getUidsToIndex', '', [])
        );
    }

    /**
     * @unit
     */
    public function testGetUidsToIndexIfDataIsArrayAndHasWrongTypeKey()
    {
        $hook = $this->getMock(
            'tx_mksearch_hooks_IndexerAutoUpdate',
            ['getRnbaseDatabaseUtility']
        );
        $hook->expects($this->never())->method('getRnbaseDatabaseUtility');

        self::assertEquals(
            [],
            $this->callInaccessibleMethod(
                $hook,
                'getUidsToIndex',
                '',
                ['type' => 'noSelect']
            )
        );
    }

    /**
     * @unit
     * @dataProvider dataProviderGetUidsToIndex
     */
    public function testGetUidsToIndexIfDataIsArrayAndHasSelectTypeKey(
        $data,
        $expectedFrom,
        $expectedOptions,
        $selectReturn,
        $expectedReturn
    ) {
        $hook = $this->getMock(
            'tx_mksearch_hooks_IndexerAutoUpdate',
            ['getRnbaseDatabaseUtility']
        );
        $databaseUtility = $this->getMock(
            'Tx_Rnbase_Database_Connection',
            ['doSelect']
        );
        $databaseUtility->expects($this->once())
            ->method('doSelect')
            ->with('uid', $expectedFrom, $expectedOptions)
            ->will($this->returnValue($selectReturn));
        $hook->expects($this->once())
            ->method('getRnbaseDatabaseUtility')
            ->will($this->returnValue($databaseUtility));

        self::assertEquals(
            $expectedReturn,
            $this->callInaccessibleMethod(
                $hook,
                'getUidsToIndex',
                'test_table',
                $data
            )
        );
    }

    /**
     * @return multitype:multitype:string multitype:string  multitype:number  multitype:multitype:number
     */
    public function dataProviderGetUidsToIndex()
    {
        return [
            // from aus übergebenem data array
            [
                ['type' => 'select', 'from' => 'another_test_table'],
                'another_test_table',
                ['enablefieldsoff' => true, 'where' => ''],
                [123 => ['uid' => 123]],
                [123],
            ],
            // from aus übergebener tabelle
            [
                ['type' => 'select'],
                'test_table',
                ['enablefieldsoff' => true, 'where' => ''],
                [123 => ['uid' => 123]],
                [123],
            ],
            // options aus übergebenem data array
            [
                ['type' => 'select', 'options' => ['debug' => true]],
                'test_table',
                ['enablefieldsoff' => true, 'where' => '', 'debug' => true],
                [123 => ['uid' => 123]],
                [123],
            ],
            // where aus options hat vorrang
            [
                ['type' => 'select', 'options' => ['where' => 'where clause'], 'where' => 'clause'],
                'test_table',
                ['enablefieldsoff' => true, 'where' => 'where clause'],
                [123 => ['uid' => 123]],
                [123],
            ],
            // where aus data
            [
                ['type' => 'select', 'where' => 'clause'],
                'test_table',
                ['enablefieldsoff' => true, 'where' => 'clause'],
                [123 => ['uid' => 123]],
                [123],
            ],
            // doSelect liefert kein Ergebnis
            [
                ['type' => 'select'],
                'test_table',
                ['enablefieldsoff' => true, 'where' => ''],
                [],
                [],
            ],
            // kein uid key im select array result
            [
                ['type' => 'select'],
                'test_table',
                ['enablefieldsoff' => true, 'where' => ''],
                [123 => ['pid' => 123]],
                [],
            ],
            // mehr als ein ergebnis
            [
                ['type' => 'select'],
                'test_table',
                ['enablefieldsoff' => true, 'where' => ''],
                [123 => ['uid' => 123], 456 => ['uid' => 456]],
                [123, 456],
            ],
        ];
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|tx_mksearch_hooks_IndexerAutoUpdate;
     */
    protected function getHookMock($service = null)
    {
        $service = $service ? $service : $this->getMock('tx_mksearch_service_internal_Index');

        $hook = $this->getMock(
            'tx_mksearch_hooks_IndexerAutoUpdate',
            ['getIntIndexService', 'getIndexersForTable', 'getUidsToIndex']
        );

        $hook
            ->expects($this->any())
            ->method('getIntIndexService')
            ->will($this->returnValue($service));

        $indexers[] = tx_rnbase::makeInstance('tx_mksearch_indexer_TtContent');
        $hook
            ->expects($this->any())
            ->method('getIndexersForTable')
            ->will($this->returnValue($indexers));

        return $hook;
    }

    /**
     * @return \TYPO3\CMS\Core\DataHandling\DataHandler
     */
    protected function getTceMock()
    {
        $tce = tx_rnbase::makeInstance(tx_rnbase_util_Typo3Classes::getDataHandlerClass());
        // default datamap
        $tce->datamap = [
            'tt_content' => [
                '1' => ['uid' => '1'],
                '2' => ['uid' => '2'],
                '3' => ['uid' => '3'],
                '5' => ['uid' => '5'],
                '8' => ['uid' => '8'],
                '13' => ['uid' => '13'],
            ],
        ];
        // default workspace
        $tce->BE_USER = new stdClass();
        $tce->BE_USER->workspace = 0;

        return $tce;
    }
}
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/hooks/class.tx_mksearch_tests_hooks_IndexerAutoUpdateTest.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/hooks/class.tx_mksearch_tests_hooks_IndexerAutoUpdateTest.php'];
}
