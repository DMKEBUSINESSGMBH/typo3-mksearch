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
 * Service for accessing index models from database.
 */
class tx_mksearch_service_internal_Index extends tx_mksearch_service_internal_Base
{
    /**
     * @var bool
     */
    private static $indexingInProgress = false;

    /**
     * Search class of this service.
     *
     * @var string
     */
    protected $searchClass = 'tx_mksearch_search_Index';
    /**
     * Database table responsible for indexing queue management.
     *
     * @var string
     */
    private static $queueTable = 'tx_mksearch_queue';

    const TYPE_SOLR = 'solr';

    /**
     * Search database for all configurated Indices.
     *
     * @param tx_mksearch_model_internal_Composite $indexerconfig
     *
     * @return array[tx_mksearch_model_internal_Index]
     */
    public function getByComposite(tx_mksearch_model_internal_Composite $composite)
    {
        $fields['INDXCMPMM.uid_foreign'][OP_EQ_INT] = $composite->getUid();

        return $this->search($fields, []);
    }

    /**
     * Add a single database record to search index.
     *
     * @param string $tableName
     * @param int    $uid
     * @param bool   $prefer
     * @param string $resolver  class name of record resolver
     * @param array  $data
     *
     * @return bool true if record was successfully spooled
     */
    public function indexRecord($tableName, $uid, $prefer = false, $resolver = false, $data = false)
    {
        if (false === $resolver) {
            $resolver = tx_mksearch_util_Config::getResolverForDatabaseTable($tableName);
            $resolver = count($resolver) ? $resolver['className'] : '';
        }
        // dummy record bauen!
        $record = [
                'recid' => $uid,
                'tablename' => $tableName,
                'resolver' => $resolver,
                'data' => is_array($data) ? serialize($data) : $data,
            ];
        // Indizierung starten
        $this->executeQueueData([$record]);
    }

    /**
     * Builds the record for insert.
     *
     * @param string $tableName
     * @param int    $uid
     * @param bool   $prefer
     * @param string $resolver  class name of record resolver
     * @param array  $data
     * @param array  $options
     *
     * @return mixed array: (cr_date,prefer,recid,tablename,data,resolver) | false: if allredy exists
     */
    private function buildRecordForIndex($tableName, $uid, $prefer = false, $resolver = false, $data = false, array $options = [])
    {
        $checkExisting = isset($options['checkExisting']) ? $options['checkExisting'] : true;
        if (false === $resolver) {
            $resolver = tx_mksearch_util_Config::getResolverForDatabaseTable($tableName);
            $resolver = count($resolver) ? $resolver['className'] : '';
        }
        if ($checkExisting) {
            $options = [];
            $options['where'] = 'recid=\''.$uid.'\' AND tablename=\''.$tableName.'\' AND deleted=0';
            $options['enablefieldsoff'] = 1;
            $ret = $this->getDatabaseConnection()->doSelect('uid', self::$queueTable, $options);
            if (count($ret)) {
                return false;
            } // Item schon in queue
        }

        // achtung: die reihenfolge ist wichtig für addRecordsToIndex
        $record = [
            'cr_date' => tx_rnbase_util_Dates::datetime_tstamp2mysql($GLOBALS['EXEC_TIME']),
            'lastupdate' => tx_rnbase_util_Dates::datetime_tstamp2mysql($GLOBALS['EXEC_TIME']),
            'prefer' => (int) $prefer,
            'recid' => $uid,
            'tablename' => $tableName,
            'data' => false !== $data ? (is_array($data) ? serialize($data) : $data) : '',
            'resolver' => !empty($resolver) ? $resolver : '',
        ];

        return $record;
    }

    /**
     * Add a single database record to search index.
     *
     * @param string $tableName
     * @param int    $uid
     * @param bool   $prefer
     * @param string $resolver  class name of record resolver
     * @param array  $data
     * @param array  $options
     *
     * @return bool true if record was successfully spooled
     */
    public function addRecordToIndex($tableName, $uid, $prefer = false, $resolver = false, $data = false, array $options = [])
    {
        if (empty($uid) || empty($tableName)) {
            tx_rnbase_util_Logger::warn(
                'Could not add record to index. No table or uid given.',
                'mksearch',
                [
                    'tablename' => '['.gettype($tableName).'] '.$tableName,
                    'uid' => '['.gettype($uid).'] '.$uid,
                    'trace' => tx_rnbase_util_Debug::getDebugTrail(),
                ]
            );

            return false;
        }

        $record = $this->buildRecordForIndex($tableName, $uid, $prefer, $resolver, $data, $options);

        if (!is_array($record)) {
            return;
        }

        $qid = $this->getDatabaseConnection()->doInsert(self::$queueTable, $record);
        if (tx_rnbase_util_Logger::isDebugEnabled()) {
            tx_rnbase_util_Logger::debug('New record to be indexed added to queue.', 'mksearch', ['queue-id' => $qid, 'tablename' => $tableName, 'recid' => $uid]);
        }

        return $qid > 0;
    }

    /**
     * @return Tx_Rnbase_Database_Connection
     */
    protected function getDatabaseConnection()
    {
        return Tx_Rnbase_Database_Connection::getInstance();
    }

    /**
     * Add single database records to search index.
     *
     * @param array $records array('tablename' => 'required', 'uid' => 'required', 'preferer' => 'optional', 'resolver' => 'optional', 'data' => 'optional');
     * @param array $options
     *
     * @return bool true if record was successfully spooled
     */
    public function addRecordsToIndex(array $records, array $options = [])
    {
        if (empty($records)) {
            return true;
        }
        $sqlValues = [];
        $count = 0;
        // build records
        foreach ($records as &$record) {
            $tableName = $record['tablename'];
            $uid = $record['uid'];
            // only if table and uid exists
            if (empty($tableName) || empty($uid)) {
                continue;
            }
            $prefer = isset($record['preferer']) ? $record['preferer'] : false;
            $resolver = isset($record['resolver']) ? $record['resolver'] : false;
            $data = isset($record['data']) ? $record['data'] : false;
            // build record, inclusiv existing check
            $record = $this->buildRecordForIndex($tableName, $uid, $prefer, $resolver, $data, $options);

            // ready to insert
            if (is_array($record)) {
                // quote and escape values
                $record = $this->getDatabaseConnection()->fullQuoteArray($record, self::$queueTable);
                // build the query part
                $sqlValues[] = '('.implode(',', $record).')';
                // insert max. 500 items
                ++$count;
                if ($count >= 500) {
                    // do import
                    $this->doInsertRecords($sqlValues);
                    // reset
                    $count = 0;
                    $sqlValues = [];
                }
            }
        }

        return $this->doInsertRecords($sqlValues);
    }

    /**
     * Adds models to the indexing queue.
     *
     * @param array:Tx_Rnbase_Domain_Model_DomainInterface $models
     * @param Traversable|array                            $options
     */
    public function addModelsToIndex(
        $models,
        array $options = []
    ) {
        if (!(is_array($models) || $models instanceof Traversable)) {
            throw new Exception('Argument 1 passed to'.__METHOD__.'() must be of the type array or Traversable.');
        }

        $prefer = isset($options['preferer']) ? $options['preferer'] : false;
        $resolver = isset($options['resolver']) ? $options['resolver'] : false;
        $data = isset($options['data']) ? $options['data'] : false;
        /* @var $model Tx_Rnbase_Domain_Model_DomainInterface */
        foreach ($models as $model) {
            // only rnbase models with uid and tablename supported!
            if (!$model instanceof Tx_Rnbase_Domain_Model_DomainInterface
                // @TODO @deprecated fallback for old rnbase models versions
                && !$model instanceof Tx_Rnbase_Domain_Model_RecordInterface
            ) {
                continue;
            }
            $this->addRecordToIndex(
                $model->getTableName(),
                $model->getUid(),
                $prefer,
                $resolver,
                $data,
                $options
            );
        }
    }

    /**
     * Does the insert.
     *
     * @param $sqlValues $sqlValues
     *
     * @return bool
     */
    protected function doInsertRecords(array $sqlValues)
    {
        $insert = 'INSERT INTO '.self::$queueTable.'(cr_date,lastupdate,prefer,recid,tablename,data,resolver)';

        // no inserts found
        if (empty($sqlValues)) {
            return true;
        }

        // build query string
        $sqlQuery = $insert." VALUES \r\n".implode(", \r\n", $sqlValues).';';
        $this->getDatabaseConnection()->doQuery($sqlQuery);
        if (tx_rnbase_util_Logger::isDebugEnabled()) {
            tx_rnbase_util_Logger::debug('New records to be indexed added to queue.', 'mksearch', ['sqlQuery' => $sqlQuery]);
        }

        return true;
    }

    /**
     * Count all items in queue with state "being_indexed".
     *
     * @param string $tablename
     *
     * @return mixed
     */
    public function countItemsInQueueBeingIndexed($tablename = '')
    {
        $whereCondition = 'deleted=0 AND being_indexed=1';

        return $this->countItemsInQueue($tablename, $whereCondition);
    }

    /**
     * Count all items in queue.
     *
     * @param string $tablename
     * @param string $condition
     *
     * @return mixed
     */
    public function countItemsInQueue($tablename = '', $condition = 'deleted=0')
    {
        $options = [];
        $options['count'] = 1;
        $options['where'] = $condition;
        if (strcmp($tablename, '')) {
            $fullQuoted = $this->getDatabaseConnection()->fullQuoteStr($tablename, self::$queueTable);
            $options['where'] .= ($tablename ? ' AND tablename='.$fullQuoted : '');
        }
        $options['enablefieldsoff'] = 1;

        $data = $this->getDatabaseConnection()->doSelect('count(*) As cnt', self::$queueTable, $options);

        return $data[0]['cnt'];
    }

    /**
     * Trigger indexing from indexing queue.
     *
     * @param array $config
     *                      no:     Number of items to be indexed
     *                      pid:    trigger only records for this pageid
     *
     * @return array
     */
    public function triggerQueueIndexing($config = [])
    {
        //checks whether items with state "being_indexed" got stuck in queue
        if ($this->countItemsInQueueBeingIndexed() > 0) {
            $this->resetOldBeingIndexedEntries();
        }

        if (!is_array($config)) {
            $config = ['limit' => $config];
        }
        $options = [];
        $options['orderby'] = 'prefer desc, lastupdate asc';
        $options['limit'] = isset($config['limit']) ? (int) $config['limit'] : 100;
        $options['where'] = 'deleted=0 AND being_indexed=0';
        $options['enablefieldsoff'] = 1;

        $data = $this->getDatabaseConnection()->doSelect('*', self::$queueTable, $options);

        // Nothing found in queue? Stop
        if (empty($data)) {
            return 0;
        }

        $uids = [];
        $rows = [];
        foreach ($data as $queue) {
            $uids[] = $queue['uid'];
            // daten sammeln
            $rows[$queue['tablename']][] = $queue['recid'];
        }

        $this->getDatabaseConnection()->doUpdate(
            self::$queueTable,
            'uid IN ('.implode(',', $uids).')',
            [
                'being_indexed' => 1,
                'lastupdate' => date('Y-m-d H:i:s'),
            ]
        );

        // Trigger update for the found items
        if (!$this->executeQueueData($data, $config)) {
            return [];
        }

        if (1 != $GLOBALS['TYPO3_CONF_VARS']['MKSEARCH_testmode']) {
            $ret = Tx_Rnbase_Database_Connection::getInstance()->doUpdate(
                self::$queueTable,
                'uid IN ('.implode(',', $uids).')',
                ['deleted' => 1, 'being_indexed' => 0, 'lastupdate' => date('Y-m-d H:i:s')]
            );
            $this->deleteOldQueueEntries();
            tx_rnbase_util_Logger::info(
                'Indexing run finished with '.$ret.' items executed.',
                'mksearch',
                ['data' => $data]
            );
        } else {
            tx_rnbase_util_Logger::info(
                'Indexing run finished in test mode. Queue not deleted!',
                'mksearch',
                ['data' => $data]
            );
        }

        return $rows;
    }

    /**
     * Abarbeitung der Indizierungs-Queue.
     *
     * @param array $data   records aus der Tabelle tx_mksearch_queue
     * @param array $config
     *                      pid:    trigger only records for this pageid
     */
    private function executeQueueData($data, array $config = [])
    {
        self::setSignalThatIndexingIsInProgress();

        // alle indexer fragen oder nur von der aktuellen pid?
        if ($config['pid']) {
            $indices = $this->getByPageId($config['pid']);
        } else {
            $indices = $this->findAll();
        }

        tx_rnbase_util_Logger::debug('[INDEXQUEUE] Found '.count($indices).' indices for update', 'mksearch');

        try {
            // Loop through all active indices, collecting all configurations
            foreach ($indices as $index) {
                /* @var $index tx_mksearch_model_internal_Index */
                tx_rnbase_util_Logger::debug('[INDEXQUEUE] Next index is '.$index->getTitle(), 'mksearch');
                // Container for all documents to be indexed / deleted
                $indexDocs = [];
                $searchEngine = tx_mksearch_util_ServiceRegistry::getSearchEngine($index);

                $indexConfig = $index->getIndexerOptions();
                // Ohne indexConfig kann nichts indiziert werden
                if (!$indexConfig) {
                    tx_rnbase_util_Logger::notice(
                        '[INDEXQUEUE] No indexer config found! Re-check your settings in mksearch BE-Module!',
                        'mksearch',
                        ['Index' => $index->getTitle().' ('.$index->getUid().')', 'indexerClass' => get_class($index), 'indexdata' => $data]
                    );
                    continue; // Continue with next index
                }

                if (tx_rnbase_util_Logger::isDebugEnabled()) {
                    tx_rnbase_util_Logger::debug('[INDEXQUEUE] Config for index '.$index->getTitle().' found.', 'mksearch', [$indexConfig]);
                }

                // Jetzt die Datensätze durchlaufen (Könnte vielleicht auch als äußere Schleife erfolgen...)
                foreach ($data as $queueRecord) {
                    // Zuerst laden wir den Resolver
                    $resolverClazz = $queueRecord['resolver'] ? $queueRecord['resolver'] : 'tx_mksearch_util_ResolverT3DB';
                    $resolver = tx_rnbase::makeInstance($resolverClazz);
                    try {
                        $dbRecords = $resolver->getRecords($queueRecord);
                        foreach ($dbRecords as $record) {
                            // Diese Records müssen jetzt den jeweiligen Indexern übegeben werden.
                            foreach ($this->getIndexersForTable($index, $queueRecord['tablename']) as $indexer) {
                                //foreach (tx_mksearch_util_Config::getIndexersForTable($queueRecord['tablename']) as $indexer) {
                                if (!$indexer) {
                                    continue;
                                } // Invalid indexer
                                // Collect all index documents
                                list($extKey, $contentType) = $indexer->getContentType();
                                //there can be more than one config for the current indexer
                                //so we execute the indexer with each config that was found.
                                //when one element (tt_content) is indexed by let's say tow indexer configs which
                                //aim to the same index than you should take care that the element
                                //isn't taken by both indexer configs as the doc of the element for
                                //the first config will be overwritten by the second one
                                foreach ($indexConfig[$extKey.'.'][$contentType.'.'] as $aConfigByContentType) {
                                    // config mit der default config mergen, falls vorhanden
                                    if (is_array($indexConfig['default.'][$extKey.'.'][$contentType.'.'])) {
                                        $aConfigByContentType = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
                                            $indexConfig['default.'][$extKey.'.'][$contentType.'.'],
                                            $aConfigByContentType
                                        );
                                    }

                                    $aConfigByContentType['queueRecord'] = $queueRecord;

                                    // indizieren!
                                    $doc = $indexer->prepareSearchData(
                                        $queueRecord['tablename'],
                                        $record,
                                        $searchEngine->makeIndexDocInstance($extKey, $contentType),
                                        $aConfigByContentType
                                    );
                                    if ($doc) {
                                        if ($GLOBALS['TCA'][$queueRecord['tablename']]['ctrl']['versioningWS']) {
                                            $this->deleteDocumentIfNotCorrectWorkspace(
                                                $aConfigByContentType,
                                                $record,
                                                $doc
                                            );
                                        }

                                        //add fixed_fields from indexer config if defined
                                        $doc = $this->addFixedFields($doc, $aConfigByContentType);

                                        try {
                                            $indexDocs[$doc->getPrimaryKey(true)] = $doc;
                                        } catch (Exception $e) {
                                            tx_rnbase_util_Logger::warn('[INDEXQUEUE] Invalid document returned from indexer.', 'mksearch', ['Indexer class' => get_class($indexer), 'record' => $record]);
                                        }
                                    }
                                }
                            }
                        }
                    } catch (Exception $e) {
                        tx_rnbase_util_Logger::warn(
                            '[INDEXQUEUE] Error processing queue item '.$queueRecord['uid'],
                            'mksearch',
                            [
                                'Exception' => $e->getMessage(),
                                'On' => $e->getFile().'#'.$e->getLine(),
                                'Queue-Item' => $queueRecord,
                                'Trace' => $e->getTraceAsString(),
                            ]
                        );
                    }
                }
                // Finally, actually do the index update, if there is sth. to do:
                if (count($indexDocs)) {
                    $searchEngine->openIndex($index, true);
                    foreach ($indexDocs as $doc) {
                        try {
                            if ($doc->getDeleted()) {
                                $key = $doc->getPrimaryKey();
                                $searchEngine->indexDeleteByContentUid($key['uid']->getValue(), $key['extKey']->getValue(), $key['contentType']->getValue());
                            } else {
                                $searchEngine->indexUpdate($doc);
                            }
                        } catch (Exception $e) {
                            tx_rnbase_util_Logger::fatal(
                                '[INDEXQUEUE] Fatal error processing search document!',
                                'mksearch',
                                [
                                    'Exception' => $e->getMessage(),
                                    'document' => $doc->__toString(),
                                    // getData liefert die IndexerField Objekte.
                                    // Diese wandeln wir in Strings um, da sonst die Objekte
                                    // nicht wiederhergesetllt werden können und Serialisiert auch zu viel Speicher rauben!
                                    'data' => array_map('strval', $doc->getData()),
                                ]
                            );
                        }
                    }
                    $searchEngine->commitIndex();
                    //shall something be done after indexing?
                    $searchEngine->postProcessIndexing($index);
                    //that's it
                    $searchEngine->closeIndex();
                }
            }
        } catch (Exception $e) {
            tx_rnbase_util_Logger::fatal(
                '[INDEXQUEUE] Fatal error processing queue occured! The queue is left as it is so the indexing can be tried again.',
                'mksearch',
                [
                    'Exception' => $e->getMessage(),
                    'Queue-Items' => $data,
                ]
            );
            self::removeSignalThatIndexingIsInProgress();

            return false;
        }

        self::removeSignalThatIndexingIsInProgress();

        return true;
    }

    /**
     * @param array                                 $configurationByContentType
     * @param array                                 $record
     * @param tx_mksearch_interface_IndexerDocument $indexDocument
     */
    protected function deleteDocumentIfNotCorrectWorkspace(
        array $configurationByContentType,
        array $record,
        tx_mksearch_interface_IndexerDocument $indexDocument
    ) {
        $workspacesToIndex = $configurationByContentType['workspaceIds'] ?
            Tx_Rnbase_Utility_Strings::trimExplode(',', $configurationByContentType['workspaceIds']) :
            [0];

        $isInIndexableWorkspace = false;
        foreach ($workspacesToIndex as $workspaceId) {
            if ($record['t3ver_wsid'] == $workspaceId) {
                $isInIndexableWorkspace = true;
                break;
            }
        }

        if (!$isInIndexableWorkspace) {
            $indexDocument->setDeleted(true);
        }
    }

    /**
     * Adds fixed fields which are defined in the indexer config
     * if none are defined we have nothing to do.
     *
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param array                                 $options
     *
     * @return tx_mksearch_interface_IndexerDocument
     */
    protected function addFixedFields(tx_mksearch_interface_IndexerDocument $indexDoc, $options)
    {
        $aFixedFields = $options['fixedFields.'];
        //without config there is nothing to do
        if (empty($aFixedFields)) {
            return $indexDoc;
        }

        foreach ($aFixedFields as $sFixedFieldKey => $mFixedFieldValue) {
            //config is something like
            //site_area{
            // 0 = first
            // 1 = second
            //}
            if (is_array($mFixedFieldValue)) {
                //if we have an array we have to delete the
                //trailing dot of the key name because this
                //seems senseless to add
                $sFixedFieldKey = substr($sFixedFieldKey, 0, strlen($sFixedFieldKey) - 1);
            }
            //else the config is something like
            //site_area = first
            $indexDoc->addField($sFixedFieldKey, $mFixedFieldValue);
        }

        return $indexDoc;
    }

    /**
     * Returns all indexers for an index and a specific tablename.
     *
     * @param tx_mksearch_model_internal_Index $index
     * @param string                           $tablename
     */
    private function getIndexersForTable($index, $tablename)
    {
        $ret = [];
        $indexers = tx_mksearch_util_Config::getIndexersForTable($tablename);
        foreach ($indexers as $indexer) {
            if ($this->isIndexerDefined($index, $indexer)) {
                $ret[] = $indexer;
            }
        }

        return $ret;
    }

    /**
     * Clear indexing queue for the given table.
     *
     * @param string $table
     * @param array  $options
     */
    public static function clearIndexingQueueForTable($table)
    {
        $database = Tx_Rnbase_Database_Connection::getInstance();
        $fullQuoted = $database->fullQuoteStr($table, self::$queueTable);

        return $database->doDelete(self::$queueTable, 'tablename='.$fullQuoted);
    }

    /**
     * Reset indexing queue for the given table.
     *
     * Old entries are deleted before all (valid) entries of the
     * given table name are inserted.
     *
     * @param string $table
     * @param array  $options
     */
    public static function resetIndexingQueueForTable($table, array $options)
    {
        self::clearIndexingQueueForTable($table);

        $database = Tx_Rnbase_Database_Connection::getInstance();

        $resolver = tx_mksearch_util_Config::getResolverForDatabaseTable($table);
        $resolver = count($resolver) ? $resolver['className'] : '';

        $fullQuoted = $database->fullQuoteStr($table, self::$queueTable);
        $uidName = isset($options['uidcol']) ? $options['uidcol'] : 'uid';
        $from = isset($options['from']) ? $options['from'] : $table;
        $where = isset($options['where']) ? ' WHERE '.$options['where'] : '';

        $query = 'INSERT INTO '.self::$queueTable.'(tablename, recid, resolver) ';
        $query .= 'SELECT DISTINCT '.$fullQuoted.', '.$uidName.
            ', CONCAT(\''.$resolver.'\') FROM '.$from.$where;

        if ($options['debug']) {
            tx_rnbase_util_Debug::debug(
                $query,
                'class.tx_mksearch_srv_Search.php : '.__LINE__
            );
        }
        $database->doQuery($query);
    }

    /**
     * Check and reset items in state "being_indexed".
     *
     * @return int
     */
    protected function resetOldBeingIndexedEntries()
    {
        $timeLimit = $this->getMinutesToKeepBeingIndexedEntries();
        $resetCount = $this->resetItemsBeingIndexed($timeLimit);
        if ($resetCount > 0) {
            tx_rnbase_util_Logger::warn(
                'Items in queue are resetted because they are in state "being_indexed" '.
                'longer than the configured amount of time. Check that, if it occurs multiple times.',
                'mksearch',
                [
                    'itemResetCount' => $resetCount,
                    'configuredTimeLimitInMin' => $timeLimit,
                ]
            );
        }

        return $resetCount;
    }

    /**
     * Reset items with state "being_indexed" older than given time period in minutes.
     *
     * @param int $olderThanMinutes
     *
     * @return int
     */
    public function resetItemsBeingIndexed($olderThanMinutes = 0)
    {
        $where = sprintf(
            'being_indexed = 1 AND lastupdate < NOW() - INTERVAL %d MINUTE',
            $olderThanMinutes
        );

        return Tx_Rnbase_Database_Connection::getInstance()->doUpdate(
            self::$queueTable,
            $where,
            [
                'being_indexed' => 0,
                'lastupdate' => date('Y-m-d H:i:s'),
            ]
        );
    }

    public function getRandomSolrIndex()
    {
        $fields['INDX.engine'][OP_EQ] = self::TYPE_SOLR;
        $options['limit'] = 1;

        $indexes = $this->search($fields, $options);

        return !empty($indexes[0]) ? $indexes[0] : null;
    }

    protected function deleteOldQueueEntries()
    {
        $this->getDatabaseUtility()->doDelete(
            self::$queueTable,
            'deleted = 1 AND cr_date < NOW() - '.$this->getSecondsToKeepQueueEntries()
        );
    }

    /**
     * @return Tx_Rnbase_Database_Connection
     */
    protected function getDatabaseUtility()
    {
        return tx_rnbase::makeInstance('Tx_Rnbase_Database_Connection');
    }

    /**
     * @return string
     */
    protected function getSecondsToKeepQueueEntries()
    {
        $secondsToKeepQueueEntries = tx_rnbase_configurations::getExtensionCfgValue(
            'mksearch',
            'secondsToKeepQueueEntries'
        );
        $sevenDaysInSeconds = 604800;

        return $secondsToKeepQueueEntries ? $secondsToKeepQueueEntries : $sevenDaysInSeconds;
    }

    /**
     * Returns the amount of minutes to keep being indexed entries in queue
     * before resetting them.
     *
     * @return int
     */
    protected function getMinutesToKeepBeingIndexedEntries()
    {
        $minutesToKeepBeingIndexedEntries = tx_rnbase_configurations::getExtensionCfgValue(
            'mksearch',
            'minutesToKeepBeingIndexedEntries'
        );

        return $minutesToKeepBeingIndexedEntries ?: 60;
    }

    private static function setSignalThatIndexingIsInProgress()
    {
        self::$indexingInProgress = true;
    }

    /**
     * @return bool
     */
    public static function isIndexingInProgress()
    {
        return self::$indexingInProgress;
    }

    private static function removeSignalThatIndexingIsInProgress()
    {
        self::$indexingInProgress = false;
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/service/internal/class.tx_mksearch_service_internal_Index.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/service/internal/class.tx_mksearch_service_internal_Index.php'];
}
