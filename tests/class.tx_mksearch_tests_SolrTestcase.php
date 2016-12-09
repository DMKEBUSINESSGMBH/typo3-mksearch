<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 DMK E-Business GmbH
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

tx_rnbase::load('tx_rnbase_util_Files');
tx_rnbase::load('tx_mksearch_tests_Testcase');
tx_rnbase::load('tx_mksearch_util_ServiceRegistry');
tx_rnbase::load('tx_rnbase_util_Spyc');
tx_rnbase::load('tx_mksearch_tests_Util');

/**
 * Base test class for tests hitting Solr
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_tests
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 *
 * @deprecated no support for Solr >= 5.x will be added. Testing directly through
 * Solr doesn't make much sense in many cases anyway because that doesn't say much about
 * how the search will actually work in the UI. A better way would be to test
 * the search directly in the UI.
 */
abstract class tx_mksearch_tests_SolrTestcase
	extends tx_mksearch_tests_Testcase {

	/**
	 * @var unknown_type
	 */
	private $solr;

	/**
	 * Can be a TYPO3 path like EXT:mksearch/tests.....
	 * will be created upon core creation.
	 *
	 * @var string
	 */
	protected $instanceDir = '';

	/**
	 * Can be a TYPO3 path like EXT:mksearch/tests.....
	 * used for all solr version below 4.0
	 *
	 * @var string
	 */
	protected $configFile = '';

	/**
	 * Can be a TYPO3 path like EXT:mksearch/tests.....
	 * Is used when the solr index model is set to solr version 4.0
	 *
	 * @var string
	 */
	protected $configFileForSolr4 = '';

	/**
	 * Can be a TYPO3 path like EXT:mksearch/tests.....
	 * @var string
	 */
	protected $schemaFile = '';

	/**
	 * @var string
	 */
	private $coreName = '';

	/**
	 * @var tx_mksearch_service_engine_Solr
	 */
	private $solrEngine;

	/**
	 * @var unknown_type
	 */
	private $defaultIndexModel;

	/**
	 * (non-PHPdoc)
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['devlog']['nolog'] = true;

		$this->initAbsolutePathsForConfigs();
		tx_rnbase_util_Files::rmdir($this->instanceDir,true);
		$this->createCore();
	}

	/**
	 * (non-PHPdoc)
	 * @see PHPUnit_Framework_TestCase::tearDown()
	 */
	protected function tearDown() {
		parent::tearDown();
		$this->unloadCore();
		tx_rnbase_util_Files::rmdir($this->instanceDir,true);
	}

	/**
	 * @return void
	 */
	protected function initAbsolutePathsForConfigs() {
		$this->setConfigFileDependendOnSolrVersion();
		$this->instanceDir = tx_rnbase_util_Files::getFileAbsFileName($this->instanceDir);
		$this->configFile = tx_rnbase_util_Files::getFileAbsFileName($this->configFile);
		$this->schemaFile = tx_rnbase_util_Files::getFileAbsFileName($this->schemaFile);
	}

	/**
	 *
	 * @return void
	 */
	private function setConfigFileDependendOnSolrVersion() {
		$defaultIndexModel = $this->getDefaultIndexModel();

		if($defaultIndexModel->isSolr4()){
			$this->configFile = $this->configFileForSolr4;
		}
	}

	/**
	 * @return void
	 */
	protected function createCore() {
		if(!$this->isSolrOkay())
			$this->markTestSkipped($this->getSolrNotRespondingMessage());

		$this->createInstanceDir($this->instanceDir);

		$solr = $this->getSolr();

		$httpTransport = $solr->getHttpTransport();
		$url = $this->getAdminCoresPath() . '?action=CREATE&name=' . $this->getCoreName() .
			'&instanceDir=' . $this->instanceDir . '&config=' . $this->configFile . '&schema=' .
			$this->schemaFile;
		$httpResponse = $httpTransport->performGetRequest($url);

		if($httpResponse->getStatusCode() != 200){
			$this->fail('Der Core (' . $this->getCoreName() . ') konnte nicht erstellt werden. URL: ' . $url . '. Bitte in die Solr Konsole schauen bzgl. der Fehler!');
		}

		$this->setSolrCredentialsForNewCore();
	}

	private function setSolrCredentialsForNewCore() {
		//$this->getDefaultIndexModel()->record['name'] ist z.B.
		//"localhost,8081,/solr-3.5.0/mycore"
		$credentialsStringParts =
			tx_rnbase_util_Strings::trimExplode(',',$this->getDefaultIndexModel()->record['name']);

		//damit ist also $credentialsStringParts[2] z.B.
		//"/solr-3.5.0/mycore"
		$solrPathParts = tx_rnbase_util_Strings::trimExplode('/',$credentialsStringParts[2]);

		//build new credential string
		$newCredentialsString = $credentialsStringParts[0] . ',' . $credentialsStringParts[1] .
			',/' . $solrPathParts[1] . '/' . $this->getCoreName();

		$newCredentials = $this->getSolrEngine()->getCredentialsFromString($newCredentialsString);
		$this->getSolrEngine()->setConnection(
			$newCredentials['host'], $newCredentials['port'], $newCredentials['path'], false
		);
		$this->solr = null;
	}

	/**
	 * Enter description here ...
	 */
	private function getDefaultIndexModel() {
		if(!$this->defaultIndexModel){
			$this->defaultIndexModel =
				tx_mksearch_util_ServiceRegistry::getIntIndexService()->getRandomSolrIndex();
		}

		if(!$this->defaultIndexModel)
			$this->markTestSkipped('Es wurde kein Solr Index gefunden. Solr ist scheinbar nicht konfigruiert.');

		return $this->defaultIndexModel;
	}


	/**
	 * @return string
	 */
	private function getSolrNotRespondingMessage() {
		$additionalMessage = '';
		if(!is_null($this->solr)){
			$additionalMessage .= ' auf: Host: ' . $this->getSolr()->getHost() . ', Port: ' .
				$this->getSolr()->getPort() . ', Path: ' . $this->getSolr()->getPath();
		}

		return 'Solr ist nicht erreichbar' . $additionalMessage;
	}

	/**
	 *
	 * @return Apache_Solr_Service
	 */
	protected function getSolr() {
		if($this->solr)
			return $this->solr;

		try {
			$this->solr = $this->getSolrEngine()->getSolr();
		} catch (Exception $e) {
			$this->markTestSkipped($this->getSolrNotRespondingMessage());
		}

		return $this->solr;
	}

	/**
	 * @return string
	 */
	protected function getCoreName() {
		if(!$this->coreName)
			//muss mit einem buchstaben beginnen da der name
			//in setSolrCredentialsForNewCore in preg_replace
			//nicht korrekt ersetzt wird
			$this->coreName = 'a'. md5(microtime());

		return $this->coreName;
	}

	/**
	 * per default den ersten konfiguriereten index.
	 * sollte so passen.
	 * @return tx_mksearch_service_engine_Solr
	 */
	protected function getSolrEngine() {
		if(!$this->solrEngine){
			if(!$defaultIndexModel = $this->getDefaultIndexModel())
				$this->markTestSkipped($this->getSolrNotRespondingMessage());

			$this->solrEngine = tx_mksearch_util_ServiceRegistry::getSearchEngine(
				$defaultIndexModel
			);
		}
		return $this->solrEngine;
	}

	/**
	 * @param string $path
	 * @return void
	 */
	protected function createInstanceDir($path) {
		//da auf den ordner auch der nutzer zugreift, der solr ausführt und das
		//nicht der gleiche wie der nutzer von tyop3 sein sollte, müssen wir
		//diesem zugriff auf den ordner geben. die default umask, die über setfacl
		//gesetzt wird, wird von TYPO3 überschrieben. Also setzen wir die umask vorrübergehend wie
		//wir es brauchen
		$umaskBackup = $GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask'];
		$GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask'] = '0775';

		tx_rnbase_util_Files::mkdir_deep($path . '/conf');
		tx_rnbase_util_Files::mkdir_deep($path . '/lib');

		$GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask'] = $umaskBackup;
	}

	/**
	 * @return string
	 */
	protected function getAdminCoresPath() {
		return $this->getBaseUrl() . '/' . 'admin/cores';
	}

	/**
	 * @return string
	 */
	private function getBaseUrl() {
		$solr = $this->getSolr();
		$baseSolrPath = explode('/', $solr->getPath());
		return 'http://' . $solr->getHost() . ':' . $solr->getPort() . '/' . $baseSolrPath[1];
	}

	/**
	 * @return void
	 */
	protected function unloadCore() {
		if(!$this->isSolrOkay())
			$this->fail($this->getSolrNotRespondingMessage());

		$url = $this->getAdminCoresPath() . '?action=UNLOAD&core=' . $this->getCoreName() . '&deleteIndex=true';
		$httpResponse =  $this->getSolr()->getHttpTransport()->performGetRequest($url);

		if($httpResponse->getStatusCode() != 200){
			$this->fail('Der Core (' . $this->getCoreName() . ') konnte nicht gelöscht werden. URL: ' . $url . '. Bitte in die Solr Konsolte schauen bzgl. der Fehler!');
		}
	}

	/**
	 *
	 * @param string $yamlPath
	 */
	protected function indexDocsFromYaml($yamlPath) {
		if(!$this->isSolrOkay())
			$this->fail($this->getSolrNotRespondingMessage());

		// Erstmal komplett leer räumen
		$this->getSolr()->deleteByQuery('*:*');

		tx_rnbase::load('tx_rnbase_util_Spyc');
		$data = tx_rnbase_util_Spyc::YAMLLoad($yamlPath);

		foreach($data['docs'] As $docArr) {
			$extKey = $docArr['extKey']; unset($docArr['extKey']);
			$cType = $docArr['contentType']; unset($docArr['contentType']);
			$uid = $docArr['uid']; unset($docArr['uid']);
			$indexDoc = $this->createDoc($extKey, $cType);
			$indexDoc->setUid($uid);

			foreach($docArr As $field => $value) {
				$indexDoc->addField($field, $value);
			}
			$this->getSolrEngine()->indexUpdate($indexDoc);
		}

		$this->getSolrEngine()->commitIndex();
	}

	/**
	 * @param unknown_type $extKey
	 * @param unknown_type $cntType
	 * @return tx_mksearch_interface_IndexerDocument
	 */
	private function createDoc($extKey, $cntType) {
		$indexDoc = $this->getSolrEngine()->makeIndexDocInstance($extKey, $cntType);
		return $indexDoc;
	}

	/**
	 * @return boolean
	 */
	protected function isSolrOkay() {
		try {
			$ret = $this->getSolr()->ping() !== false;
		}
		catch(Exception $e) {
			$ret = false;
		}
		return $ret;
	}

	/**
	 * es wird default nach *:* und einem limit von 10
	 * gesucht
	 *
	 * @param array $options
	 * @param array $fields
	 * @return array
	 */
	protected function search(array $options = array(), array $fields = array()) {
		if(empty($fields['term']))
			$fields['term'] = '*:*';

		if(empty($options['limit']))
			$options['limit'] = 10;

		return $this->getSolrEngine()->search($fields, $options);
	}

	protected function assertNothingFound($result) {
		self::assertEquals(0, $result['numFound'], 'doch etwas gefunden');
		self::assertEmpty($result['items'], 'doch items etwas gefunden');
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/class.tx_mksearch_tests_SolrTestcase.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/class.tx_mksearch_tests_SolrTestcase.php']);
}