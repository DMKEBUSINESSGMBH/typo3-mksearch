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
 * tx_mksearch_tests_hooks_DatabaseConnection_testcase.
 *
 * @author          Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_hooks_DatabaseConnectionTest extends tx_mksearch_tests_Testcase
{
    /**
     * @var int
     */
    private static $loadHiddenObjectsConfigurationBackup;

    protected function setUp(): void
    {
        self::$loadHiddenObjectsConfigurationBackup = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['rn_base']['loadHiddenObjects'] ?? false;
    }

    protected function tearDown(): void
    {
        $property = new ReflectionProperty('tx_mksearch_hooks_DatabaseConnection', 'loadHiddenObjectsConfigurationBackup');
        $property->setAccessible(true);
        $property->setValue(null, null);

        $property = new ReflectionProperty('tx_mksearch_hooks_DatabaseConnection', 'loadHiddenObjectsConfigurationBackupSet');
        $property->setAccessible(true);
        $property->setValue(null, false);

        $this->setIsIndexingInProgress(false);

        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['rn_base']['loadHiddenObjects'] = self::$loadHiddenObjectsConfigurationBackup;
    }

    /**
     * @unit
     */
    public function testDoSelectPostResetsRnBaseConfiguration()
    {
        $backUpProperty = new ReflectionProperty('tx_mksearch_hooks_DatabaseConnection', 'loadHiddenObjectsConfigurationBackup');
        $backUpProperty->setAccessible(true);
        $backUpProperty->setValue(null, $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['rn_base']['loadHiddenObjects'] ?? null);
        $initialValue = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['rn_base']['loadHiddenObjects'] ?? null;

        $backUpSetProperty = new ReflectionProperty('tx_mksearch_hooks_DatabaseConnection', 'loadHiddenObjectsConfigurationBackupSet');
        $backUpSetProperty->setAccessible(true);
        $backUpSetProperty->setValue(null, true);

        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['rn_base']['loadHiddenObjects'] = 'test';

        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_hooks_DatabaseConnection')->doSelectPost();

        self::assertSame($initialValue, $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['rn_base']['loadHiddenObjects']);
        self::assertFalse($backUpSetProperty->getValue(null));
        self::assertNull($backUpProperty->getValue(null));
    }

    /**
     * @unit
     */
    public function testDoSelectPostResetsRnBaseConfigurationNotIfBackUpWasNotSet()
    {
        $backUpProperty = new ReflectionProperty('tx_mksearch_hooks_DatabaseConnection', 'loadHiddenObjectsConfigurationBackup');
        $backUpProperty->setAccessible(true);
        $backUpProperty->setValue(null, $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['rn_base']['loadHiddenObjects'] ?? null);
        $initialValue = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['rn_base']['loadHiddenObjects'] ?? null;

        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['rn_base']['loadHiddenObjects'] = 'test';

        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_hooks_DatabaseConnection')->doSelectPost();

        self::assertSame('test', $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['rn_base']['loadHiddenObjects']);

        $backUpSetProperty = new ReflectionProperty('tx_mksearch_hooks_DatabaseConnection', 'loadHiddenObjectsConfigurationBackupSet');
        $backUpSetProperty->setAccessible(true);
        self::assertFalse($backUpSetProperty->getValue(null));
        self::assertSame($initialValue, $backUpProperty->getValue(null));
    }

    /**
     * @unit
     */
    public function testDoSelectPreSetEnableFieldsFeIfDuringIndexingAndNoEnableFieldsOff()
    {
        $this->setIsIndexingInProgress();
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['rn_base']['loadHiddenObjects'] = 'test';

        $paramaters = ['options' => []];

        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_hooks_DatabaseConnection')->doSelectPre($paramaters);

        self::assertSame(1, $paramaters['options']['enablefieldsfe']);
        self::assertSame(0, $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['rn_base']['loadHiddenObjects']);

        $backUpProperty = new ReflectionProperty('tx_mksearch_hooks_DatabaseConnection', 'loadHiddenObjectsConfigurationBackup');
        $backUpProperty->setAccessible(true);
        self::assertSame('test', $backUpProperty->getValue(null));

        $backUpSetProperty = new ReflectionProperty('tx_mksearch_hooks_DatabaseConnection', 'loadHiddenObjectsConfigurationBackupSet');
        $backUpSetProperty->setAccessible(true);
        self::assertTrue($backUpSetProperty->getValue(null));
    }

    /**
     * @unit
     */
    public function testDoSelectPreSetEnableFieldsFeAndRemovesEnableFieldsBeIfDuringIndexingNoEnableFieldsOffAndEnableFieldsBe()
    {
        $this->setIsIndexingInProgress();
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['rn_base']['loadHiddenObjects'] = 'test';

        $paramaters = ['options' => ['enablefieldsbe' => 1]];

        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_hooks_DatabaseConnection')->doSelectPre($paramaters);

        self::assertSame(1, $paramaters['options']['enablefieldsfe']);
        self::assertArrayNotHasKey('enablefieldsbe', $paramaters['options']);
        self::assertSame(0, $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['rn_base']['loadHiddenObjects']);

        $backUpProperty = new ReflectionProperty('tx_mksearch_hooks_DatabaseConnection', 'loadHiddenObjectsConfigurationBackup');
        $backUpProperty->setAccessible(true);
        self::assertSame('test', $backUpProperty->getValue(null));

        $backUpSetProperty = new ReflectionProperty('tx_mksearch_hooks_DatabaseConnection', 'loadHiddenObjectsConfigurationBackupSet');
        $backUpSetProperty->setAccessible(true);
        self::assertTrue($backUpSetProperty->getValue(null));
    }

    /**
     * @unit
     */
    public function testDoSelectPreDoesNothingWhenDuringIndexingButEnableFieldsOff()
    {
        $this->setIsIndexingInProgress();
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['rn_base']['loadHiddenObjects'] = 'test';

        $paramaters = ['options' => ['enablefieldsoff' => true]];

        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_hooks_DatabaseConnection')->doSelectPre($paramaters);

        self::assertArrayNotHasKey('enablefieldsfe', $paramaters['options']);
        self::assertTrue($paramaters['options']['enablefieldsoff']);
        self::assertSame('test', $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['rn_base']['loadHiddenObjects']);

        $backUpProperty = new ReflectionProperty('tx_mksearch_hooks_DatabaseConnection', 'loadHiddenObjectsConfigurationBackup');
        $backUpProperty->setAccessible(true);
        self::assertNull($backUpProperty->getValue(null));

        $backUpSetProperty = new ReflectionProperty('tx_mksearch_hooks_DatabaseConnection', 'loadHiddenObjectsConfigurationBackupSet');
        $backUpSetProperty->setAccessible(true);
        self::assertFalse($backUpSetProperty->getValue(null));
    }

    /**
     * @unit
     */
    public function testDoSelectPreDoesNothingWhenNotDuringIndexing()
    {
        $this->setIsIndexingInProgress(false);
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['rn_base']['loadHiddenObjects'] = 'test';

        $paramaters = ['options' => ['enablefieldsoff' => true]];

        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_hooks_DatabaseConnection')->doSelectPre($paramaters);

        self::assertArrayNotHasKey('enablefieldsfe', $paramaters['options']);
        self::assertTrue($paramaters['options']['enablefieldsoff']);
        self::assertSame('test', $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['rn_base']['loadHiddenObjects']);

        $backUpProperty = new ReflectionProperty('tx_mksearch_hooks_DatabaseConnection', 'loadHiddenObjectsConfigurationBackup');
        $backUpProperty->setAccessible(true);
        self::assertNull($backUpProperty->getValue(null));

        $backUpSetProperty = new ReflectionProperty('tx_mksearch_hooks_DatabaseConnection', 'loadHiddenObjectsConfigurationBackupSet');
        $backUpSetProperty->setAccessible(true);
        self::assertFalse($backUpSetProperty->getValue(null));
    }

    /**
     * @param bool $indexingInProgress
     */
    protected function setIsIndexingInProgress($indexingInProgress = true)
    {
        $property = new ReflectionProperty('tx_mksearch_service_internal_Index', 'indexingInProgress');
        $property->setAccessible(true);
        $property->setValue(null, $indexingInProgress);
    }
}
