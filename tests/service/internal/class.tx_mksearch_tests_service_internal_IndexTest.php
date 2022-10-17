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
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_service_internal_IndexTest extends tx_mksearch_tests_Testcase
{
    protected function setUp(): void
    {
        tx_mksearch_tests_Util::storeExtConf();
        self::markTestIncomplete('Error: Call to a member function isConnected() on null');
        // @TODO: ther are TYPO3_DB operations. where? mock it!
        $this->prepareLegacyTypo3DbGlobal();
    }

    protected function tearDown(): void
    {
        tx_mksearch_tests_Util::restoreExtConf();

        tx_mksearch_util_Config::registerResolver(false, ['tx_mktest_table']);
    }

    /**
     * @group unit
     */
    public function testGetDatabaseUtility()
    {
        $indexService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_service_internal_Index'
        );

        self::assertInstanceOf(
            \Sys25\RnBase\Database\Connection::class,
            $this->callInaccessibleMethod(
                $indexService,
                'getDatabaseUtility'
            )
        );
    }

    /**
     * @group unit
     */
    public function testGetSecondsToKeepQueueEntries()
    {
        $indexService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_service_internal_Index'
        );

        self::assertEquals(
            604800,
            $this->callInaccessibleMethod(
                $indexService,
                'getSecondsToKeepQueueEntries'
            ),
            'Fallback falsch'
        );

        tx_mksearch_tests_Util::setExtConfVar('secondsToKeepQueueEntries', 123);

        self::assertEquals(
            123,
            $this->callInaccessibleMethod(
                $indexService,
                'getSecondsToKeepQueueEntries'
            ),
            'Konfiguration nicht korrekt ausgelsen'
        );
    }

    /**
     * @group unit
     */
    public function testDeleteOldQueueEntries()
    {
        $indexService = $this->getAccessibleMock(
            'tx_mksearch_service_internal_Index',
            ['getDatabaseUtility', 'getSecondsToKeepQueueEntries']
        );

        $indexService->expects($this->once())
            ->method('getSecondsToKeepQueueEntries')
            ->will($this->returnValue(123));

        $databaseUtility = $this->getMock(
            \Sys25\RnBase\Database\Connection::class,
            ['doDelete']
        );
        $databaseUtility->expects($this->once())
            ->method('doDelete')
            ->with('tx_mksearch_queue', 'deleted = 1 AND cr_date < NOW() - 123');

        $indexService->expects($this->once())
            ->method('getDatabaseUtility')
            ->will($this->returnValue($databaseUtility));

        $indexService->_call('deleteOldQueueEntries');
    }

    /**
     * test for tx_mksearch_service_internal_Index::addModelsToIndex.
     *
     * @group unit
     *
     * @test
     */
    public function testAddModelsToIndex()
    {
        $model = $this->getModel(['uid' => '5']);
        $options = [];

        /* @var $srv tx_mksearch_service_internal_Index */
        $srv = $this->getMock(
            'tx_mksearch_service_internal_Index',
            ['addRecordToIndex']
        );

        $srv
            ->expects(self::once())
            ->method('addRecordToIndex')
            ->with(
                $model->getTableName(),
                $model->getUid(),
                false,
                false,
                false,
                $options
            );

        $srv->addModelsToIndex(
            [$model],
            $options
        );
    }

    /**
     * @group unit
     *
     * @dataProvider dataProviderDeleteDocumentIfNotCorrectWorkspaceTest
     */
    public function testDeleteDocumentIfNotCorrectWorkspace(
        array $configuration,
        array $record,
        $isDeleted
    ) {
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', 'mksearch', 'test');

        $this->callInaccessibleMethod(
            tx_mksearch_util_ServiceRegistry::getIntIndexService(),
            'deleteDocumentIfNotCorrectWorkspace',
            $configuration,
            $record,
            $indexDoc
        );

        self::assertSame($isDeleted, $indexDoc->getDeleted());
    }

    /**
     * @return array
     */
    public function dataProviderDeleteDocumentIfNotCorrectWorkspaceTest()
    {
        return [
            [[], ['t3ver_wsid' => 0], false],
            [[], ['t3ver_wsid' => 1], true],
            [['workspaceIds' => '1,2,3'], ['t3ver_wsid' => 3], false],
            [['workspaceIds' => '1,2,3'], ['t3ver_wsid' => 0], true],
        ];
    }

    /**
     * @group unit
     */
    public function testGetDatabaseConnection()
    {
        self::assertInstanceOf(
            \Sys25\RnBase\Database\Connection::class,
            $this->callInaccessibleMethod(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_internal_Index'), 'getDatabaseConnection')
        );
    }

    /**
     * @group unit
     */
    public function testAddRecordToIndex()
    {
        $databaseConnection = $this->getMock(\Sys25\RnBase\Database\Connection::class, ['doInsert']);
        $databaseConnection->expects(self::once())
            ->method('doInsert')
            ->with(
                'tx_mksearch_queue',
                [
                    'cr_date' => \Sys25\RnBase\Utility\Dates::datetime_tstamp2mysql($GLOBALS['EXEC_TIME']),
                    'prefer' => 0,
                    'recid' => 50,
                    'tablename' => 'tx_mktest_table',
                    'data' => '',
                    'resolver' => '',
                ]
            )
            ->will(self::returnValue(123));

        $service = $this->getMock('tx_mksearch_service_internal_Index', ['getDatabaseConnection']);
        $service->expects(self::once())
            ->method('getDatabaseConnection')
            ->will(self::returnValue($databaseConnection));

        self::assertTrue($service->addRecordToIndex('tx_mktest_table', 50));
    }

    /**
     * @group unit
     */
    public function testAddRecordToIndexWithRegisteredResolver()
    {
        tx_mksearch_util_Config::registerResolver('tx_mksearch_resolver_Test', ['tx_mktest_table']);

        $databaseConnection = $this->getMock(\Sys25\RnBase\Database\Connection::class, ['doInsert']);
        $databaseConnection->expects(self::once())
            ->method('doInsert')
            ->with(
                'tx_mksearch_queue',
                [
                        'cr_date' => \Sys25\RnBase\Utility\Dates::datetime_tstamp2mysql($GLOBALS['EXEC_TIME']),
                        'prefer' => 0,
                        'recid' => 50,
                        'tablename' => 'tx_mktest_table',
                        'data' => '',
                        'resolver' => 'tx_mksearch_resolver_Test',
                ]
            )
            ->will(self::returnValue(123));

        $service = $this->getMock('tx_mksearch_service_internal_Index', ['getDatabaseConnection']);
        $service->expects(self::once())
            ->method('getDatabaseConnection')
            ->will(self::returnValue($databaseConnection));

        self::assertTrue($service->addRecordToIndex('tx_mktest_table', 50));
    }

    /**
     * @group unit
     */
    public function testDoInsertRecords()
    {
        $databaseConnection = $this->getMock(\Sys25\RnBase\Database\Connection::class, ['doQuery']);
        $databaseConnection->expects(self::once())
            ->method('doQuery')
            ->with(
                "INSERT INTO tx_mksearch_queue(cr_date,prefer,recid,tablename,data,resolver) VALUES \r\nvalue1, \r\nvalue2;"
            );

        $service = $this->getMock('tx_mksearch_service_internal_Index', ['getDatabaseConnection']);
        $service->expects(self::once())
            ->method('getDatabaseConnection')
            ->will(self::returnValue($databaseConnection));

        $this->callInaccessibleMethod($service, 'doInsertRecords', ['value1', 'value2']);
    }

    /**
     * @group unit
     */
    public function testDoInsertRecordsWhenNoSqlValuesGiven()
    {
        $service = $this->getMock('tx_mksearch_service_internal_Index', ['getDatabaseConnection']);
        $service->expects(self::never())
            ->method('getDatabaseConnection');

        self::assertTrue($this->callInaccessibleMethod($service, 'doInsertRecords', []));
    }

    /**
     * @group unit
     */
    public function testAddRecordsToIndex()
    {
        $execTimeFormatted = \Sys25\RnBase\Utility\Dates::datetime_tstamp2mysql($GLOBALS['EXEC_TIME']);
        $service = $this->getMock('tx_mksearch_service_internal_Index', ['doInsertRecords']);
        $service->expects(self::once())
            ->method('doInsertRecords')
            ->with([
                0 => "('$execTimeFormatted','0','50','tx_mktest_table','','')",
                1 => "('$execTimeFormatted','1','51','tx_mktest_table','','')",
                2 => "('$execTimeFormatted','1','52','tx_mktest_table','','tx_mksearch_resolver_Test')",
                3 => "('$execTimeFormatted','1','53','tx_mktest_table','test index','tx_mksearch_resolver_Test')",
            ]);

        $records = [
            ['tablename' => 'tx_mktest_table', 'uid' => '50'],
            ['tablename' => 'tx_mktest_table', 'uid' => '51', 'preferer' => '1'],
            ['tablename' => 'tx_mktest_table', 'uid' => '52', 'preferer' => '1', 'resolver' => 'tx_mksearch_resolver_Test'],
            ['tablename' => 'tx_mktest_table', 'uid' => '53', 'preferer' => '1', 'resolver' => 'tx_mksearch_resolver_Test', 'data' => 'test index'],
        ];
        $options = ['checkExisting' => false];
        $service->addRecordsToIndex($records, $options);
    }

    /**
     * @group unit
     */
    public function testAddRecordsToIndexWithMoreThan500Records()
    {
        $execTimeFormatted = \Sys25\RnBase\Utility\Dates::datetime_tstamp2mysql($GLOBALS['EXEC_TIME']);
        for ($i = 1; $i <= 501; ++$i) {
            $records[] = ['tablename' => 'tx_mktest_table', 'uid' => $i];
        }
        foreach ($records as $record) {
            $expectedRecordsToInsert[] = "('$execTimeFormatted','0','".$record['uid']."','".$record['tablename']."','','')";
        }

        $service = $this->getMock('tx_mksearch_service_internal_Index', ['doInsertRecords']);
        $service->expects(self::at(0))
            ->method('doInsertRecords')
            ->with(array_slice($expectedRecordsToInsert, 0, 500));
        $service->expects(self::at(1))
            ->method('doInsertRecords')
            ->with(array_slice($expectedRecordsToInsert, 500));

        $options = ['checkExisting' => false];
        $service->addRecordsToIndex($records, $options);
    }

    /**
     * @group unit
     */
    public function testAddRecordsToIndexWhenNoRecordsGiven()
    {
        $service = $this->getMock('tx_mksearch_service_internal_Index', ['doInsertRecords']);
        $service->expects(self::never())
            ->method('doInsertRecords');

        self::assertTrue($service->addRecordsToIndex([]));
    }
}
