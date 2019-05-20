<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Lars Heber <dev@dmk-ebusiness.de>
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

tx_rnbase::load('tx_mksearch_interface_SearchEngine');
tx_rnbase::load('tx_rnbase_configurations');
tx_rnbase::load('tx_rnbase_util_Logger');
tx_rnbase::load('tx_mksearch_service_engine_lucene_DataTypeMapper');
tx_rnbase::load('Tx_Rnbase_Service_Base');

/**
 * Service "ZendLucene search engine" for the "mksearch" extension.
 *
 * @author  Lars Heber <dev@dmk-ebusiness.de>
 */
class tx_mksearch_service_engine_ZendLucene extends Tx_Rnbase_Service_Base implements tx_mksearch_interface_SearchEngine
{
    const FE_GROUP_FIELD = 'fe_group_mi';

    /**
     * Index used for searching and indexing.
     *
     * @var Zend_Search_Lucene_Interface
     */
    private $index;

    /**
     * Name of the currently open index.
     *
     * @var string
     */
    private $indexName;

    /**
     * Reference to index configuration.
     *
     * @var tx_mksearch_model_internal_Index
     */
    private $indexModel;

    /* @var tx_mksearch_service_engine_lucene_DataTypeMapper */
    private $dataTypeMapper;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Explicitely include zend path if necessary
        $zendPath = tx_rnbase_configurations::getExtensionCfgValue('mksearch', 'zendPath');
        $zendPath = tx_rnbase_util_Files::getFileAbsFileName($zendPath);

        $iniPath = get_include_path();
        if (false === strpos($zendPath, $iniPath)) {
            set_include_path($iniPath.PATH_SEPARATOR.$zendPath);
        }
        if (!is_readable($zendPath)) {
            tx_rnbase_util_Logger::fatal('Current path to Zend root does not exist!', 'mksearch', array('Path' => $zendPath));
            throw new Exception('Current path to Zend root does not exist!');
        }

        $autoLoaderPath = rtrim($zendPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'Zend'.DIRECTORY_SEPARATOR.'Loader'.DIRECTORY_SEPARATOR.'Autoloader.php';
        if (!is_readable($autoLoaderPath)) {
            tx_rnbase_util_Logger::fatal('Zend auto loader class not found. Check extension settings!', 'mksearch', array('Path' => $autoLoaderPath));
            throw new Exception('Zend auto loader class not found. Check extension settings! More info in devlog.');
        }

        // Trigger Zend autoloading mechanism
        require_once 'Zend/Loader/Autoloader.php';
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('Zend_');

        // Set utf-8-able analyzer
        // @todo: Make configurable
        Zend_Search_Lucene_Analysis_Analyzer::setDefault(
            tx_rnbase::makeInstance('Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive')
        );
    }

    /**
     * @return tx_mksearch_service_engine_lucene_DataTypeMapper
     */
    protected function getDataTypeMapper()
    {
        if (!is_object($this->dataTypeMapper)) {
            // Der Mapper sollte noch mit einer Config gefüttert werden, aus der er
            // weitere Informationen zu den gewünschten Typen gesetzt bekommt. Das wäre
            // dann eine Art schema.xml für Lucene...
            $data = $this->indexModel->getIndexConfig();
            $mapperCfg = isset($data['lucene.']['schema.']) ? $data['lucene.']['schema.'] : array();
            $this->dataTypeMapper = tx_rnbase::makeInstance('tx_mksearch_service_engine_lucene_DataTypeMapper', $mapperCfg);
        }

        return $this->dataTypeMapper;
    }

    public function getFieldNames($indexed = false)
    {
        $ret = array();
        if (!$this->checkForOpenIndex(false)) {
            return $ret;
        }
        $fieldNames = array_values($this->index->getFieldNames($indexed));
        sort($fieldNames);

        return $fieldNames;
    }

    /**
     * Check if an index was opened.
     *
     * @param bool $throwException throw exception in case of error
     *
     * @return bool
     */
    private function checkForOpenIndex($throwException = true)
    {
        if ($this->index) {
            return true;
        } elseif ($throwException) {
            throw new Exception('class.tx_mksearch_service_ZendLucene.php - no open index available!');
        }

        return false;
    }

    /**
     * Return index directory path.
     *
     * @param string $name Name of index
     *
     * @return string
     */
    private function getIndexDirectory($name)
    {
        $path = tx_rnbase_configurations::getExtensionCfgValue('mksearch', 'luceneIndexDir').DIRECTORY_SEPARATOR.$name;
        tx_rnbase::load('tx_rnbase_util_Files');
        if (!tx_rnbase_util_Files::isAbsPath($path)) {
            $path = \Sys25\RnBase\Utility\Environment::getPublicPath().$path;
        }

        return $path;
    }

    /**
     * Build query recursively from query array.
     *
     * @param $fields
     *
     * @return Zend_Search_Lucene_Search_Query_Boolean
     */
    private function buildQuery(array $fields)
    {
        $query = new Zend_Search_Lucene_Search_Query_Boolean();
        $mtquery = new Zend_Search_Lucene_Search_Query_MultiTerm();

        // Loop through all items of the field
        foreach ($fields as $key => $f) {
            foreach ($f as $ff) {
                if (!is_array($ff['term'])) {
                    // The term is a single token
                    if (!(isset($ff['phrase']) and $ff['phrase'])) {
                        // Call hook to manipulate search term. Term is utf8-encoded!
                        tx_rnbase_util_Misc::callHook(
                            'mksearch',
                            'engine_ZendLucene_buildQuery_manipulateSingleTerm',
                            array('term' => &$ff['term']),
                            $this
                        );

                        // The term is really just a simple string
                        $mtquery->addTerm(
                            new Zend_Search_Lucene_Index_Term($ff['term'], '__default__' == $key ? null : $key),
                            isset($ff['sign']) ? $ff['sign'] : null
                        );
                    } else {
                        // The term is a complete phrase, which must be build from its parts
                        $pq = new Zend_Search_Lucene_Search_Query_Phrase();
                        foreach (explode(' ', $ff['term']) as $t) { // @todo: explode with regex for respecting white spaces in general
                            // Call hook to manipulate search term. Term is utf8-encoded!
                            tx_rnbase_util_Misc::callHook(
                                'mksearch',
                                'engine_ZendLucene_buildQuery_manipulateSingleTerm',
                                array('term' => &$t),
                                $this
                            );
                            if ($t) {
                                $pq->addTerm(
                                    new Zend_Search_Lucene_Index_Term(
                                        $t,
                                        '__default__' == $key ? null : $key
                                    )
                                );
                            }
                        }

                        $query->addSubquery($pq);
                    }
                } else {
                    // The term represents a subquery - step down recursively
                    $query->addSubquery(
                        $this->buildQuery($ff['term']),
                        isset($ff['sign']) ? $ff['sign'] : null
                    );
                }
            }
        }
        if ($mtquery->getTerms()) {
            $query->addSubquery($mtquery);
        }

        return $query;
    }

    /**
     * Search indexed data via Zend Lucene.
     *
     * Search term must be charset-encoded identically like data was indexed (utf-8 by default)!
     * NOTE: Search results are always utf8-encoded!
     *
     * Provide fields in a recursive array.
     * A search token is defined by an array with several keys or by an array of tokens:
     *
     * 1.*
     * ***
     * token => array(
     *                  'term' => string
     *                  ,
     *                  'sign' [optional]   =>  true [field required, i.e. AND] |
     *                                          false [field prohibited, i. e. AND NOT] |
     *                                          null [field optional, i. e. OR]
     *                  ,
     *                  'phrase' [optional] =>  treat search term as phrase which is to be found completely
     *              )
     *
     * 2.*
     * ***
     * token => array(
     *                  'term' => token,
     *                  'sign' => ...
     *              )
     *
     *
     * Have a look at the following example where we are searching for
     * shoes classified as sneaker of the brand either adidas or puma, but not nike,
     * in the size 42 and in color yellow or red.
     * In technical terms this means we search for documents:
     * * which contain the word "sneaker"
     * * which's indexed attribute 'clothing' is "shoe"
     * * which's indexed attribute 'brand' is "adidas" or "puma", but not "nike"
     * * which meets the following condition:
     *   * let the indexed attribute 'color' be "yellow" or "red" AND
     *   * let the indexed attribute 'size' be "42"
     *
     * Note: It is not necessary to implement the "subquery" part in this example as sub-array,
     * but of course there are cases you really need self-contained subqueries.
     *
     *  $fields =
     *      array(
     *          // [document text contains:] 'sneaker'
     *          // -> use reserved fieldname '__default__'!
     *          '__default__'=> array(
     *                              array('term' => 'sneaker', 'sign' => true),
     *                          ),
     *
     *          // AND clothing='shoe'
     *          // -> use field name as array key
     *          'clothing'  =>  array(array('term' => 'shoe', 'sign' => true)),
     *
     *          // OR brand='adidas' OR brand='puma' AND NOT brand='nike'
     *          'brand'     =>  array(
     *                              array('term' => 'adidas', 'sign' => null),
     *                              array('term' => 'puma', 'sign' => null),
     *                              array('term' => 'nike', 'sign' => false),
     *                          ),
     *
     *          // AND ((color='yellow' OR color='red') AND size=42)
     *          // -> use an arbitrary fieldname, it's not needed for the subquery
     *          'subquery'  =>  array(
     *                              array(
     *                                  'term' => array(
     *                                              'color' => array(
     *                                                              array('term' => 'yellow', 'sign' => null),
     *                                                              array('term' => 'red', 'sign' => null),
     *                                                          ),
     *                                              'size'  => array(array('term' => 42, 'sign' => true)),
     *                                  ),
     *                                  'sign' => true
     *                              ),
     *                          ),
     *  );
     *
     * @param array $fields  Structure of fields - see example above
     * @param array $options Options for search:
     *                       * [bool] rawFormat:     Pass through $fields['term'] as raw search
     *                       * [array] fe_groups:    Allowed FE groups
     *                       * [string] sort:        Sort Field and Sort Order e.g. name desc
     *                       * [boolean] sortRandom: random sorting no matter what sorting was set
     *
     * @return array[tx_mksearch_model_SearchHit] or array[Zend_Search_Lucene_Search_QueryHit]  search results - format according to $options['rawOutput'] (usually not for public use as raw output depends on used search engine!)
     */
    public function search(array $fields = array(), array $options = array())
    {
        $this->checkForOpenIndex();
        // Advanced search, i. e. search term follows lucene query syntax?
        if (isset($options['rawFormat']) and $options['rawFormat']) {
            // Add access rights to search query
            if (array_key_exists('fe_groups', $options)) {
                // Explicitely add 0 to fe_groups to enable searches of anonymous users
                if (!in_array(0, $options['fe_groups'])) {
                    $options['fe_groups'][] = 0;
                }
                foreach ($options['fe_groups'] as &$f) {
                    $f = self::FE_GROUP_FIELD.':'.$f;
                }
                $queryString = '('.implode(' OR ', $options['fe_groups']).') AND ('.$fields['term'].')';
            } else {
                $queryString = $fields['term'];
            }

            // @TODO: Check syntax etc.
        } else {
            // @TODO: Change to $docIds  = $index->termDocs($term) => use filters

            // Add access rights to search query
            if (array_key_exists('fe_groups', $options)) {
                // Explicitely add 0 to fe_groups to enable searches of anonymous users
                if (!in_array(0, $options['fe_groups'])) {
                    $options['fe_groups'][] = 0;
                }

                $fe_groups = array();
                // Combine the given fe_groups by "OR"
                foreach ($options['fe_groups'] as $f) {
                    $fe_groups[] = array('term' => $f, 'sign' => null);
                }

                // Re-Build new $fields by "AND"ing fe_groups
                $fields = array('fe_groups_aware' => array(
                        array(
                            'term' => array(self::FE_GROUP_FIELD => $fe_groups),
                            'sign' => true,
                        ),
                        array(
                            'term' => $fields,    // Original fields
                            'sign' => true,
                        ),
                    ),
                );
            }
            $queryString = $this->buildQuery($fields);
        }
        // Attention: $queryString may also be an object...
        if ($options['debug'] && !is_string($queryString)) {
            $queryString->debug = true;
        }

        if ($options['sort']) {
            $sortParts = explode(' ', $options['sort']);
            list($sortField, $sortOrder) = explode(' ', $options['sort']);
            $sortOrder = ('asc' == strtolower($sortOrder)) ? SORT_ASC : SORT_DESC;
            $hits = $this->index->find($queryString, $sortField, SORT_REGULAR, $sortOrder);
        } else {
            $hits = $this->index->find($queryString);
        }

        if ($options['sortRandom']) {
            shuffle($hits);
        }

        if ($options['debug']) {
            tx_rnbase::load('tx_rnbase_util_Debug');
            tx_rnbase_util_Debug::debug(
                array(
                    'Fields' => $fields, 'Options' => $options,
                    'Query' => $queryString, 'Hits' => count($hits),
                ),
                'class.tx_mksearch_service_engine_ZendLucene.php'
            );
        }

        if (isset($options['rawOutput']) and $options['rawOutput']) {
            return $hits;
        }

        $offset = 0;
        $limit = count($hits);
        // Jetzt den PageBrowser setzen
        if (is_object($pb = $options['pb'])) {
            /* @var $pb tx_rnbase_util_PageBrowser */
            $pb->setListSize($limit);
            $state = $pb->getState();
            $offset = $state['offset'];
            $limit = $state['limit'];
        }

        // else
        tx_rnbase::load('tx_mksearch_model_SearchHit');
        $results = array();
        for ($i = $offset; $i < $limit + $offset; ++$i) {
            if (array_key_exists($i, $hits)) {
                $searchHit = $this->buildSearchHit($hits[$i]);
                if ($searchHit) {
                    $results[] = $searchHit;
                }
            } else {
                break;
            }
        }

        return $results;
    }

    private function buildSearchHit($hit)
    {
        $doc = $hit->getDocument();
        $data = array();
        foreach ($doc->getFieldNames() as $fn) {
            $field = $doc->getField($fn);
            // Get all fields except binary ones utf8-encoded
            if (!$field->isBinary) {
                $data[$fn] = $doc->getFieldUtf8Value($fn);
            } else {
                $data[$fn] = $doc->getFieldValue($fn);
            }
        }

        return $data ? new tx_mksearch_model_SearchHit($data) : null;
    }

    /**
     * Return the index opened at the moment.
     *
     * @return string
     */
    public function getOpenIndexName()
    {
        return $this->indexName;
    }

    /**
     * Open an index.
     *
     * @param tx_mksearch_model_internal_Index $name          Name of the index to open
     * @param bool                             $forceCreation Force creation of index if it doesn't exist
     */
    public function openIndex(tx_mksearch_model_internal_Index $index, $forceCreation = false)
    {
        if ($this->checkForOpenIndex(false)) {
            throw new Exception('class.tx_mksearch_service_ZendLucene.php::openIndex() - there is still an open index!');
        }

        $name = $index->getCredentialString();
        $indexDir = $this->getIndexDirectory($name);
        // At first try to open index
        try {
            $this->index = Zend_Search_Lucene::open($indexDir);
        } catch (Zend_Search_Lucene_Exception $e) {
            // Didn't work? That's because it doesn't exist.
            // Are we instructed to create the index if necessary? Then do so:
            tx_rnbase_util_Logger::warn('Lucene index open failed!', 'mksearch', array('indexDir' => $indexDir, 'Exception' => $e->getMessage()));
            if ($forceCreation) {
                $this->index = Zend_Search_Lucene::create($indexDir);
                tx_rnbase_util_Logger::warn('New Lucene index created!', 'mksearch', array('indexDir' => $indexDir));
            } // No? Then re-throw the Exception:
            else {
                throw $e;
            }
        }

        if ($this->index) {
            $this->indexName = $name;
        } // This never should happen:
        else {
            throw new Exception('class.tx_mksearch_service_ZendLucene.php::openIndex() - could not open / create index!');
        }

        // Set encoding
        Zend_Search_Lucene_Search_QueryParser::setDefaultEncoding('utf-8');
    }

    public function setIndexModel(tx_mksearch_model_internal_Index $index)
    {
        $this->indexModel = $index;
    }

    /**
     * Check if the specified index exists.
     *
     * @param string $name Name of index
     *
     * @return bool
     */
    public function indexExists($name)
    {
        return is_dir($this->getIndexDirectory($name));
    }

    /**
     * Commit index.
     *
     * Explicite commits are not needed for Zend_Lucene, as commit commit happens implicitely on
     * close of index and prior to all other operations which depend on a clean data state.
     *
     * @return bool success
     */
    public function commitIndex()
    {
        return true;
    }

    /**
     * Close index.
     */
    public function closeIndex()
    {
        $this->indexName = null;
        unset($this->index);
    }

    /**
     * Delete an entire index.
     *
     * @param optional string $name Name of index to delete, if not the open index is meant to be deleted
     */
    public function deleteIndex($name = null)
    {
        // Close index if necessary
        if (!$name or is_object($this) and $this->getOpenIndex() == $name) {
            $name = $this->indexName;
            $this->closeIndex();
        }

        // @todo: treat index locking...

        $indexDir = $this->getIndexDirectory($name);
        // Delete index directory recursively
        $iterator = new RecursiveDirectoryIterator($indexDir);
        foreach (new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($indexDir);
    }

    /**
     * Optimize index.
     */
    public function optimizeIndex()
    {
        // Committing the index before doing the actual optimization is not necessary
        // as the commit happens implictely on optimization by Zend_Lucene
        $this->index->optimize();
    }

    /**
     * Replace an index with another.
     *
     * The index to be replaced will be deleted.
     * This actually means that the old's index's directory will be deleted recursively!
     *
     * @param string $which Name of index to be replaced i. e. deleted
     * @param string $by    Name of index which replaces the index named $which
     */
    public function replaceIndex($which, $by)
    {
        if (!($this->indexExists($which) and $this->indexExists($by))) {
            throw new Exception('class.tx_mksearch_service_ZendLucene.php::replaceIndex() - at least one of the specified indexes doesn\'n exist!');
        }

        // Delete index $which
        $this->deleteIndex($which);
        // Rename index $by to the just deleted $which
        rename($this->getIndexDirectory($by), $this->getIndexDirectory($which));
    }

    /**
     * Get a document from index.
     *
     * @param $uid
     * @param $extKey
     * @param $contentType
     *
     * @return unknown_type
     */
    private function getIndexDocumentByContentUid($uid, $extKey, $contentType)
    {
        $searchTerm = "+uid:$uid +extKey:$extKey +contentType:$contentType";

        return $this->search(array('term' => $searchTerm), array('rawFormat' => 1, 'rawOutput' => 1));
    }

    /**
     * Get a document from index.
     *
     * @param string $uid
     * @param string $extKey
     * @param string $contentType
     *
     * @return tx_mksearch_model_SearchHit
     */
    public function getByContentUid($uid, $extKey, $contentType)
    {
        $results = $this->getIndexDocumentByContentUid($uid, $extKey, $contentType);
        if (count($results) > 1) {
            tx_rnbase_util_Logger::warn(
                'getByContentUid has returned more than one element.',
                'mksearch',
                array(
                    'service' => get_class($this),
                    'uid' => $uid,
                    'extKey' => $extKey,
                    'contentType' => $contentType,
                )
            );
        }

        return empty($results) ? null : reset($results);
    }

    /**
     * Add a field to the given index document.
     *
     * @param string                             $key
     * @param tx_mksearch_interface_IndexerField &$field
     * @param Zend_Search_Lucene_Document        &$doc
     */
    private function addFieldToIndexDoc($key, tx_mksearch_interface_IndexerField $field, Zend_Search_Lucene_Document $doc)
    {
        $value = $field->getValue();
        if (is_array($value)) {
            // Zend Lucene doesn't support multivalued fields. So we implode all data with withspace
            $value = implode(' ', $value);
        }
        // Den Type über den DataTypeMapper ermitteln. Dieser ersetzt die schema.xml von Solr...
        $storageType = $this->getDataTypeMapper()->getDataType($key);
        $encoding = '' != $field->getEncoding() ? $field->getEncoding() : 'utf8'; // Lucene unterstützt nur UTF-8.
        // switch ($field->getStorageType()) {
        switch ($storageType) {
            case 'text':
                $doc->addField(Zend_Search_Lucene_Field::Text($key, $value, $encoding));
                break;
            case 'keyword':
                $zf = Zend_Search_Lucene_Field::Keyword($key, $value, $encoding);
                $doc->addField($zf);
                break;
            case 'unindexed':
                $doc->addField(Zend_Search_Lucene_Field::UnIndexed($key, $value, $encoding));
                break;
            case 'unstored':
                $doc->addField(Zend_Search_Lucene_Field::UnStored($key, $value, $encoding));
                break;
            case 'binary':
                $doc->addField(Zend_Search_Lucene_Field::Binary($key, $value, $encoding));
                break;
            default:
                throw new Exception('tx_mksearch_service_engine_ZendLucene::_addFieldToIndexDoc(): Unknown storage type "'.$field->getStorageType().'"!');
        }
    }

    /**
     * Put a new record into index.
     *
     * @param tx_mksearch_model_IndexerDocument $doc "Document" to index
     */
    public function indexNew(tx_mksearch_interface_IndexerDocument $doc)
    {
        $zlDoc = new Zend_Search_Lucene_Document();

        // Core data
        $data = $doc->getPrimaryKey();
        //$data = $doc->getCoreData();

        // Hook to manipulate data
        // Note that manipulating data not only influences the indexing itself
        // but also the search result data at search time.
        // Keep in mind that e. g. lowercasing fields will result
        // in lowercased output on displaying search results!
        tx_rnbase_util_Misc::callHook(
            'mksearch',
            'engine_ZendLucene_indexNew_beforeAddingCoreDataToDocument',
            array('data' => &$data),
            $this
        );

        foreach ($data as $key => $field) {
            $this->addFieldToIndexDoc($key, $field, $zlDoc);
        }
        // Additional data
        $data = $doc->getData();
        // Hook to manipulate data
        tx_rnbase_util_Misc::callHook(
            'mksearch',
            'engine_ZendLucene_indexNew_beforeAddingAdditionalDataToDocument',
            array('data' => &$data),
            $this
        );

        // add default fe_group field if not present
        if (!array_key_exists('fe_group_mi', $data)) {
            $doc->addField('fe_group_mi', 0);
        }

        $data = $doc->getData();

        foreach ($data as $key => $field) {
            $this->addFieldToIndexDoc($key, $field, $zlDoc);
        }
        // There's intentionally no test if $this->index is valid for performance reasons.
        // You should not have made it to this point without a valid index anyway...
        $this->index->addDocument($zlDoc);
    }

    /**
     * Update or create an index record.
     *
     * @param tx_mksearch_model_IndexerDocument $doc "Document" to index
     */
    public function indexUpdate(tx_mksearch_interface_IndexerDocument $doc)
    {
        $data = $doc->getPrimaryKey();
        $old = $this->getIndexDocumentByContentUid($data['uid']->getValue(), $data['extKey']->getValue(), $data['contentType']->getValue());
        // No former document? Create one
        if (!$old) {
            $this->indexNew($doc);
        } // Is there still a former version of this document
        else {
            // Delete former version and create new version
            $this->index->delete($old[0]->id);
            $this->indexNew($doc);
        }
    }

    /**
     * Delete index document specified by content uid.
     *
     * @param int    $uid         Unique identifier of data record - unique within the scope of $extKey and $content_type
     * @param string $extKey      Key of extension the data record belongs to
     * @param string $contentType Name of semantic content type
     *
     * @return bool success
     */
    public function indexDeleteByContentUid($uid, $extKey, $contentType)
    {
        $hits = $this->getIndexDocumentByContentUid($uid, $extKey, $contentType);
        // No document with passed uid found?
        if (!$hits) {
            return false;
        }
        // else
        foreach ($hits as $h) {
            $this->index->delete($h->id);
        }

        return true;
    }

    /**
     * Delete index document specified by index id.
     *
     * @param int $id
     */
    public function indexDeleteByIndexId($id)
    {
        $this->index->delete($id);
    }

    /**
     * (non-PHPdoc).
     *
     * @see tx_mksearch_interface_SearchEngine::indexDeleteByQuery()
     */
    public function indexDeleteByQuery($query, $options = array())
    {
        // Not implemented!
        return false;
    }

    /**
     * Return an indexer document instance for the given content type.
     *
     * @param string $extKey      Extension key of records to be indexed
     * @param string $contentType Content type of records to be indexed
     *
     * @return tx_mksearch_interface_IndexerDocument
     */
    public function makeIndexDocInstance($extKey, $contentType)
    {
        return tx_rnbase::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            $extKey,
            $contentType
        );
    }

    /**
     * @return tx_mksearch_util_Status
     */
    public function getStatus()
    {
        $status = tx_rnbase::makeInstance('tx_mksearch_util_Status');
        // TODO: sinnvollen Test einfallen lassen...
        //Läßt sich der Index öffnen?
        if (!$this->indexModel) {
            $status->setStatus(-1, 'Illegal State: No index model found!');

            return $status;
        }
        $id = 1;
        $this->openIndex($this->indexModel, true);

        $msg = 'Up and running on directory '.$this->getIndexDirectory($this->indexModel->getCredentialString());
        $msg .= '<br/> Number of indexed documents: '.$this->index->numDocs();
        $status->setStatus($id, $msg);
        $this->closeIndex();

        return $status;
    }

    /**
     * Nothing to do.
     *
     * @see tx_mksearch_interface_SearchEngine::postProcessIndexing()
     */
    public function postProcessIndexing(tx_mksearch_model_internal_Index $oIndex)
    {
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/service/engine/class.tx_mksearch_service_engine_ZendLucene.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/service/engine/class.tx_mksearch_service_engine_ZendLucene.php'];
}
