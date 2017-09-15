<?php
namespace DMK\Mksearch\Index\Queue;

/*
 * This script is part of the TYPO3 project - inspiring people to share! *
 * *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by *
 * the Free Software Foundation. *
 * *
 * This script is distributed in the hope that it will be useful, but *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN- *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General *
 * Public License for more details. *
 */

use \tx_rnbase_util_Logger as tx_rnbase_util_Logger;
use \tx_rnbase as tx_rnbase;
use \tx_mksearch_util_ServiceRegistry as tx_mksearch_util_ServiceRegistry;
use \tx_rnbase_util_Arrays as tx_rnbase_util_Arrays;

/**
 * Responsible to process items in mksearch indexing queue.
 */
class Processor
{
    const TABLE_QUEUE = 'tx_mksearch_queue';

    /**
     * Trigger indexing from indexing queue
     *
     * @param array $config
     *            no: Number of items to be indexed
     *            pid: trigger only records for this pageid
     * @return array
     */
    public function triggerQueueIndexing(\tx_mksearch_service_internal_Index $indexSrv, $config = array())
    {
        if (! is_array($config)) {
            $config = array(
                'limit' => $config
            );
        }
        
        $data = $this->retrieveQueueItems($config);
        // Nothing found in queue? Stop
        if (empty($data)) {
            return 0;
        }
        
        // Trigger update for the found items
        if (! $this->executeQueueData($data, $config, $indexSrv)) {
            return array();
        }

        return $this->finalizeIndexingRun($data);
    }
    /**
     * Delete processed 
     * @param unknown $data
     */
    protected function finalizeIndexingRun($data)
    {   
        $uids = array();
        $rows = array();
        foreach ($data as $queue) {
            $uids[] = $queue['uid'];
            // daten sammeln
            $rows[$queue['tablename']][] = $queue['recid'];
        }
        $ret = $this->deleteQueueEntries($uids);
        $this->deleteOldQueueEntries();
        $this->log('info', 'Indexing run finished with ' . $ret . ' items executed.', 'mksearch', array(
            'data' => $data
        ));
    }

    protected function deleteQueueEntries($uids)
    {
        return $this->getDatabaseConnection()->doUpdate(self::TABLE_QUEUE, 'uid IN (' . implode(',', $uids) . ')', array(
            'deleted' => 1
        ));
    }
    protected function log($level, $msg, $ext, $data = [])
    {
        tx_rnbase_util_Logger::$level($msg, $ext, $data);
        
    }
    /**
     * Abarbeitung der Indizierungs-Queue
     * @param   array $data records aus der Tabelle tx_mksearch_queue
     * @param   array   $config
     *                      pid:    trigger only records for this pageid
     * @return  void
     */
    private function executeQueueData($data, array $config, \tx_mksearch_service_internal_Index $indexSrv)
    {
        // alle indexer fragen oder nur von der aktuellen pid?
        if (isset($config['pid']) && $config['pid']) {
            $indices = $indexSrv->getByPageId($config['pid']);
        } else {
            $indices = $indexSrv->findAll();
        }
        
        $this->log('debug', '[INDEXQUEUE] Found '.count($indices) . ' indices for update', 'mksearch');
        
        try {
            // Loop through all active indices, collecting all configurations
            foreach ($indices as $index) {
                /* @var $index tx_mksearch_model_internal_Index */
                tx_rnbase_util_Logger::debug('[INDEXQUEUE] Next index is '.$index->getTitle(), 'mksearch');
                // Container for all documents to be indexed / deleted
                $indexDocs = array();
                $searchEngine = tx_mksearch_util_ServiceRegistry::getSearchEngine($index);
                
                $indexConfig = $index->getIndexerOptions();
                // Ohne indexConfig kann nichts indiziert werden
                if (!$indexConfig) {
                    tx_rnbase_util_Logger::notice(
                        '[INDEXQUEUE] No indexer config found! Re-check your settings in mksearch BE-Module!',
                        'mksearch',
                        array('Index' => $index->getTitle() . ' ('.$index->getUid().')', 'indexerClass' => get_class($index))
                        );
                    continue; // Continue with next index
                }
                
                if (tx_rnbase_util_Logger::isDebugEnabled()) {
                    tx_rnbase_util_Logger::debug('[INDEXQUEUE] Config for index '.$index->getTitle().' found.', 'mksearch', array($indexConfig));
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
                                            \tx_rnbase_util_Logger::warn('[INDEXQUEUE] Invalid document returned from indexer.', 'mksearch', array('Indexer class' => get_class($indexer), 'record' => $record));
                                        }
                                    }
                                }
                            }
                        }
                    } catch (Exception $e) {
                        \tx_rnbase_util_Logger::warn('[INDEXQUEUE] Error processing queue item ' . $queueRecord['uid'], 'mksearch', array('Exception' => $e->getMessage(), 'Queue-Item' => $queueRecord));
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
                                array(
                                    'Exception' => $e->getMessage(),
                                    'document' => $doc->__toString(),
                                    // getData liefert die IndexerField Objekte.
                                    // Diese wandeln wir in Strings um, da sonst die Objekte
                                    // nicht wiederhergesetllt werden können und Serialisiert auch zu viel Speicher rauben!
                                    'data' => array_map('strval', $doc->getData()),
                                )
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
            \tx_rnbase_util_Logger::fatal(
                '[INDEXQUEUE] Fatal error processing queue occured! The queue is left as it is so the indexing can be tried again.',
                'mksearch',
                array(
                    'Exception' => $e->getMessage(),
                    'Queue-Items' => $data
                )
                );
            
            return false;
        }
        
        return true;
    }
    
    /**
     * @param array $configurationByContentType
     * @param array $record
     * @param tx_mksearch_interface_IndexerDocument $indexDocument
     *
     * @return void
     */
    protected function deleteDocumentIfNotCorrectWorkspace(
        array $configurationByContentType,
        array $record,
        \tx_mksearch_interface_IndexerDocument $indexDocument
        ) {
            $workspacesToIndex = $configurationByContentType['workspaceIds'] ?
            \Tx_Rnbase_Utility_Strings::trimExplode(',', $configurationByContentType['workspaceIds']) :
            array(0);
            
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
     * Hard delete old processed queue items
     * @return void
     */
    protected function deleteOldQueueEntries()
    {
        $this->getDatabaseConnection()->doDelete(
            self::TABLE_QUEUE,
            'deleted = 1 AND cr_date < NOW() - ' . $this->getSecondsToKeepQueueEntries()
            );
    }

    /**
     * 
     * @return number|mixed|boolean
     */
    protected function getSecondsToKeepQueueEntries()
    {
        $secondsToKeepQueueEntries = \tx_rnbase_configurations::getExtensionCfgValue(
            'mksearch',
            'secondsToKeepQueueEntries'
            );
        $thirtyDaysInSeconds = 2592000;
        
        return $secondsToKeepQueueEntries ? $secondsToKeepQueueEntries : $thirtyDaysInSeconds;
    }
    
    /**
     * @return \Tx_Rnbase_Database_Connection
     */
    protected function getDatabaseConnection()
    {
        return \Tx_Rnbase_Database_Connection::getInstance();
    }

    protected function retrieveQueueItems($config = array())
    {
        $options = array();
        $options['orderby'] = 'prefer desc, cr_date asc, uid asc';
        $options['limit'] = isset($config['limit']) ? (int) $config['limit'] : 100;
        $options['where'] = 'deleted=0';
        $options['enablefieldsoff'] = 1;
        
        return $this->getDatabaseConnection()->doSelect('*', self::TABLE_QUEUE, $options);
        
    }
}
