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

require_once t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php';
tx_rnbase::load('tx_mksearch_interface_SearchEngine');
tx_rnbase::load('tx_rnbase_configurations');
tx_rnbase::load('tx_rnbase_util_Misc');
tx_rnbase::load('tx_mksearch_util_Misc');
tx_rnbase::load('tx_mksearch_service_engine_SolrException');
tx_rnbase::load('tx_rnbase_util_Logger');

require_once(t3lib_extMgm::extPath('mksearch').'lib/Apache/Solr/Service.php' );

/**
 * Service "Solr search engine" for the "mksearch" extension.
 *
 * @package	TYPO3
 * @subpackage	tx_mksearch
 */
class tx_mksearch_service_engine_Solr extends t3lib_svbase implements tx_mksearch_interface_SearchEngine {

	/**
	 * Index used for searching and indexing
	 *
	 * @var Apache_Solr_Service
	 */
	private $index = null;

	/**
	 * Name of the currently open index
	 *
	 * @var string
	 */
	private $indexName;

	/**
	 * @var tx_mksearch_model_internal_Index
	 */
	private $indexModel = null;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
//		$this->setConnection(tx_rnbase_configurations::getExtensionCfgValue('mksearch', 'solrhost'),
//				tx_rnbase_configurations::getExtensionCfgValue('mksearch', 'solrport'),
//				tx_rnbase_configurations::getExtensionCfgValue('mksearch', 'solrpath'));
	}

	/**
	 * Try to connect to the named server, port, and url
	 * @param string $host
	 * @param string $port
	 * @param string $path
	 * @throws Exception
	 */
	public function setConnection($host, $port, $path, $force = true) {
		$this->index = new Apache_Solr_Service( $host, $port, $path);

		$this->index->setSolrVersion($this->indexModel->getSolrVersion());

		//per default werden alle HTTP Aufrufe per file_get_contents erledigt.
		//siehe Apache_Solr_Service::getHttpTransport()
		//damit das funktioniert muss allerdings allow_url_fopen in den PHP
		//Einstellungen aktiv sein. Das öffnet nun aber eine große
		//Sicherheitslücke. Alternativ bieten wir daher an alle Http Aufrufe
		//per Curl durchzuführen.
		if(tx_rnbase_configurations::getExtensionCfgValue('mksearch', 'useCurlAsHttpTransport')){
			require_once(t3lib_extMgm::extPath('mksearch').'lib/Apache/Solr/HttpTransport/Curl.php' );
			$oHttpTransport = new Apache_Solr_HttpTransport_Curl();
			$this->index->setHttpTransport($oHttpTransport);
		}

		//die Methode Ping gibt bei einem 200er die Millisek. zurück,
		//die die Anfrage gedauert hat, dies kann auch 0 sein!
		if ( $this->index->ping() === FALSE && $force) {
			tx_rnbase_util_Logger::fatal('Solr service not responding.','mksearch',array($host, $port, $path));
			throw new tx_mksearch_service_engine_SolrException('Solr service not responding.', -1, 'http://'.$host.':'.$port.$path);
		}
	}

	/**
	 * Check if an index was opened
	 *
	 * @param bool $throwException	throw exception in case of error
	 * @return bool
	 */
	private function checkForOpenIndex($throwException=true) {
		if ($this->index) return true;
		else if ($throwException)
			throw new Exception('class.tx_mksearch_service_Solr.php - no open index available!');
		return false;
	}

	/**
	 * Return index directory path
	 * @param string	$name	Name of index
	 * @return string
	 */
	private function getIndexDirectory($name) {
		return tx_rnbase_configurations::getExtensionCfgValue('mksearch', 'luceneIndexDir').DIRECTORY_SEPARATOR.$name;
	}

	/**
	 * Build query recursively from query array
	 *
	 * @param $fields
	 * @return Zend_Search_Lucene_Search_Query_Boolean
	 */
	private function buildQuery(array $fields) {
		$query = new Zend_Search_Lucene_Search_Query_Boolean;
		$mtquery = new Zend_Search_Lucene_Search_Query_MultiTerm;

		// Loop through all items of the field
		foreach ($fields as $key=>$f) {
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
										new Zend_Search_Lucene_Index_Term(
											$ff['term'],
											$key=='__default__'?null:$key
										),
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
												$key=='__default__' ? null : $key
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
		if ($mtquery->getTerms()) $query->addSubquery($mtquery);
		return $query;
	}

	/**
	 * Search indexed data via Apache Solr
	 *
	 * Search term must be charset-encoded identically like data was indexed (utf-8 by default)!
	 * NOTE: Search results are always utf8-encoded!
	 * Possible attribute for $fields: 'term'
	 * $fields['term'] = 'solrfield:test* OR otherfield:test*'
	 * The term string contains the solr query string.
	 *
	 * @param array		$fields
	 * @param array		$options	key / value pairs for other query parameters (see Solr documentation), use arrays for parameter keys used more than once (e.g. facet.field)
	 * 								* [int] offset
	 * 								* [int] limit
	 * @return array[tx_mksearch_model_SearchHit]
	 */
	public function search(array $fields=array(), array $options=array()) {
		return $this->searchSolr($fields, $options);
	}

	/**
	 * Suche in Solr
	 *
	 * @param array $fields erlaubt ist derzeit term
	 * @param array $options Alle weiteren Solr-Optionen
	 * @return array
	 */
	private function searchSolr($fields, $options) {
		$start = microtime(true);
		$ret = array();
		$solr = $this->getSolr();
		try {
			$response = $solr->search($fields['term'],intval($options['offset']),intval($options['limit']), $options);
			if($response->getHttpStatus() != 200) {
				throw new tx_mksearch_service_engine_SolrException('Error requesting solr. HTTP status:'.$response->getHttpStatus(), -1, $solr->lastUrl);
			}

			// wir müssen hier schon die hits erzeugen.
			// im tx_mksearch_util_SolrResponseProcessor werden Sie dann nurnoch bearbeidet!
			$hits = array();
			if ($response->response->docs) {
				foreach($response->response->docs as $doc) {
					$hits[] = tx_rnbase::makeInstance('tx_mksearch_model_SolrHit', $doc);
				}
			}

			$ret['items'] = $hits;
			$ret['searchUrl'] = $solr->lastUrl;
			$ret['searchTime'] = (microtime(true) - $start) . ' ms';
			$ret['numFound'] = $response->response->numFound;
			$ret['response'] = &$response; // wichtig, wird im SolrResponseProcessor benötigt

			if($options['debug']) {
				if (is_object($response->debug))
					$ret['debug'] = get_object_vars($response->debug);
				tx_rnbase_util_Debug::debug(array($options, $ret), 'class.tx_mksearch_service_engine_Solr.php Line: '.__LINE__); // TODO: remove me
			}
		}
		catch(Exception $e) {
			throw new tx_mksearch_service_engine_SolrException('Exception caught from Solr:'.$e->getMessage(), -1, $solr->lastUrl, $e);
		}

		return $ret;
	}

	/**
	 * Return the index opened at the moment
	 * @return string
	 */
	public function getOpenIndexName() {
		return $this->indexName;
	}

	/**
	 * Open an index
	 *
	 * @param tx_mksearch_model_internal_Index	$index Instance of the index to open
	 * @param bool 		$forceCreation	Force creation of index if it doesn't exist
	 * @return void
	 */
	public function openIndex(tx_mksearch_model_internal_Index $index, $forceCreation=false) {
		$cred = self::getCredentialsFromString($index->getCredentialString());
		$this->setConnection($cred['host'], $cred['port'], $cred['path']);
	}
	public function setIndexModel(tx_mksearch_model_internal_Index $index) {
		$this->indexModel = $index;
	}

	/**
	 * Return credential array for string
	 * @param string $data
	 * @return array
	 */
	public static function getCredentialsFromString($data) {
		$data = t3lib_div::trimExplode(',', $data);
		if(count($data) != 3) throw new Exception('Wrong credentials for solr defined.');
		$ret = array();
		$ret['host'] = $data[0];
		$ret['port'] = $data[1];
		$ret['path'] = $data[2];
		return $ret;
	}


	/**
	 * Check if the specified index exists
	 * @param string	$name	Name of index
	 * @return bool
	 */
	public function indexExists($name) {
		return is_dir($this->getIndexDirectory($name));
	}

	/**
	 * Commit index
	 *
	 * Explicite commits are not needed for Zend_Lucene, as commit commit happens implicitely on
	 * close of index and prior to all other operations which depend on a clean data state.
	 *
	 * @return bool success
	 * @throws Exception
	 */
	public function commitIndex() {
		$this->getSolr()->commit();
		$this->optimizeIndex();
		return true;
	}

	/**
	 * Close index
	 * @return void
	 */
	public function closeIndex() {
		$this->indexName = null;
		unset($this->index);
	}

	/**
	 * Delete an entire index
	 *
	 * @param optional string $name	Name of index to delete, if not the open index is meant to be deleted
	 * @return void
	 */
	public function deleteIndex($name=null) {
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
			if ($file->isDir()) rmdir($file->getPathname());
			else unlink($file->getPathname());
		}
		rmdir($indexDir);
	}

	/**
	 * Optimize index
	 *
	 * @return void
	 */
	public function optimizeIndex() {
		// Committing the index before doing the actual optimization is not necessary
		// as the commit happens implictely on optimization by Zend_Lucene
		$this->getSolr()->optimize();
	}

	/**
	 * Replace an index with another.
	 *
	 * The index to be replaced will be deleted.
	 * This actually means that the old's index's directory will be deleted recursively!
	 *
	 * @param string	$which	Name of index to be replaced i. e. deleted
	 * @param string	$by		Name of index which replaces the index named $which
	 * @return void
	 */
	public function replaceIndex($which, $by) {
		if (!($this->indexExists($which) and $this->indexExists($by)))
			throw new Exception('class.tx_mksearch_service_ZendLucene.php::replaceIndex() - at least one of the specified indexes doesn\'n exist!');

		// Delete index $which
		$this->deleteIndex($which);
		// Rename index $by to the just deleted $which
		rename($this->getIndexDirectory($by), $this->getIndexDirectory($which));
	}

	/**
	 * Get a document from index
	 * @param $uid
	 * @param $extKey
	 * @param $contentType
	 * @return unknown_type
	 */
	private function getIndexDocumentByContentUid($uid, $extKey, $contentType) {
		$searchTerm = "+uid:$uid +extKey:$extKey +contentType:$contentType";
		return $this->search(
			array('term' => $searchTerm),
			//we set the defType to "lucene" in case the default request handler
			//is dismax or something else. please note that the default request handler
			//shouldn't set a fq or something else!
			array('defType' => 'lucene', 'rawFormat' => 1, 'rawOutput' => 1, 'limit'=>100)
		);
	}

	/**
	 * Get a document from index
	 * @param string $uid
	 * @param string $extKey
	 * @param string $contentType
	 * @return tx_mksearch_model_SearchHit
	 */
	public function getByContentUid($uid, $extKey, $contentType) {
		$response = $this->getIndexDocumentByContentUid($uid, $extKey, $contentType);
		if (empty($response['items']) || !is_array($response['items'])) {
			return NULL;
		}
		if (count($response['items']) > 1) {
			tx_rnbase_util_Logger::warn(
				'getByContentUid has returned more than one element.',
				'mksearch', array(
					'service' => get_class($this),
					'uid' => $uid,
					'extKey' => $extKey,
					'contentType' => $contentType,
				)
			);
		}
		return reset($response['items']);
	}

	/**
	 * Add a field to the given index document
	 * TODO: wird hier in Solr vermutlich nicht verwendet! tx_mksearch_model_IndexerField gibt es nicht mehr...
	 *
	 * @param string $key
	 * @param tx_mksearch_model_IndexerField 	&$field
	 * @param Zend_Search_Lucene_Document		&$doc
	 */
	private function addFieldToIndexDoc(
						$key,
						tx_mksearch_model_IndexerField &$field,
						Zend_Search_Lucene_Document &$doc
						)
	{
		switch ($field->getStorageType()) {
			case 'text':		$doc->addField(Zend_Search_Lucene_Field::Text(
												$key,
												$field->getValue(),
												$field->getEncoding()
								));
								break;
   			case 'keyword':		$doc->addField(Zend_Search_Lucene_Field::Keyword(
											$key,
											$field->getValue(),
											$field->getEncoding()
   								));
   								break;
   			case 'unindexed':	$doc->addField(Zend_Search_Lucene_Field::UnIndexed(
											$key,
											$field->getValue(),
											$field->getEncoding()
   								));
   								break;
   			case 'unstored':	$doc->addField(Zend_Search_Lucene_Field::UnStored(
   											$key,
											$field->getValue(),
											$field->getEncoding()
   								));
   								break;
   			case 'binary':		$doc->addField(Zend_Search_Lucene_Field::Binary(
   											$key,
											$field->getValue(),
											$field->getEncoding()
   								));
   								break;
   			default:			throw new Exception('tx_mksearch_service_engine_Colr::_addFieldToIndexDoc(): Unknown storage type "'.$field->getStorageType().'"!');
		}
	}

	/**
	 * Put a new record into index
	 *
	 * @param tx_mksearch_interface_IndexerDocument	$doc	"Document" to index
	 * @return void
	 */
	public function indexNew(tx_mksearch_interface_IndexerDocument $doc) {
		$solrDoc = new Apache_Solr_Document();

		// Primary key data (fields are all scalar)
		$data = $doc->getPrimaryKey();
		$id = array();
		foreach ($data as $key=>$field) {
			if (!empty($field)) {
				$value = $field->getValuesWithBoost();
				if (!empty($value)) {
					$id[$key] = $value['value'];
					$solrDoc->setField($key, $value['value'], $value['boost']);
				}
				// Without complete id we can't do anything meaningful!
				else return;
			}
		}
		// Explicitely set "id" field - this must be set as unique field in Solr's schema.xml!
		// @todo make configurable!
		$solrDoc->setField('id', $id['extKey'] . ':' . $id['contentType'] . ':' . $id['uid']);
		// Payload data
		$data = $doc->getData();
		foreach ($data as $key=>$field) {
			if ($field) {
				if (!$field->getStorageOption('multiValued')) {
					$values = $field->getValuesWithBoost();
					$solrDoc->setField($key, tx_mksearch_util_Misc::utf8Encode($values['value']), $values['boost']);
				} else {
					$multipleValues = $field->getValuesWithBoost();
					foreach ($multipleValues as $value)
						$solrDoc->addField($key, tx_mksearch_util_Misc::utf8Encode($value['value']), $value['boost']);
				}
			}
		}
		// There's intentionally no test if $this->index is valid for performance reasons.
		// You should not have made it to this point without a valid index anyway...
		try {
			// Check if there is a binary document to be indexed
			$seCommands = $doc->getSECommands();
			if(is_array($seCommands) && array_key_exists('indexBinary', $seCommands)) {
				$this->indexBinaryDoc($seCommands['indexBinary'], $solrDoc);
			}
			else {
				$this->getSolr()->addDocument($solrDoc);
			}
		} catch (Apache_Solr_HttpTransportException $e) {
			tx_rnbase_util_Logger::fatal('[SOLR] Adding document to Solr failed.', 'mksearch',
				array('Exception' => $e->getMessage(), 'lastUrl' => $this->getSolr()->lastUrl,
						'doc' => $this->getFields4Doc($solrDoc),
						'solrResponse'=>$e->getResponse()->getRawResponse()));
			throw $e;
		}
	}
	public function getFields4Doc(Apache_Solr_Document $solrDoc) {
		$ret = array();
		$fieldNames = $solrDoc->getFieldNames();
		foreach($fieldNames As $fName) {
			$ret[$fName] = $solrDoc->getField($fName);
		}
		return $ret;
	}
	/**
	 * Let Solr Cell extract data from streamed content.
	 * @param array $options
	 * @param Apache_Solr_Document $solrDoc
	 */
	private function indexBinaryDoc(array $options, Apache_Solr_Document $solrDoc) {
		$file = $options['sourcefile'];
		$id = $solrDoc->getField('id');
		if(!$file) {
			throw new Exception('No filename found for binary document: ' . (is_array($id) ? $id['value'] : '"no id given"'));
		}
		$fileType = $options['file_type'];
		$fileMimeType = $options['file_mime_type'];
		$fileMimeSubtype = $options['file_mime_subtype'];

		// Zuerst nach Optionen für Filetype suchen
		$params = $options['solr.']['indexOptions.'][$fileType.'.']['params.'];
		$params = is_array($params) ? $params : $options['solr.']['indexOptions.']['params.'];
		$params = is_array($params) ? $params : array();

		$solrMimeType = ($fileMimeType && $fileMimeSubtype) ? $fileMimeType .'/'. $fileMimeSubtype : 'application/octet-stream';
		$response = $this->getSolr()->extract($file, $params, $solrDoc, $solrMimeType);
		return $response;
	}

	/**
	 * Update or create an index record
	 *
	 * @param tx_mksearch_interface_IndexerDocument	$doc	"Document" to index
	 * @return void
	 */
	public function indexUpdate(tx_mksearch_interface_IndexerDocument $doc) {
		$this->indexNew($doc);
	}

	/**
	 * Delete index document specified by content uid
	 *
	 * @param int		$uid			Unique identifier of data record - unique within the scope of $extKey and $content_type
	 * @param string	$extKey			Key of extension the data record belongs to
	 * @param string	$contentType	Name of semantic content type
	 * @return bool success
	 */
	public function indexDeleteByContentUid($uid, $extKey, $contentType) {
		$result = $this->getIndexDocumentByContentUid($uid, $extKey, $contentType);

		// No document with passed uid found?
		if ($result['numFound'] == 0 || empty($result['items'])) return false;
		$hits = $result['items'];
		foreach ($hits as $hit)
			$this->getSolr()->deleteById($hit->getSolrId());
		return true;
	}

	/**
	 * Delete index document specified by index id
	 *
	 * @param int $id
	 * @return void
	 */
	public function indexDeleteByIndexId($id) {
		$this->index->delete($id);
	}
	/**
	 * (non-PHPdoc)
	 * @see tx_mksearch_interface_SearchEngine::indexDeleteByQuery()
	 */
	public function indexDeleteByQuery($query, $options=array()) {
		$solr = $this->getSolr();

		try {
			$ret = array();
			$response = $solr->deleteByQuery($query);
			if($response->getHttpStatus() != 200) {
				throw new tx_mksearch_service_engine_SolrException('Error requesting solr. HTTP status:'.$response->getHttpStatus(), -1, $solr->lastUrl);
			}
			$ret['response'] = &$response; // wichtig, wird im SolrResponseProcessor benötigt

			if($options['debug']) {
				$ret['debug'] = get_object_vars($response->debug);
				tx_rnbase_util_Debug::debug(array($options, $ret), 'class.tx_mksearch_service_engine_Solr.php Line: '.__LINE__); // TODO: remove me
			}
		}
		catch(Exception $e) {
			throw new tx_mksearch_service_engine_SolrException('Exception caught from Solr:'.$e->getMessage(), -1, $solr->lastUrl, $e);
		}

		return $ret;
	}

	/**
	 * Return an indexer document instance for the given content type
	 *
	 * @param string	$extKey			Extension key of records to be indexed
	 * @param string	$contentType	Content type of records to be indexed
	 * @return tx_mksearch_interface_IndexerDocument
	 */
	public function makeIndexDocInstance($extKey, $contentType) {
		// TODO: Die einheitliche Feldklasse verwenden: tx_mksearch_model_IndexerFieldBase
		return tx_rnbase::makeInstance(
			'tx_mksearch_model_IndexerDocumentBase',
			$extKey, $contentType,
			'tx_mksearch_model_engineSpecific_solr_IndexerField'
		);
	}
	/**
	 * @return tx_mksearch_util_Status
	 */
	public function getStatus() {
		/* @var $status tx_mksearch_util_Status */
		$status = tx_rnbase::makeInstance('tx_mksearch_util_Status');
		$id = 1;
		$msg = 'Up and running';
		try {
			$respTime = $this->getSolr()->ping();
			if($respTime !== FALSE)
				$msg .= ' (Ping time: ' . $respTime .' ms)';
			else {
				$id = -1;
				$msg = 'Ping to Solr failed! Url: ' . $this->getSolr()->getHost() . ':'. $this->getSolr()->getPort() . $this->getSolr()->getPath() . 'admin/ping';
			}
		}
		catch(Exception $e) {
			$id = -1;
			$msg = 'Error connecting Solr: ' . $e->getMessage();
			$msg .= " Url: ".$this->getSolr()->getHost() . ':'. $this->getSolr()->getPort() . $this->getSolr()->getPath() . 'admin/ping';
		}
		$status->setStatus($id, $msg);
		return $status;
	}
	/**
	 * Liefert den Index
	 *
	 * @return Apache_Solr_Service
	 */
	public function getSolr() {
		if(!is_object($this->index)) {
			$this->openIndex($this->indexModel, false);
		}
		return $this->index;
	}

	/**
	 * Shall the autocomplete/spellcheck index be updated?
	 *
	 * @see tx_mksearch_interface_SearchEngine::postProcessIndexing()
	 */
	public function postProcessIndexing(tx_mksearch_model_internal_Index $oIndex) {
		$aConfig = $oIndex->getIndexConfig();
		//shall the autocomplete/spellcheck be updated?
		if($aConfig['solr.']['builtSpellcheck'])
			$this->builtSpellcheckIndex($aConfig['solr.']['builtSpellcheck']);
	}

	/**
	 * Build the autocomplete index
	 *
	 * @deprecated use buildOnCommit Option for the Solr Suggest/Spellcheck Component. Take
	 * a look at the default solrconfig.xml and the "suggest" search component.
	 * @param string $sRequestHandler
	 * @return void
	 */
	protected function builtSpellcheckIndex($sRequestHandler) {
		$oSolr = $this->getSolr();
		//remove trailing slash of the path
		if(substr($oSolr->getPath(),-1) == '/')
			$sPath = substr($oSolr->getPath(),0,-1);
		//now add the configured request handler executing the update
		$sUrl = $oSolr->getHost() . ':' . $oSolr->getPort() . $sPath . $sRequestHandler;
		//now add the command for the built
		$sUrl .= '?spellcheck.build=true';

		//and execute the command
		$oSolr->getHttpTransport()->performHeadRequest($sUrl,array(),'application/xml; charset=UTF-8');
	}


	/**
	 * Resets the service!
	 *
	 * @return	void
	 */
	function reset() {
		parent::reset();
		unset($this->index);
		unset($this->indexModel);
		$this->index = null;
		$this->indexName = null;
		$this->indexModel = null;
	}

}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/service/engine/class.tx_mksearch_service_engine_Solr.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/service/engine/class.tx_mksearch_service_engine_Solr.php']);
}
