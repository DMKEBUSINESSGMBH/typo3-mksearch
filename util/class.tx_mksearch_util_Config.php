<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 das Medienkombinat
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
 * Methods for accessing configuration options etc.
 */
class tx_mksearch_util_Config
{
    /**
     * Registered indexers.
     *
     * @var array
     */
    private static $indexerTableMappings = [];

    /**
     * Container for table 2 indexer mappings.
     *
     * @var array
     */
    private static $tableIndexerMappings = [];

    /**
     * Container table - indexers.
     *
     * @var array
     */
    private static $tableIndexer = [];

    /**
     * Container extkey - contenttype - indexer.
     *
     * @var array
     */
    private static $indexerMap = [];
    /**
     * Container table - resolvers.
     *
     * @var array
     */
    private static $resolverMap = [];

    /**
     * Return indexer configuration option.
     *
     * @param string $name        Name of the option
     * @param array  $contentType Content type: array('extKey' => [extension the content type belongs to], 'name' => [name of content type])
     *
     * @return mixed
     *
     * @deprecated
     */
    public static function getIndexerOption($name, $contentType)
    {
        throw new Exception('tx_mksearch_util_Config::getIndexerOption is deprecated! Use config file instead.');
        $l = \Sys25\RnBase\Configuration\Processor::getExtensionCfgValue(
            $contentType['extKey'],
            'mksearch_'.$name.'_'.$contentType['name']
        );
        if ($l) {
            return $l;
        }
        // else
        $l = \Sys25\RnBase\Configuration\Processor::getExtensionCfgValue(
            'mksearch',
            $name.'_'.$contentType['extKey'].'_'.$contentType['name']
        );
        if ($l) {
            return $l;
        }
        // else
        return \Sys25\RnBase\Configuration\Processor::getExtensionCfgValue('mksearch', $name.'_fallback');
    }

    /**
     * Return indexers.
     *
     * @param string $extKey (optional)
     *
     * @return array[tx_mksearch_interface_Indexer]
     */
    public static function getIndexers($extKey = null)
    {
        $ret = [];
        if ($extKey) {
            $cfgData = [self::$indexerMap[$extKey]];
        }
        $cfgData = array_values(self::$indexerMap);
        foreach ($cfgData as $cfg) {
            foreach ($cfg as $contentType => $classArr) {
                $ret[$classArr['className']] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($classArr['className']);
            }
        }

        return array_values($ret);
    }

    /**
     * Return indexer config array.
     *
     * @param string $extKey (optional)
     *
     * @return array
     */
    public static function getIndexerConfigs($extKey = null)
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
     * @param array  $tables
     */
    public static function registerResolver($resolver, array $tables)
    {
        foreach ($tables as $table) {
            if (array_key_exists($table, self::$tableIndexerMappings)) {
                // Es wurde bereits ein Resolver für diese tabelle registriert
                if (\Sys25\RnBase\Utility\Logger::isWarningEnabled()) {
                    \Sys25\RnBase\Utility\Logger::warn('[registerResolver] Für die Tabelle '.$table.' wurde bereits ein Resolver registriert', 'mksearch');
                }
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
     * @param array  $tables
     */
    public static function registerIndexer($extKey, $contentType, $indexerClass, array $tables, $resolver = false)
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
                $instances[] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($indexerArr['className']);
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
    public static function getIndexerByType($extKey, $contentType)
    {
        $indexerArr = self::$indexerMap[$extKey][$contentType] ?? null;

        return is_array($indexerArr) ? \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($indexerArr['className']) : false;
    }

    /**
     * Return all indexers the given tables are indexed by.
     *
     * @param array $tables
     *
     * @return array
     */
    public static function getIndexersForDatabaseTables(array $tables)
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
        return
            isset(self::$indexerTableMappings[$extKey][$contentType]) ?
                self::$indexerTableMappings[$extKey][$contentType] :
                [];
    }
}
