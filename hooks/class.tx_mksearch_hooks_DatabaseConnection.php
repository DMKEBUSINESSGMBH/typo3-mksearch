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
 * tx_mksearch_hooks_DatabaseConnection.
 *
 * @author          Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_hooks_DatabaseConnection
{
    /**
     * @var int
     */
    private static $loadHiddenObjectsConfigurationBackup;

    private static bool $loadHiddenObjectsConfigurationBackupSet = false;

    /**
     * During indexing we always use enablefieldsfe for selects to avoid hidden
     * data being indexed. enablefieldsoff needs to be set when this behaviour is
     * not desired. in this case setting enable fields has to be done manually
     * when searching.
     *
     * Example without this fix: Wneh a news is indexed hidden categories will be
     * indexed.
     */
    public function doSelectPre(array &$parameters): void
    {
        if (tx_mksearch_service_internal_Index::isIndexingInProgress()
            && !isset($parameters['options']['enablefieldsoff'])
        ) {
            $parameters['options']['enablefieldsfe'] = 1;

            if (isset($parameters['options']['enablefieldsbe'])) {
                unset($parameters['options']['enablefieldsbe']);
            }

            // avoid that our changes are overwritten when loadHiddenObjects is true.
            self::$loadHiddenObjectsConfigurationBackup = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['rn_base']['loadHiddenObjects'];
            self::$loadHiddenObjectsConfigurationBackupSet = true;
            $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['rn_base']['loadHiddenObjects'] = 0;
        }
    }

    public function doSelectPost(): void
    {
        if (self::$loadHiddenObjectsConfigurationBackupSet) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['rn_base']['loadHiddenObjects'] = self::$loadHiddenObjectsConfigurationBackup;
            self::$loadHiddenObjectsConfigurationBackup = null;
            self::$loadHiddenObjectsConfigurationBackupSet = false;
        }
    }
}
