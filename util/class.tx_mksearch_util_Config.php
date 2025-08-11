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
 * Methods for accessing configuration options etc.
 */
class tx_mksearch_util_Config
{
    /**
     * Registered indexers.
     */
    private static array $indexerTableMappings = [];

    /**
     * Container for table 2 indexer mappings.
     */
    private static array $tableIndexerMappings = [];

    /**
     * Container table - indexers.
     */
    private static array $tableIndexer = [];

    /**
     * Container extkey - contenttype - indexer.
     */
    private static array $indexerMap = [];

    /**
     * Container table - resolvers.
     */
    private static array $resolverMap = [];

    /**
     * Return indexer configuration option.
     *
     * @param string $name        Name of the option
     * @param array  $contentType Content type: array('extKey' => [extension the content type belongs to], 'name' => [name of content type])
     *
     * @deprecated
     */
    public static function getIndexerOption($name, $contentType): never
    {
        throw new Exception('tx_mksearch_util_Config::getIndexerOption is deprecated! Use config file instead.');
    }

    /**
     * Return indexers.
     *
     * @param string $extKey (optional)
     *
     * @return array[tx_mksearch_interface_Indexer]
     */
    public static function getIndexers($extKey = null): array
    {
        $ret = [];
        if ($extKey) {
            $cfgData = [self::$indexerMap[$extKey]];
        }

        $cfgData = array_values(self::$indexerMap);
        foreach ($cfgData as $cfg) {
            foreach ($cfg as $classArr) {
                $ret[$classArr['className']] = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($classArr['className']);
            }
        }

        return array_values($ret);
    }

    /**
     * Return indexer config array.
     *
     * @param string $extKey (optional)
     */
    public static function getIndexerConfigs($extKey = null): array
    {
        // All indexers
        if (!$extKey) {
            return self::$indexerTableMappings;
        }

        // Indexers with given $extKey
        return isset(self::$indexerTableMappings[$extKey]) ?
                array_keys(self::$indexerTableMappings[$extKey]) : [];
    }

    /**
     * Get the given indexer's Typoscript configuration.
     *
     * @param string $extKey
     * @param string $contentType
     *
     * @return string
     */
    public static function getIndexerDefaultTSConfig($extKey, $contentType)
    {
        $indexer = self::getIndexerByType($extKey, $contentType);

        return is_object($indexer) ? $indexer->getDefaultTSConfig() : '';
    }

    /**
     * Registriert einen Resolver für eine oder mehrere Tabellen.
     * Achtung: Für eine Tabelle kann es nur einen Resolver geben.
     *          Existiert bereits ein Resolver für die Tabelle,
     *          wird dieser Überschrieben.
     *
     * @param string $resolver
     */
    public static function registerResolver($resolver, array $tables): void
    {
        foreach ($tables as $table) {
            // Es wurde bereits ein Resolver für diese tabelle registriert
            if (array_key_exists($table, self::$tableIndexerMappings) && Sys25\RnBase\Utility\Logger::isWarningEnabled()) {
                Sys25\RnBase\Utility\Logger::warn('[registerResolver] Für die Tabelle '.$table.' wurde bereits ein Resolver registriert', 'mksearch');
            }

            self::$resolverMap[$table] = ['className' => $resolver];
        }
    }

    /**
     * Register an index and define the table it is responsible for.
     *
     * @param string $extKey
     * @param string $contentType
     * @param string $indexerClass class name of indexer implementation
     */
    public static function registerIndexer($extKey, $contentType, $indexerClass, array $tables, $resolver = false): void
    {
        if (!isset(self::$indexerTableMappings[$extKey])) {
            self::$indexerTableMappings[$extKey] = [];
        }

        if (!isset(self::$indexerTableMappings[$extKey][$contentType])) {
            self::$indexerTableMappings[$extKey][$contentType] = [];
        }

        self::$indexerTableMappings[$extKey][$contentType] =
            array_merge(self::$indexerTableMappings[$extKey][$contentType], $tables);

        $ec = ['extKey' => $extKey, 'contentType' => $contentType, 'className' => $indexerClass];

        foreach ($tables as $table) {
            if (!isset(self::$tableIndexerMappings[$table])) {
                self::$tableIndexerMappings[$table] = [];
            }

            if (!in_array($ec, self::$tableIndexerMappings[$table])) {
                self::$tableIndexerMappings[$table][] = $ec;
            }
        }

        if ($resolver) {
            self::registerResolver($resolver, $tables);
        }

        self::$indexerMap[$extKey][$contentType] = ['className' => $indexerClass];
    }

    /**
     * Return all indexers a table is indexed by
     * Achtung, hier wird nur das Config-Array geliefert, nicht die wirkliche Instanz des Indexers!
     *
     * @param string $table
     *
     * @return array
     */
    public static function getIndexersForDatabaseTable($table)
    {
        return array_key_exists($table, self::$tableIndexerMappings) ?
                self::$tableIndexerMappings[$table] : [];
    }

    /**
     * Liefert eine Resolver Klasse für eine Tabelle.
     *
     * @param string $table
     *
     * @return array
     */
    public static function getResolverForDatabaseTable($table)
    {
        return array_key_exists($table, self::$resolverMap) ?
                self::$resolverMap[$table] : [];
    }

    /**
     * Returns indexer instances for a given DB tablename.
     *
     * @param string $table
     *
     * @return array
     */
    public static function getIndexersForTable($table)
    {
        if (!array_key_exists($table, self::$tableIndexer)) {
            $instances = [];
            foreach (self::getIndexersForDatabaseTable($table) as $indexerArr) {
                $instances[] = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($indexerArr['className']);
            }

            self::$tableIndexer['table'] = $instances;
        }

        return self::$tableIndexer['table'];
    }

    /**
     * Returns the indexer instance by type.
     *
     * @param string $extKey
     * @param string $contentType
     *
     * @return tx_mksearch_interface_Indexer
     */
    public static function getIndexerByType($extKey, $contentType): object|false
    {
        $indexerArr = self::$indexerMap[$extKey][$contentType] ?? null;

        return is_array($indexerArr) ? TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($indexerArr['className']) : false;
    }

    /**
     * Return all indexers the given tables are indexed by.
     */
    public static function getIndexersForDatabaseTables(array $tables): array
    {
        $res = [];
        foreach (array_unique($tables) as $table) {
            foreach (self::getIndexersForDatabaseTable($table) as $i) {
                if (!in_array($i, $res)) {
                    $res[] = $i;
                }
            }
        }

        return $res;
    }

    /**
     * Return all tables a given indexer is responsible for.
     *
     * @param string $extKey
     * @param string $contentType
     *
     * @return array
     */
    public static function getDatabaseTablesForIndexer($extKey, $contentType)
    {
        return self::$indexerTableMappings[$extKey][$contentType] ?? [];
    }
}
