<?php

use TYPO3\CMS\Core\Database\ConnectionPool;

/***************************************************************
*  Copyright notice
*
*  (c) 2014 DMK E-BUSINESS GmbH
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
 * Base Testcase for DB Tests.
 *
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
abstract class tx_mksearch_tests_DbTestcase extends tx_mksearch_tests_Testcase
{
    protected $workspaceBackup;
    protected $db;

    /**
     * @var array
     */
    private $originalDatabaseName;

    /**
     * @var array
     */
    protected $addRootLineFieldsBackup = '';

    /**
     * Liste der extensions, welche in die test DB importiert werden müssen.
     *
     * @var array
     */
    protected $importExtensions = ['core', 'frontend', 'mksearch'];

    /**
     * Liste der daten, welche in die test DB importiert werden müssen.
     *
     * @var array
     */
    protected $importDataSets = [];

    protected function setUp(): void
    {
        self::markTestIncomplete('Database tests are no longer supported. Please switch to functional tests');

        tx_mksearch_tests_Util::emptyAddRootlineFields();

        // set up the TCA
        tx_mksearch_tests_Util::tcaSetUp($this->importExtensions);

        // set up hooks
        tx_mksearch_tests_Util::hooksSetUp();

        // wir deaktivieren den relation manager
        tx_mksearch_tests_Util::disableRelationManager();

        // set up the workspace
        $this->workspaceBackup = $GLOBALS['BE_USER']->workspace;
        $GLOBALS['BE_USER']->setWorkspace(0);

        try {
            $this->createDatabase();
        } catch (RuntimeException $e) {
            $this->markTestSkipped(
                'This test is skipped because the test database is not available.'
            );
        }
        // assuming that test-database can be created otherwise PHPUnit will skip the test
        $this->db = $this->useTestDatabase();

        $this->setUpTestDatabaseForConnectionPool();

        $this->importStdDB();
        $this->importExtensions($this->importExtensions);

        foreach ($this->importDataSets as $importDataSet) {
            $this->importDataSet($importDataSet);
        }

        $this->purgeRootlineCaches();
    }

    protected function tearDown(): void
    {
        // tear down TCA
        tx_mksearch_tests_Util::tcaTearDown();

        // tear down hooks
        tx_mksearch_tests_Util::hooksTearDown();

        // wir aktivieren den relation manager wieder
        tx_mksearch_tests_Util::restoreRelationManager();

        $this->tearDownDatabase();

        // tear down Workspace
        $GLOBALS['BE_USER']->setWorkspace($this->workspaceBackup);

        tx_mksearch_tests_Util::resetAddRootlineFields();

        $this->purgeRootlineCaches();
    }

    protected function purgeRootlineCaches()
    {
        \TYPO3\CMS\Core\Utility\RootlineUtility::purgeCaches();
    }

    /**
     * We need to set the new database for the connection pool connections aswell
     * because it is used for example in the rootline utility.
     *
     * @todo we should not only support the default connection
     */
    protected function setUpTestDatabaseForConnectionPool()
    {
        // truncate connections so they can be reinitialized with $this->testDatabase
        // when needed
        $connections = new ReflectionProperty(ConnectionPool::class, 'connections');
        $connections->setAccessible(true);
        $connections->setValue(null, []);

        $this->originalDatabaseName = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'];
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'] = $this->testDatabase;
    }

    protected function tearDownDatabase()
    {
        if ($this->originalDatabaseName) {
            // $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'] is used
            // inside phpunit to get the original database so we need to reset that
            // before anything is done
            $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['dbname'] = $this->originalDatabaseName;
        }
        $this->cleanDatabase();
        $this->dropDatabase();

        // truncate connections so they can be reinitialized with the real configuration
        $connections = new ReflectionProperty(ConnectionPool::class, 'connections');
        $connections->setAccessible(true);
        $connections->setValue(null, []);

        $this->switchToTypo3Database();
    }
}
