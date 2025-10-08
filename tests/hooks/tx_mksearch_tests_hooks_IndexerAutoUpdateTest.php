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
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_hooks_IndexerAutoUpdateTest extends tx_mksearch_tests_Testcase
{
    protected function setUp(): void
    {
        tx_mksearch_tests_Util::storeExtConf();
        tx_mksearch_tests_Util::setExtConfVar('enableRnBaseUtilDbHook', 1);
    }

    protected function tearDown(): void
    {
        tx_mksearch_tests_Util::restoreExtConf();
    }

    /**
     * @unit
     */
    public function testProcessDatamapAfterAllOperationsWithWorkspace(): void
    {
        self::markTestIncomplete('A cache with identifier cache_runtime does not exist.');

        $hook = $this->getHookMock(
            $service = $this->getMockBuilder('tx_mksearch_service_internal_Index')->getMock()
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
    public function testProcessDatamapAfterAllOperationsWithEmptyDatamap(): void
    {
        self::markTestIncomplete('A cache with identifier cache_runtime does not exist.');

        $hook = $this->getHookMock(
            $service = $this->getMockBuilder('tx_mksearch_service_internal_Index')->getMock()
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
    public function testProcessDatamapAfterAllOperationsWithDataFromHistoryUndo(): void
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
    public function testProcessAutoUpdate(): void
    {
        self::markTestIncomplete('A cache with identifier cache_runtime does not exist.');

        $hook = $this->getHookMock(
            $service = $this->getMockBuilder('tx_mksearch_service_internal_Index')->getMock()
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
            ->willReturnMap(
                [
                    ['tt_content', 1, [1]],
                    ['tt_content', 2, [2]],
                    ['tt_content', 3, [3]],
                    ['tt_content', 5, [5]],
                    ['tt_content', 8, [8]],
                    ['tt_content', 13, [13]],
                ]
            );

        $service
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($indices);
        $service
            ->expects($this->once())
            ->method('isIndexerDefined')
            ->willReturn(true);
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
    public function testRnBaseDoInsertPost(): void
    {
        $hook = $this->getHookMock(
            $service = $this->getMockBuilder('tx_mksearch_service_internal_Index')->getMock()
        );

        $hookParams = ['tablename' => 'tt_content', 'uid' => 1];

        $hook
            ->expects($this->once())
            ->method('getUidsToIndex')
            ->willReturnMap(
                [
                    ['tt_content', 1, [1]],
                ]
            );

        $indices = [
            $this->getMockBuilder('tx_mksearch_model_internal_Index')
                ->setConstructorArgs([['uid' => 1]])
                ->getMock(),
        ];

        $service
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($indices);
        $service
            ->expects($this->once())
            ->method('isIndexerDefined')
            ->willReturn(true);
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
    public function testRnBaseDoUpdatePost(): void
    {
        $hook = $this->getHookMock(
            $service = $this->getMockBuilder('tx_mksearch_service_internal_Index')->getMock()
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
            ->willReturn(['1', '2']);

        $indices = [
            $this->getMockBuilder('tx_mksearch_model_internal_Index')
                ->setConstructorArgs([['uid' => 1]])
                ->getMock(),
        ];

        $service
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($indices);
        $service
            ->expects($this->once())
            ->method('isIndexerDefined')
            ->willReturn(true);
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
    public function testRnBaseDoDeletePre(): void
    {
        // use the same method as doUpdate
        $this->testRnBaseDoUpdatePost();
    }

    /**
     * @unit
     */
    public function testRnBaseDoInsertPostIfHookDeactivated(): void
    {
        tx_mksearch_tests_Util::setExtConfVar('enableRnBaseUtilDbHook', 0);

        $hook = $this->getHookMock(
            $service = $this->getMockBuilder('tx_mksearch_service_internal_Index')->getMock()
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
    public function testRnBaseDoUpdatePostIfHookDeactivated(): void
    {
        tx_mksearch_tests_Util::setExtConfVar('enableRnBaseUtilDbHook', 0);

        $hook = $this->getHookMock(
            $service = $this->getMockBuilder('tx_mksearch_service_internal_Index')->getMock()
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
    public function testGetRnBaseDatabaseUtility(): void
    {
        self::assertInstanceOf(
            Sys25\RnBase\Database\Connection::class,
            $this->getHookMock()->_call('getRnbaseDatabaseUtility')
        );
    }

    /**
     * @unit
     */
    public function testGetUidsToIndexIfDataIsNumeric(): void
    {
        $hook = $this->getAccessibleMock(
            'tx_mksearch_hooks_IndexerAutoUpdate',
            ['getRnbaseDatabaseUtility']
        );
        $hook->expects($this->never())->method('getRnbaseDatabaseUtility');

        self::assertEquals(
            [123],
            $hook->_call('getUidsToIndex', '', 123)
        );
    }

    /**
     * @unit
     */
    public function testGetUidsToIndexIfDataIsString(): void
    {
        $hook = $this->getAccessibleMock(
            'tx_mksearch_hooks_IndexerAutoUpdate',
            ['getRnbaseDatabaseUtility']
        );
        $hook->expects($this->never())->method('getRnbaseDatabaseUtility');

        self::assertEquals(
            [],
            $hook->_call('getUidsToIndex', '', 'testString')
        );
    }

    /**
     * @unit
     */
    public function testGetUidsToIndexIfDataIsArrayButHasNoTypeKey(): void
    {
        $hook = $this->getAccessibleMock(
            'tx_mksearch_hooks_IndexerAutoUpdate',
            ['getRnbaseDatabaseUtility']
        );
        $hook->expects($this->never())->method('getRnbaseDatabaseUtility');

        self::assertEquals(
            [],
            $hook->_call('getUidsToIndex', '', [])
        );
    }

    /**
     * @unit
     */
    public function testGetUidsToIndexIfDataIsArrayAndHasWrongTypeKey(): void
    {
        $hook = $this->getAccessibleMock(
            'tx_mksearch_hooks_IndexerAutoUpdate',
            ['getRnbaseDatabaseUtility']
        );
        $hook->expects($this->never())->method('getRnbaseDatabaseUtility');

        self::assertEquals(
            [],
            $hook->_call(
                'getUidsToIndex',
                '',
                ['type' => 'noSelect']
            )
        );
    }

    #[PHPUnit\Framework\Attributes\DataProvider('dataProviderGetUidsToIndex')]
    public function testGetUidsToIndexIfDataIsArrayAndHasSelectTypeKey(
        array $data,
        string $expectedFrom,
        array $expectedOptions,
        array $selectReturn,
        array $expectedReturn,
    ): void {
        $hook = $this->getAccessibleMock(
            'tx_mksearch_hooks_IndexerAutoUpdate',
            ['getRnbaseDatabaseUtility']
        );
        $databaseUtility = $this->getMockBuilder(Sys25\RnBase\Database\Connection::class)
            ->onlyMethods(['doSelect'])
            ->getMock();
        $databaseUtility->expects($this->once())
            ->method('doSelect')
            ->with('uid', $expectedFrom, $expectedOptions)
            ->willReturn($selectReturn);
        $hook->expects($this->once())
            ->method('getRnbaseDatabaseUtility')
            ->willReturn($databaseUtility);

        self::assertEquals(
            $expectedReturn,
            $hook->_call(
                'getUidsToIndex',
                'test_table',
                $data
            )
        );
    }

    /**
     * @return multitype:multitype:string multitype:string  multitype:number  multitype:multitype:number
     */
    public static function dataProviderGetUidsToIndex(): array
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
        $service = $service ?: $this->getMockBuilder('tx_mksearch_service_internal_Index')->getMock();

        $hook = $this->getAccessibleMock(
            'tx_mksearch_hooks_IndexerAutoUpdate',
            ['getIntIndexService', 'getIndexersForTable', 'getUidsToIndex']
        );

        $hook
            ->expects($this->any())
            ->method('getIntIndexService')
            ->willReturn($service);

        $indexers[] = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_TtContent');
        $hook
            ->expects($this->any())
            ->method('getIndexersForTable')
            ->willReturn($indexers);

        return $hook;
    }

    /**
     * @return TYPO3\CMS\Core\DataHandling\DataHandler
     */
    protected function getTceMock(): object
    {
        $tce = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Core\DataHandling\DataHandler::class);
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
