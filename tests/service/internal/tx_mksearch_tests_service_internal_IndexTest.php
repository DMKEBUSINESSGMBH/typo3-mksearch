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

    public function testGetDatabaseUtility(): void
    {
        $indexService = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_service_internal_Index'
        );

        self::assertInstanceOf(
            Sys25\RnBase\Database\Connection::class,
            $this->callInaccessibleMethod(
                $indexService,
                'getDatabaseUtility'
            )
        );
    }

    public function testGetSecondsToKeepQueueEntries(): void
    {
        $indexService = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
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

    public function testDeleteOldQueueEntries(): void
    {
        $indexService = $this->getAccessibleMock(
            'tx_mksearch_service_internal_Index',
            ['getDatabaseUtility', 'getSecondsToKeepQueueEntries']
        );

        $indexService->expects($this->once())
            ->method('getSecondsToKeepQueueEntries')
            ->willReturn(123);

        $databaseUtility = $this->getMock(
            Sys25\RnBase\Database\Connection::class,
            ['doDelete']
        );
        $databaseUtility->expects($this->once())
            ->method('doDelete')
            ->with('tx_mksearch_queue', 'deleted = 1 AND cr_date < NOW() - 123');

        $indexService->expects($this->once())
            ->method('getDatabaseUtility')
            ->willReturn($databaseUtility);

        $indexService->_call('deleteOldQueueEntries');
    }

    public function testAddModelsToIndex(): void
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

    #[PHPUnit\Framework\Attributes\DataProvider('dataProviderDeleteDocumentIfNotCorrectWorkspaceTest')]
    public function testDeleteDocumentIfNotCorrectWorkspace(
        array $configuration,
        array $record,
        bool $isDeleted,
    ): void {
        $indexDoc = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', 'mksearch', 'test');

        $this->callInaccessibleMethod(
            tx_mksearch_util_ServiceRegistry::getIntIndexService(),
            'deleteDocumentIfNotCorrectWorkspace',
            $configuration,
            $record,
            $indexDoc
        );

        self::assertSame($isDeleted, $indexDoc->getDeleted());
    }

    public static function dataProviderDeleteDocumentIfNotCorrectWorkspaceTest(): array
    {
        return [
            [[], ['t3ver_wsid' => 0], false],
            [[], ['t3ver_wsid' => 1], true],
            [['workspaceIds' => '1,2,3'], ['t3ver_wsid' => 3], false],
            [['workspaceIds' => '1,2,3'], ['t3ver_wsid' => 0], true],
        ];
    }

    public function testGetDatabaseConnection(): void
    {
        self::assertInstanceOf(
            Sys25\RnBase\Database\Connection::class,
            $this->callInaccessibleMethod(TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_internal_Index'), 'getDatabaseConnection')
        );
    }

    public function testAddRecordToIndex(): void
    {
        $databaseConnection = $this->getMock(Sys25\RnBase\Database\Connection::class, ['doInsert']);
        $databaseConnection->expects(self::once())
            ->method('doInsert')
            ->with(
                'tx_mksearch_queue',
                [
                    'cr_date' => Sys25\RnBase\Utility\Dates::datetime_tstamp2mysql(TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Core\Context\Context::class)->getPropertyFromAspect('date', 'timestamp')),
                    'prefer' => 0,
                    'recid' => 50,
                    'tablename' => 'tx_mktest_table',
                    'data' => '',
                    'resolver' => '',
                ]
            )
            ->willReturn(123);

        $service = $this->getMock('tx_mksearch_service_internal_Index', ['getDatabaseConnection']);
        $service->expects(self::once())
            ->method('getDatabaseConnection')
            ->willReturn($databaseConnection);

        self::assertTrue($service->addRecordToIndex('tx_mktest_table', 50));
    }

    public function testAddRecordToIndexWithRegisteredResolver(): void
    {
        tx_mksearch_util_Config::registerResolver('tx_mksearch_resolver_Test', ['tx_mktest_table']);

        $databaseConnection = $this->getMock(Sys25\RnBase\Database\Connection::class, ['doInsert']);
        $databaseConnection->expects(self::once())
            ->method('doInsert')
            ->with(
                'tx_mksearch_queue',
                [
                    'cr_date' => Sys25\RnBase\Utility\Dates::datetime_tstamp2mysql(TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Core\Context\Context::class)->getPropertyFromAspect('date', 'timestamp')),
                    'prefer' => 0,
                    'recid' => 50,
                    'tablename' => 'tx_mktest_table',
                    'data' => '',
                    'resolver' => 'tx_mksearch_resolver_Test',
                ]
            )
            ->willReturn(123);

        $service = $this->getMock('tx_mksearch_service_internal_Index', ['getDatabaseConnection']);
        $service->expects(self::once())
            ->method('getDatabaseConnection')
            ->willReturn($databaseConnection);

        self::assertTrue($service->addRecordToIndex('tx_mktest_table', 50));
    }

    public function testDoInsertRecords(): void
    {
        $databaseConnection = $this->getMock(Sys25\RnBase\Database\Connection::class, ['doQuery']);
        $databaseConnection->expects(self::once())
            ->method('doQuery')
            ->with(
                "INSERT INTO tx_mksearch_queue(cr_date,prefer,recid,tablename,data,resolver) VALUES \r\nvalue1, \r\nvalue2;"
            );

        $service = $this->getMock('tx_mksearch_service_internal_Index', ['getDatabaseConnection']);
        $service->expects(self::once())
            ->method('getDatabaseConnection')
            ->willReturn($databaseConnection);

        $this->callInaccessibleMethod($service, 'doInsertRecords', ['value1', 'value2']);
    }

    public function testDoInsertRecordsWhenNoSqlValuesGiven(): void
    {
        $service = $this->getMock('tx_mksearch_service_internal_Index', ['getDatabaseConnection']);
        $service->expects(self::never())
            ->method('getDatabaseConnection');

        self::assertTrue($this->callInaccessibleMethod($service, 'doInsertRecords', []));
    }

    public function testAddRecordsToIndex(): void
    {
        $execTimeFormatted = Sys25\RnBase\Utility\Dates::datetime_tstamp2mysql(TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Core\Context\Context::class)->getPropertyFromAspect('date', 'timestamp'));
        $service = $this->getMock('tx_mksearch_service_internal_Index', ['doInsertRecords']);
        $service->expects(self::once())
            ->method('doInsertRecords')
            ->with([
                0 => sprintf("('%s','0','50','tx_mktest_table','','')", $execTimeFormatted),
                1 => sprintf("('%s','1','51','tx_mktest_table','','')", $execTimeFormatted),
                2 => sprintf("('%s','1','52','tx_mktest_table','','tx_mksearch_resolver_Test')", $execTimeFormatted),
                3 => sprintf("('%s','1','53','tx_mktest_table','test index','tx_mksearch_resolver_Test')", $execTimeFormatted),
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

    public function testAddRecordsToIndexWithMoreThan500Records(): void
    {
        $execTimeFormatted = Sys25\RnBase\Utility\Dates::datetime_tstamp2mysql(TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Core\Context\Context::class)->getPropertyFromAspect('date', 'timestamp'));
        for ($i = 1; $i <= 501; ++$i) {
            $records[] = ['tablename' => 'tx_mktest_table', 'uid' => $i];
        }

        foreach ($records as $record) {
            $expectedRecordsToInsert[] = sprintf("('%s','0','", $execTimeFormatted).$record['uid']."','".$record['tablename']."','','')";
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

    public function testAddRecordsToIndexWhenNoRecordsGiven(): void
    {
        $service = $this->getMock('tx_mksearch_service_internal_Index', ['doInsertRecords']);
        $service->expects(self::never())
            ->method('doInsertRecords');

        self::assertTrue($service->addRecordsToIndex([]));
    }
}
