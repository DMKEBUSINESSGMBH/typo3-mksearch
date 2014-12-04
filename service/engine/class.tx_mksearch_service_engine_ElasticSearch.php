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

require_once t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php';
tx_rnbase::load('tx_mksearch_interface_SearchEngine');
tx_rnbase::load('tx_rnbase_configurations');
tx_rnbase::load('tx_rnbase_util_Misc');
tx_rnbase::load('tx_mksearch_util_Misc');
tx_rnbase::load('tx_mksearch_service_engine_SolrException');
tx_rnbase::load('tx_rnbase_util_Logger');


/**
 * Service "ElasticSearch search engine" for the "mksearch" extension.
 *
 * @package	TYPO3
 * @subpackage	tx_mksearch
 */
class tx_mksearch_service_engine_ElasticSearch
	extends t3lib_svbase implements tx_mksearch_interface_SearchEngine
{

	/**
	 * Client used for searching and indexing
	 * as we could have more than one server we dont use exactly one index
	 * but a client. the client determines what idnex to use
	 *
	 * @var Client
	 */
	private $client = null;

	/**
	 * @var tx_mksearch_model_internal_Index
	 */
	private $mksearchIndexModel = null;

	/**
	 * @var string
	 */
	private $credentialsString = '';

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		spl_autoload_register(function($class){
			if (strpos($class, 'Elastica') !== FALSE) {
				$class = str_replace('\\', '/', $class);
				$filePath = t3lib_extMgm::extPath('mksearch').'lib/' . $class . '.php';
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
	protected function initElasticSearchConnection(array $credentials) {
		$this->client = $this->getElasticaClient($credentials);

		if (!$this->isServerAvailable()) {
			$logger = $this->getLogger();
			$logger::fatal(
				'ElasticSearch service not responding.', 'mksearch',
				array($credentials)
			);
			throw new ClientException('ElasticSearch service not responding.');
		}
	}

	/**
	 * @param array $credentials
	 *
	 * @return Index
	 */
	protected function getElasticaClient($credentials) {
		return new Client($credentials);
	}

	/**
	 *
	 * @return boolean
	 * @throws Exception
	 */
	protected function isServerAvailable() {
		$serverStatus = $this->getIndex()->getStatus()->getServerStatus();

		return $serverStatus['status'] == 200;
	}

	/**
	 * @return string
	 */
	protected function getLogger() {
		return tx_rnbase_util_Logger;
	}

	/**
	 * Search indexed data
	 *
	 * @param array		$fields
	 * @param array		$options
	 * @return array[tx_mksearch_model_SearchResult]	search results
	 */
	public function search(array $fields=array(), array $options=array()) {

	}

	/**
	 * Get a document from index
	 * @param string $uid
	 * @param string $extKey
	 * @param string $contentType
	 * @return tx_mksearch_model_SearchHit
	 */
	public function getByContentUid($uid, $extKey, $contentType) {

	}


	/**
	 * Return name of the index currently opened
	 *
	 * @return string
	 */
	public function getOpenIndexName() {

	}

	/**
	 * Open an index
	 *
	 * @param tx_mksearch_model_internal_Index	$index Instance of the index to open
	 * @param bool 		$forceCreation	Force creation of index if it doesn't exist
	 * @return void
	 */
	public function openIndex(
		tx_mksearch_model_internal_Index $index, $forceCreation=false
	) {
		$credentialsForElastica = $this->getElasticaCredentialsFromCredentialsString(
			$index->getCredentialString()
		);
		$this->initElasticSearchConnection($credentialsForElastica);
	}

	/**
	 * @param string $credentialString
	 * @return multitype:unknown Ambigous <unknown>
	 */
	protected function getElasticaCredentialsFromCredentialsString($credentialString) {
		$this->credentialsString = $credentialString;
		$serverCredentials = t3lib_div::trimExplode(';', $credentialString);

		if ($this->isSingleServerConfiguration($serverCredentials)) {
			$credentialsForElastica =
				$this->getElasticaCredentialArrayFromIndexCredentialStringForOneServer(
					$serverCredentials[0]
				);
		} else {
			foreach ($serverCredentials as $serverCredential) {
				$credentialsForElastica['servers'][] =
					$this->getElasticaCredentialArrayFromIndexCredentialStringForOneServer(
						$serverCredential
					);
			}
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
		$serverCredential = t3lib_div::trimExplode(',', $credentialString);
		return array(
			'host' => $serverCredential[0],
			'port' => $serverCredential[1],
			'path' => $serverCredential[2],
			'index' => $serverCredential[3],
		);
	}

	/**
	 * @param array $serverCredentials
	 * @return boolean
	 */
	private function isSingleServerConfiguration(array $serverCredentials) {
		return count($serverCredentials) == 1;
	}

	/**
	 * Liefert den Index
	 * da wir mehr als einen Index haben könnten, verwenden wir nicht direkt einen
	 * Index sondern einen Client. Dieser entscheidet selbst welcher
	 * Index verwendet wird.
	 *
	 * @return Client
	 */
	public function getIndex() {
		if (!is_object($this->client)) {
			$this->openIndex($this->mksearchIndexModel);
		}

		return $this->client;
	}

	/**
	 * Check if the specified index exists
	 *
	 * @param string	$name	Name of index
	 * @return bool
	 */
	public function indexExists($name) {

	}

	/**
	 * Commit index
	 *
	 * @return bool success
	 */
	public function commitIndex() {

	}

	/**
	 * Close index
	 *
	 * @return void
	 */
	public function closeIndex() {

	}

	/**
	 * Delete an entire index
	 *
	 * @param optional string $name	Name of index to delete, if not the open index is meant to be deleted
	 * @return void
	 */
	public function deleteIndex($name=null) {

	}

	/**
	 * Optimize index
	 *
	 * @return void
	 */
	public function optimizeIndex() {

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

	}

	/**
	 * Put a new record into index
	 *
	 * @param tx_mksearch_interface_IndexerDocument	$doc	"Document" to index
	 * @return bool		$success
	 */
	public function indexNew(tx_mksearch_interface_IndexerDocument $doc) {

	}

	/**
	 * Update or create an index record
	 *
	 * @param tx_mksearch_interface_IndexerDocument	$doc	"Document" to index
	 * @return bool		$success
	 */
	public function indexUpdate(tx_mksearch_interface_IndexerDocument $doc) {

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

	}

	/**
	 * Delete index document specified by index id
	 *
	 * @param string $id
	 * @return void
	 */
	public function indexDeleteByIndexId($id) {

	}

	/**
	 * Delete documents specified by raw query
	 * @param string $query
	 */
	public function indexDeleteByQuery($query, $options=array()) {

	}

	/**
	 * Return an indexer document instance for the given content type
	 *
	 * @param string	$extKey			Extension key of records to be indexed
	 * @param string	$contentType	Content type of records to be indexed
	 * @return tx_mksearch_interface_IndexerDocument
	 */
	public function makeIndexDocInstance($extKey, $contentType) {

	}

	/**
	 * Returns a index status object.
	 *
	 * @return tx_mksearch_util_Status
	 */
	public function getStatus() {
		/* @var $status tx_mksearch_util_Status */
		$status = tx_rnbase::makeInstance('tx_mksearch_util_Status');

		$id = -1;
		$msg = 'Down. Maybe not started?';
		try {
			if ($this->isServerAvailable()) {
				$id = 1;
				$msg = 'Up and running';
			}
		} catch (Exception $e) {
			$msg = 'Error connecting ElasticSearch: ' . $e->getMessage() . '.';
			$msg .= " Credentials: " . $this->credentialsString;
		}

		$status->setStatus($id, $msg);

		return $status;
	}

	/**
	 * Set index model with credential data
	 *
	 * @param tx_mksearch_model_internal_Index	$index Instance of the index to open
	 * @return void
	 */
	public function setIndexModel(tx_mksearch_model_internal_Index $index) {
		$this->mksearchIndexModel = $index;
	}

	/**
	 * This function is called for each index after the indexing
	 * is done.
	 *
	 * @param tx_mksearch_model_internal_Index $index
	 * @return void
	 */
	public function postProcessIndexing(tx_mksearch_model_internal_Index $index) {

	}

}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/service/engine/class.tx_mksearch_service_engine_ElasticSearch.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/service/engine/class.tx_mksearch_service_engine_ElasticSearch.php']);
}