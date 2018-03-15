<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 René Nitzche <dev@dmk-ebusiness.de>
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
use Elastica\Client;
use Elastica\Exception\ClientException;
use Elastica\Index;
use Elastica\Document;
use Elastica\Bulk\Action;
use Elastica\Result;
use Elastica\Search;
use Elastica\ResultSet;
use Elastica\Query;

tx_rnbase::load('tx_mksearch_interface_SearchEngine');
tx_rnbase::load('tx_rnbase_configurations');
tx_rnbase::load('tx_mksearch_util_Misc');
tx_rnbase::load('tx_mksearch_service_engine_SolrException');
tx_rnbase::load('tx_rnbase_util_Logger');
tx_rnbase::load('Tx_Rnbase_Service_Base');

/**
 * Service "ElasticSearch search engine" for the "mksearch" extension.
 *
 * @package     TYPO3
 * @subpackage  tx_mksearch
 */
class tx_mksearch_service_engine_ElasticSearch extends Tx_Rnbase_Service_Base implements tx_mksearch_interface_SearchEngine
{

    /**
     * Index used for searching and indexing
     *
     * @var Index
     */
    private $index = null;

    /**
     * @var tx_mksearch_model_internal_Index
     */
    private $mksearchIndexModel = null;

    /**
     * @var string
     */
    private $credentialsString = '';

    /**
     * Name of the currently open index
     *
     * @var string
     */
    private $indexName;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        spl_autoload_register(function ($class) {
            if (strpos($class, 'Elastica') !== false || strpos($class, 'Psr') !== false || strpos($class, 'Elasticsearch') !== false) {
                $class = str_replace('\\', '/', $class);
                $filePath = tx_rnbase_util_Extensions::extPath('mksearch').'lib/' . $class . '.php';
                if (file_exists($filePath)) {
                    require_once($filePath);
                }
            }
        });
    }

    /**
     * @param array $credentials
     * @throws Exception
     */
    protected function initElasticSearchConnection(array $credentials)
    {
        $this->index = $this->getElasticaIndex($credentials);
        if (!$this->index->exists()) {
            $this->index->create();
        }
        $this->index->open();

        if (!$this->isServerAvailable()) {
            // wir rufen die Methode mit call_user_func_array auf, da sie
            // statisch ist, womit wir diese nicht mocken könnten
            call_user_func_array(
                array($this->getLogger(), 'fatal'),
                array(
                    'ElasticSearch service not responding.', 'mksearch',
                    array($credentials)
                )
            );
            throw new ClientException('ElasticSearch service not responding.');
        }
    }

    /**
     * @param array $credentials
     *
     * @return Index
     */
    protected function getElasticaIndex($credentials)
    {
        $elasticaClient =  new Client($credentials);

        return $elasticaClient->getIndex($this->getOpenIndexName());
    }

    /**
     *
     * @return bool
     */
    protected function isServerAvailable()
    {
        $response = $this->getIndex()->getClient()->getStatus()->getResponse();

        return $response->isOk();
    }

    /**
     * @return string
     */
    protected function getLogger()
    {
        return tx_rnbase_util_Logger;
    }

    /**
     * Search indexed data
     *
     * @param array     $fields
     * @param array     $options
     * @return array[tx_mksearch_model_SearchResult]    search results
     *
     * @todo support für alle optionen von elasticsearch
     * @see http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html
     */
    public function search(array $fields = array(), array $options = array())
    {
        $startTime = microtime(true);
        $result = array();

        try {
            /* @var $searchResult ResultSet */
            $searchResult = $this->getIndex()->search(
                $this->getElasticaQuery($fields, $options),
                $this->getOptionsForElastica($options)
            );

            $this->checkResponseOfSearchResult($searchResult);
            $items = $this->getItemsFromSearchResult($searchResult);

            $lastRequest = $this->getIndex()->getClient()->getLastRequest();
            $result['searchUrl'] = $lastRequest->getPath();
            $result['searchQuery'] = $lastRequest->getQuery();
            $result['searchTime'] = (microtime(true) - $startTime) . ' ms';
            $result['queryTime'] = $searchResult->getTotalTime() . ' ms';
            $result['numFound'] = $searchResult->getTotalHits();
            $result['error'] = $searchResult->getResponse()->getError();
            $result['items'] = $items;

            if ($options['debug']) {
                tx_rnbase_util_Debug::debug(
                    array('options' => $options, 'result' => $result),
                    __METHOD__. ' Line: ' . __LINE__
                );
            }
        } catch (Exception $e) {
            $message =    'Exception caught from ElasticSearch: ' . $e->getMessage();
            throw new RuntimeException($message);
        }

        return $result;
    }

    /**
     * @param array $fields
     * @param array $options
     * @return Query
     */
    protected function getElasticaQuery(array $fields, array $options)
    {
        $elasticaQuery = Query::create($fields['term']);

        $elasticaQuery = $this->handleSorting($elasticaQuery, $options);

        return $elasticaQuery;
    }

    /**
     *
     * @param Query $elasticaQuery
     * @param array $options
     * @return Query
     */
    private function handleSorting(Query $elasticaQuery, array $options)
    {
        if ($options['sort']) {
            list($field, $order) = tx_rnbase_util_Strings::trimExplode(' ', $options['sort'], true);
            $elasticaQuery->addSort(
                array(
                    $field => array(
                        'order' => $order,
                    )
                )
            );
        }

        return $elasticaQuery;
    }

    /**
     *
     * @param ResultSet $searchResult
     * @return void
     * @throws RuntimeException
     */
    protected function checkResponseOfSearchResult(ResultSet $searchResult)
    {
        $httpStatus = $searchResult->getResponse()->getStatus();
        if ($httpStatus != 200) {
            $lastRequest = $this->getIndex()->getClient()->getLastRequest();
            $message =    'Error requesting ElasticSearch. HTTP status: ' . $httpStatus .
                        '; Path: ' . $lastRequest->getPath() .
                        '; Query: ' . $lastRequest->getQuery();
            throw new RuntimeException($message);
        }
    }

    /**
     * @param ResultSet $searchResult
     * @return multitype:Ambigous <object, boolean, \TYPO3\CMS\Core\Utility\array<\TYPO3\CMS\Core\SingletonInterface>, mixed, \TYPO3\CMS\Core\SingletonInterface, \TYPO3\CMS\Core\Utility\mixed, unknown>
     */
    protected function getItemsFromSearchResult(ResultSet $searchResult)
    {
        $items = array();
        if ($elasticSearchResult = $searchResult->getResults()) {
            /* @var $item Result */
            foreach ($elasticSearchResult as $item) {
                $items[] = tx_rnbase::makeInstance(
                    'tx_mksearch_model_SearchHit',
                    $item->getData()
                );
            }
        }

        return $items;
    }

    /**
     *
     * @param array $options
     *
     * @return array
     */
    protected function getOptionsForElastica(array $options)
    {
        $elasticaOptions = array();

        foreach ($options as $key => $value) {
            $key = $this->remapElasticaOptionKey($key);
            switch ($key) {
                case Search::OPTION_SEARCH_TYPE:
                case Search::OPTION_ROUTING:
                case Search::OPTION_PREFERENCE:
                case Search::OPTION_VERSION:
                case Search::OPTION_TIMEOUT:
                case Search::OPTION_FROM:
                case Search::OPTION_SIZE:
                case Search::OPTION_SCROLL:
                case Search::OPTION_SCROLL_ID:
                case Search::OPTION_SEARCH_TYPE_SUGGEST:
                // explain und limit wird von Elastica selbst remapped
                case 'explain':
                case 'limit':
                    $elasticaOptions[$key] = $value;
            }
        }

        return $elasticaOptions;
    }

    /**
     *
     * @param string $optionKey
     *
     * @return string
     */
    private function remapElasticaOptionKey($optionKey)
    {
        switch ($optionKey) {
            case 'debug':
                $optionKey = 'explain';
                break;
            case 'offset':
                $optionKey = Search::OPTION_FROM;
                break;
        }

        return $optionKey;
    }

    /**
     * Get a document from index
     * @param string $uid
     * @param string $extKey
     * @param string $contentType
     * @return tx_mksearch_model_SearchHit
     */
    public function getByContentUid($uid, $extKey, $contentType)
    {
    }


    /**
     * Return name of the index currently opened
     *
     * @return string
     */
    public function getOpenIndexName()
    {
        return $this->indexName;
    }

    /**
     * Open an index
     *
     * @param tx_mksearch_model_internal_Index  $index Instance of the index to open
     * @param bool      $forceCreation  Force creation of index if it doesn't exist
     * @return void
     */
    public function openIndex(
        tx_mksearch_model_internal_Index $index,
        $forceCreation = false
    ) {
        $credentialsForElastica = $this->getElasticaCredentialsFromCredentialsString(
            $index->getCredentialString()
        );
        $this->initElasticSearchConnection($credentialsForElastica);
    }

    /**
     * Der String ist semikolon separiert. der erste Teil ist der Index,
     * alle weiteren sind die Server. Die Credentials für die Server
     * werden kommasepariert erwartet wobei erst host, dann port dann url pfad
     *
     * @param string $credentialString
     * @return multitype:unknown Ambigous <unknown>
     */
    protected function getElasticaCredentialsFromCredentialsString($credentialString)
    {
        $this->credentialsString = $credentialString;
        $serverCredentials = tx_rnbase_util_Strings::trimExplode(';', $credentialString, true);

        $this->indexName = $serverCredentials[0];
        unset($serverCredentials[0]);

        foreach ($serverCredentials as $serverCredential) {
            $credentialsForElastica['servers'][] =
                $this->getElasticaCredentialArrayFromIndexCredentialStringForOneServer(
                    $serverCredential
                );
        }

        return $credentialsForElastica;
    }

    /**
     * @param string $credentialString
     * @return multitype:unknown Ambigous <unknown>
     */
    private function getElasticaCredentialArrayFromIndexCredentialStringForOneServer(
        $credentialString
    ) {
        $serverCredential = tx_rnbase_util_Strings::trimExplode(',', $credentialString);

        return array(
            'host' => $serverCredential[0],
            'port' => $serverCredential[1],
            'path' => $serverCredential[2]
        );
    }

    /**
     * Liefert den Index
     *
     * @return Index
     */
    public function getIndex()
    {
        if (!is_object($this->index)) {
            $this->openIndex($this->mksearchIndexModel);
        }

        return $this->index;
    }

    /**
     * Check if the specified index exists
     *
     * @param string    $name   Name of index
     * @return bool
     */
    public function indexExists($name)
    {
        return $this->getIndex()->getClient()->getStatus()->indexExists($name);
    }

    /**
     * Commit index
     *
     * @return bool success
     */
    public function commitIndex()
    {
        // wird direkt beim Hinzufügen oder Löschen ausgeführt
    }

    /**
     * Close index
     *
     * @return void
     */
    public function closeIndex()
    {
        $this->getIndex()->close();
        unset($this->index);
    }

    /**
     * Delete an entire index
     *
     * @param optional string $name Name of index to delete, if not the open index is meant to be deleted
     * @return void
     */
    public function deleteIndex($name = null)
    {
        if ($name) {
            $this->getIndex()->getClient()->getIndex($name)->delete();
        } else {
            $this->getIndex()->delete();
        }
    }

    /**
     * Optimize index
     *
     * @return void
     */
    public function optimizeIndex()
    {
        $this->getIndex()->optimize();
    }

    /**
     * Replace an index with another.
     *
     * The index to be replaced will be deleted.
     * This actually means that the old's index's directory will be deleted recursively!
     *
     * @param string    $which  Name of index to be replaced i. e. deleted
     * @param string    $by     Name of index which replaces the index named $which
     * @return void
     */
    public function replaceIndex($which, $by)
    {
        //vorerst nichts zu tun
    }

    /**
     * Put a new record into index
     *
     * @param tx_mksearch_interface_IndexerDocument $doc    "Document" to index
     * @return bool     $success
     */
    public function indexNew(tx_mksearch_interface_IndexerDocument $doc)
    {
        $data = array();

        // Primary key data (fields are all scalar)
        $primaryKeyData = $doc->getPrimaryKey();
        foreach ($primaryKeyData as $key => $field) {
            if (!empty($field)) {
                $data[$key] = tx_mksearch_util_Misc::utf8Encode($field->getValue());
            }
        }
        foreach ($doc->getData() as $key => $field) {
            if ($field) {
                $data[$key] = tx_mksearch_util_Misc::utf8Encode($field->getValue());
            }
        }

        $primaryKey = $doc->getPrimaryKey();
        $elasticaDocument = new Document($primaryKey['uid']->getValue(), $data);
        $elasticaDocument->setType(
            $primaryKey['extKey']->getValue() . ':' .
            $primaryKey['contentType']->getValue()
        );

        return $this->getIndex()->addDocuments(array($elasticaDocument))->isOk();
    }

    /**
     * Update or create an index record
     *
     * @param tx_mksearch_interface_IndexerDocument $doc    "Document" to index
     * @return bool     $success
     */
    public function indexUpdate(tx_mksearch_interface_IndexerDocument $doc)
    {
        // ElasticSearch erkennt selbst ob ein Update nötig ist
        return $this->indexNew($doc);
    }

    /**
     * Delete index document specified by content uid
     *
     * @param int       $uid            Unique identifier of data record - unique within the scope of $extKey and $content_type
     * @param string    $extKey         Key of extension the data record belongs to
     * @param string    $contentType    Name of semantic content type
     * @return bool success
     */
    public function indexDeleteByContentUid($uid, $extKey, $contentType)
    {
        $type = $extKey . ':' . $contentType;
        $elasticaDocument = new Document($uid);
        $elasticaDocument->setType($type);

        return $this->getIndex()->deleteDocuments(array($elasticaDocument))->isOk();
    }

    /**
     * Delete index document specified by index id
     *
     * @param string $id
     * @return void
     */
    public function indexDeleteByIndexId($id)
    {
    }

    /**
     * Delete documents specified by raw query
     * @param string $query
     */
    public function indexDeleteByQuery($query, $options = array())
    {
    }

    /**
     * Return an indexer document instance for the given content type
     *
     * @param string    $extKey         Extension key of records to be indexed
     * @param string    $contentType    Content type of records to be indexed
     * @return tx_mksearch_interface_IndexerDocument
     */
    public function makeIndexDocInstance($extKey, $contentType)
    {
        return tx_rnbase::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            $extKey,
            $contentType,
            'tx_mksearch_model_IndexerFieldBase'
        );
    }

    /**
     * Returns a index status object.
     *
     * @return tx_mksearch_util_Status
     */
    public function getStatus()
    {
        /* @var $status tx_mksearch_util_Status */
        $status = tx_rnbase::makeInstance('tx_mksearch_util_Status');

        $id = -1;
        $msg = 'Down. Maybe not started?';
        try {
            if ($this->isServerAvailable()) {
                $id = 1;
                $msg = 'Up and running (Ping time: ' .
                        $this->getIndex()->getClient()->getStatus()->getResponse()->getQueryTime() .
                        ' ms)';
            }
        } catch (Exception $e) {
            $msg = 'Error connecting ElasticSearch: ' . $e->getMessage() . '.';
            $msg .= ' Credentials: ' . $this->credentialsString;
        }

        $status->setStatus($id, $msg);

        return $status;
    }

    /**
     * Set index model with credential data
     *
     * @param tx_mksearch_model_internal_Index  $index Instance of the index to open
     * @return void
     */
    public function setIndexModel(tx_mksearch_model_internal_Index $index)
    {
        $this->mksearchIndexModel = $index;
    }

    /**
     * This function is called for each index after the indexing
     * is done.
     *
     * @param tx_mksearch_model_internal_Index $index
     * @return void
     */
    public function postProcessIndexing(tx_mksearch_model_internal_Index $index)
    {
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/service/engine/class.tx_mksearch_service_engine_ElasticSearch.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/service/engine/class.tx_mksearch_service_engine_ElasticSearch.php']);
}
