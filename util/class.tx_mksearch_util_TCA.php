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
 * Tools for use in TCA.
 */
class tx_mksearch_util_TCA
{
    /**
     * Convert HTML to plain text.
     *
     * Removes HTML tags and HTML comments and converts HTML entities
     * to their applicable characters.
     *
     * @param string $t
     *
     * @return string Converted string (utf8-encoded)
     */
    public static function html2plain($t): string
    {
        return html_entity_decode(
            (string) preg_replace(
                ['/(\s+|(<.*?>)+)/', '/<!--.*?-->/'],
                [' ', ''],
                $t
            ),
            ENT_QUOTES,
            'UTF-8'
        );
    }

    /**
     * Get extension keys of all registered indexers.
     */
    public static function getIndexerExtKeys(array &$params): void
    {
        $config = tx_mksearch_util_Config::getIndexerConfigs();
        // wir sortieren vorher, damit bestehende items nicht mit sortiert werden!
        $keys = array_keys($config);
        sort($keys);
        foreach ($keys as $k) {
            $params['items'][] = [$k, $k];
        }
    }

    /**
     * Get content types keys of the given indexer extension.
     */
    public static function getIndexerContentTypes(array &$params): void
    {
        $extKey = $params['row']['extkey'][0] ?? $params['row']['extkey'] ?? '';

        // im flexform nachsehen
        if (!$extKey && !empty($params['config']['extKeyField']) && !empty($params['config']['extKeySection'])) {
            $piFlexformField = is_array($params['flexParentDatabaseRow']) ?
                $params['flexParentDatabaseRow']['pi_flexform'] : $params['row']['pi_flexform'];

            if ('tt_content' == $params['table'] && !empty($piFlexformField)) {
                $flexform = is_string($piFlexformField) ?
                    Sys25\RnBase\Utility\Arrays::xml2array($piFlexformField) :
                    $piFlexformField;
            }

            if (!empty($flexform)) {
                $section = $params['config']['extKeySection'];
                $field = $params['config']['extKeyField'];
                if (!empty($flexform['data'][$section])
                    && !empty($flexform['data'][$section]['lDEF'][$field])) {
                    $extKey = $flexform['data'][$section]['lDEF'][$field]['vDEF'];
                }
            }
        }

        if (!$extKey) {
            return;
        }

        $config = tx_mksearch_util_Config::getIndexerConfigs($extKey);
        // wir sortieren vorher, damit bestehende items nicht mit sortiert werden!
        sort($config);
        foreach ($config as $k) {
            $params['items'][] = [$k, $k];
        }
    }

    /**
     * Insert default TS configuration of the given indexer.
     */
    public static function insertIndexerDefaultTSConfig(array &$params): ?string
    {
        $extKey = is_array($params['row']['extkey']) ?
            $params['row']['extkey'][0] : $params['row']['extkey'];
        $contentType = is_array($params['row']['contenttype']) ?
            $params['row']['contenttype'][0] : $params['row']['contenttype'];

        if (!(isset($params['params']['insertBetween']) && is_array($params['params']['insertBetween'])
                    && !empty($extKey) && !empty($contentType))) {
            return null;
        }

        try {
            $ts = tx_mksearch_util_Config::getIndexerDefaultTSConfig($extKey, $contentType);
        } catch (Exception) {
            // "Service not found" exception is thrown for invalid $extkey/$contenttype combinations
            // which may temporarily occur on changing the $extkey!
            return '';
        }

        if (!$ts) {
            return '';
        }

        $lpos = strpos((string) $params['item'], (string) $params['params']['insertBetween'][0]);
        if (false === $lpos) {
            return null;
        }

        $lpos += strlen((string) $params['params']['insertBetween'][0]);
        $rpos = strrpos((string) $params['item'], (string) $params['params']['insertBetween'][1]);

        if (false === $rpos || $lpos > $rpos) {
            return null;
        }

        $between = substr((string) $params['item'], $lpos, $rpos - $lpos);
        if (!isset($params['params']['onMatchOnly']) || preg_match($params['params']['onMatchOnly'], $between)) {
            $params['item'] = substr((string) $params['item'], 0, $lpos).$ts.substr((string) $params['item'], $rpos, strlen((string) $params['item']) - $rpos);
        }

        return null;
    }

    /**
     * Insert solr indexes fot the current rootpage/domain.
     */
    public static function getIndexes(array &$params): void
    {
        // rootpage des aktuellen plugins
        $pid = is_array($params['flexParentDatabaseRow']) ?
            $params['flexParentDatabaseRow']['pid'] : $params['row']['pid'];

        $rootOfPlugin = tx_mksearch_util_Indexer::getInstance()->getSiteRootPage($pid);

        // Wir suchen alle Indexes, da die aktuelle PageId oder die RootPageId
        // nicht die der PageId des Indexes entsprechen muss.
        $fields = [];
        $fields['INDX.ENGINE'][OP_EQ] = 'solr';
        $options = [];
        // $options['debug'] = 1;
        $options['enablefieldsfe'] = 1;
        $indexer = tx_mksearch_util_ServiceRegistry::getIntIndexService()->search($fields, $options);
        foreach ($indexer as $index) {
            /* @var $index tx_mksearch_model_internal_Index */
            // rootpage des indexes
            $rootOfIndex = tx_mksearch_util_Indexer::getInstance()->getSiteRootPage($index->getProperty('pid'));
            // Sind die RootPages identisch oder ist der Index global,
            // kann der Index verwendet werden.
            if (empty($rootOfIndex['uid'] ?? null) || $rootOfIndex['uid'] == ($rootOfPlugin['uid'] ?? null)) {
                $params['items'][] = [$index->getTitle(), $index->getUid()];
            }
        }
    }

    /**
     * Liefert den Spaltennamen für das Parent der aktuellen lokalisierung.
     *
     * @param string $tableName
     *
     * @return string
     */
    public static function getTransOrigPointerFieldForTable($tableName)
    {
        global $TCA;
        if (empty($TCA[$tableName]) || empty($TCA[$tableName]['ctrl']['transOrigPointerField'])) {
            return '';
        }

        return $TCA[$tableName]['ctrl']['transOrigPointerField'];
    }

    /**
     * Liefert den Spaltennamen für das Parent der aktuellen lokalisierung.
     *
     * @param string $tableName
     *
     * @return string
     */
    public static function getLanguageFieldForTable($tableName)
    {
        global $TCA;
        if (empty($TCA[$tableName]) || empty($TCA[$tableName]['ctrl']['languageField'])) {
            return '';
        }

        return $TCA[$tableName]['ctrl']['languageField'];
    }
}
