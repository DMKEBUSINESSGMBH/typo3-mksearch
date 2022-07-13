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
 * Hooks for auto-updating search indices.
 */
class tx_mksearch_hooks_IndexerAutoUpdate
{
    /**
     * Hook after saving a record in Typo3 backend:
     * Perform index update depending on given data.
     *
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     */
    public function processDatamap_afterAllOperations($dataHandler)
    {
        // Nothing to do?
        if (empty($dataHandler->datamap)) {
            return;
        }

        // daten sammeln
        $records = [];
        foreach ($dataHandler->datamap as $table => $uids) {
            // beim rückgängig machen eines einzelnen records über die history funktion
            // ist nicht jeder datensatz in der datamap wie sonst. Es gibt den üblichen:
            //
            // array(
            //     tablename => array(
            //         uid123 => array(),
            //     )
            // )
            //
            // und einen weiteren, den wir aber nicht beachten brauchen.
            //
            // array(
            //     tablename:uid123 => -1
            // )
            if (!is_array($uids)) {
                continue;
            }

            $records[$table] = [];
            foreach (array_keys($uids) as $uid) {
                // New element?
                if (!is_numeric($uid)) {
                    $uid = $dataHandler->substNEWwithIDs[$uid] ?? 0;
                }
                $records[$table][] = (int) $uid;
            }
        }

        return $this->processAutoUpdate($records);
    }

    /**
     * Hook after performing different record actions in Typo3 backend:
     * Update indexes according to the just performed action.
     *
     * @param string                                   $command
     * @param string                                   $table
     * @param int                                      $id
     * @param int                                      $value
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     *
     * @todo Treatment of any additional actions necessary?
     */
    public function processCmdmap_postProcess($command, $table, $id, $value, $dataHandler)
    {
        $indexer = tx_mksearch_util_Config::getIndexersForDatabaseTable($table);
        if (!count($indexer)) {
            return;
        }

        $srv = tx_mksearch_util_ServiceRegistry::getIntIndexService();
        switch ($command) {
            case 'delete':
            case 'undelete':
            case 'move':
            case 'version':
                $srv->addRecordToIndex($table, $id, true);
                break;
            default:
                break;
        }
    }

    /**
     * rn_base hook nachdem ein insert durchgeführt wurde.
     * Requires rn_base 0.14.6.
     *
     * @param array &$params
     */
    public function rnBaseDoInsertPost(&$params)
    {
        if (!$this->isRnBaseUtilDbHookActivated()) {
            return null;
        }

        return $this->processAutoUpdate(
            [
                $params['tablename'] => [
                    $params['uid'],
                ],
            ]
        );
    }

    /**
     * rn_base hook nachdem ein Update durchgeführt wurde.
     * Requires rn_base 0.14.6.
     *
     * @param array &$params
     */
    public function rnBaseDoUpdatePost(&$params)
    {
        if (!$this->isRnBaseUtilDbHookActivated()) {
            return null;
        }

        $table = $params['tablename'];
        // wür müssen einen select machen, um die uid zu erfahren
        if (empty($params['values']['uid'])) {
            $data = [
                'type' => 'select',
                'from' => $table,
                'where' => $params['where'],
                'options' => $params['options'],
            ];
        } // wir haben eine uid, die übergeben wir!
        else {
            $data = $params['values']['uid'];
        }

        return $this->processAutoUpdate([$table => [$data]]);
    }

    /**
     * Wir prüfen das nochmal für den Fall dass die Hooks zur Laufzeit deaktiviert wurden.
     *
     * @return bool
     */
    protected function isRnBaseUtilDbHookActivated()
    {
        return \Sys25\RnBase\Configuration\Processor::getExtensionCfgValue('mksearch', 'enableRnBaseUtilDbHook');
    }

    /**
     * rn_base hook nachdem ein Update durchgeführt wurde.
     * Requires rn_base 0.14.6.
     *
     * @param array &$params
     */
    public function rnBaseDoDeletePre(&$params)
    {
        // use the same method as doUpdate
        return $this->rnBaseDoUpdatePost($params);
    }

    /**
     * check for updatable records and fill queue.
     *
     * @param array $records
     *                       array(
     *                       'pages' => array('1', '2', '3'. '5', '8', '13'),
     *                       'tt_content' => array('1', '2', '3'. '5', '8', '13'),
     *                       );
     *
     * @todo this method needs to be refactored as all indices and indexers
     * are retrieved from database for every action, for example for every insert that is made with
     * \Sys25\RnBase\Database\Connection.
     */
    public function processAutoUpdate(array $records)
    {
        if (empty($records)) {
            return null;
        }

        // Cores suchen:
        $intIndexSrv = $this->getIntIndexService();
        $indices = $intIndexSrv->findAll();

        // Es ist nichts zu tun, da keine Cores definiert sind!
        if (empty($indices)) {
            return null;
        }

        foreach ($records as $table => $uidList) {
            if (false !== strstr($table, 'tx_mksearch_')) {
                // Ignore internal tables
                continue;
            }

            // indexer besorgen
            $indexers = $this->getIndexersForTable($table);
            // Keine Indexer für diese Tabelle vorhanden.
            if (empty($indexers)) {
                continue;
            }

            // Prüfen, ob für diese Tabelle ein Indexer Definiert wurden.
            $isDefined = false;
            foreach ($indices as $index) {
                foreach ($indexers as $indexer) {
                    if ($intIndexSrv->isIndexerDefined($index, $indexer)) {
                        $isDefined = true;
                        break 2;
                    }
                }
            }
            // In die Queue legen, wenn Indexer für die Tabelle existieren.
            if ($isDefined) {
                foreach ($uidList as $uid) {
                    $this->addRecordToIndex($table, $uid);
                }
            }
        }

        return null;
    }

    /**
     * fügt einen datensatz für die indizierung in die queu.
     *
     * @param string $table
     * @param mixed  $data  uid or where clause
     *
     * @return bool
     */
    protected function addRecordToIndex($table, $data)
    {
        $rows = $this->getUidsToIndex($table, $data);

        if (empty($rows)) {
            return false;
        }

        $intIndexSrv = $this->getIntIndexService();
        foreach ($rows as $uid) {
            $intIndexSrv->addRecordToIndex($table, $uid);
        }

        return true;
    }

    /**
     * in $data kann eine uid oder ein array
     * mit beispielsweise einem notwendigen select stecken,
     * um die uids zu erfahren.
     *
     * @param mixed $data
     *
     * @return array
     */
    protected function getUidsToIndex($table, $data)
    {
        if (is_numeric($data)) {
            return [(int) $data];
        }

        if (is_array($data) && isset($data['type'])) {
            if ('select' === $data['type']) {
                $from = empty($data['from']) ? $table : $data['from'];
                $options = empty($data['options']) || !is_array($data['options']) ? [] : $data['options'];
                $options['where'] = $options['where'] ?? $data['where'] ?? '';
                $options['enablefieldsoff'] = true;
                $databaseUtility = $this->getRnbaseDatabaseUtility();
                if ($rows = $databaseUtility->doSelect('uid', $from, $options)) {
                    $rows = call_user_func_array('array_merge_recursive', $rows);
                }
                if (empty($rows['uid'])) {
                    return [];
                }

                return is_array($rows['uid']) ? $rows['uid'] : [$rows['uid']];
            }
        }

        return [];
    }

    /**
     * @return \Sys25\RnBase\Database\Connection
     */
    protected function getRnbaseDatabaseUtility()
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Database\Connection::class);
    }

    /**
     * @param string $table
     *
     * @return array
     */
    protected function getIndexersForTable($table)
    {
        return tx_mksearch_util_Config::getIndexersForTable($table);
    }

    /**
     * @return tx_mksearch_service_internal_Index
     */
    protected function getIntIndexService()
    {
        return tx_mksearch_util_ServiceRegistry::getIntIndexService();
    }
}
