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
tx_rnbase::load('tx_mksearch_tests_SolrTestcase');

/**
 * @author Hannes Bochmann
 * @integration
 */
class tx_mksearch_tests_filter_SolrBase_solr_testcase extends tx_mksearch_tests_SolrTestcase {

	protected $instanceDir = 'EXT:mksearch/tests/solrtestcore/';
	protected $configFile = 'EXT:mksearch/solr/conf/solrconfig-3.x.xml';
	protected $configFileForSolr4 = 'EXT:mksearch/solr/conf/solrconfig-4.x.xml';
	protected $schemaFile = 'EXT:mksearch/solr/conf/schema.xml';
	protected $groupDataBackup;

	/**
	 * (non-PHPdoc)
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp() {
		$this->groupDataBackup = $GLOBALS['TSFE']->fe_user->groupData['uid'];
		$this->initAbsolutePathsForConfigs();
		tx_rnbase_util_Files::rmdir($this->instanceDir,true);

		$this->copyNeccessaryConfigFiles($this->instanceDir);
		$this->copyNeccessaryLibFiles($this->instanceDir);

		$this->createCore();
	}

	/**
	 * (non-PHPdoc)
	 * @see tx_mksearch_tests_SolrTestcase::tearDown()
	 */
	protected function tearDown() {
		parent::tearDown();
		$GLOBALS['TSFE']->fe_user->groupData['uid'] = $this->groupDataBackup;
	}

	public function copyNeccessaryConfigFiles($destPath) {
		$neccessaryConfigFiles = array(
			'elevate.xml','protwords.txt','stopwords.txt',
			'stopwordsGerman.txt','synonyms.txt','dictionaryGerman.txt','synonymsGerman.txt',
		);
		$this->copyNeccessaryFiles($destPath, 'conf/', $neccessaryConfigFiles);
	}

	public function copyNeccessaryLibFiles($destPath) {
		$neccessaryLibFiles = array(
			'dmk-solr-core-3.5.0.jar',
			'Dmk-MultiWordSpellingQueryConverter-Solr4.jar'
		);
		$this->copyNeccessaryFiles($destPath, 'lib/', $neccessaryLibFiles);
	}

	private function copyNeccessaryFiles($destPath,$filesPath, $neccessaryFiles) {
		$this->createInstanceDir($destPath);

		foreach ($neccessaryFiles as $neccessaryFile) {
			copy(
				tx_rnbase_util_Files::getFileAbsFileName(
					'EXT:mksearch/solr/'. $filesPath . $neccessaryFile
				),$destPath . $filesPath .$neccessaryFile
			);
		}
	}

	/**
	 * @integration
	 */
	public function testDocIsFoundIfNoFeGroupSet() {
		$this->indexDocsFromYaml(tx_mksearch_tests_Util::getFixturePath('solr/fegroup/nogroupset.yaml'));

		$options = $this->getOptionsFromFilter();

		self::assertStringStartsWith(
			'(-fe_group_mi:[* TO *] AND uid:[* TO *]) OR fe_group_mi:0',
			$options['fq'],
			'scheinbar falsche filter query'
		);

		$result = $this->search($options);

		self::assertEquals(1, $result['numFound'], 'nicht nur 1 doc gefunden.');
		self::assertEquals(1, $result['items'][0]->record['uid'], 'uid falsch');
		self::assertEquals(
			'fegrouptest',
			$result['items'][0]->record['contentType'],
			'contentType falsch'
		);
	}

	/**
	 * @integration
	 */
	public function testDocIsFoundIfFeGroupSetToZero() {
		$this->indexDocsFromYaml(tx_mksearch_tests_Util::getFixturePath('solr/fegroup/groupsettozero.yaml'));

		$options = $this->getOptionsFromFilter();

		self::assertStringStartsWith(
			'(-fe_group_mi:[* TO *] AND uid:[* TO *]) OR fe_group_mi:0',
			$options['fq'],
			'scheinbar falsche filter query'
		);

		$result = $this->search($options);

		self::assertEquals(1, $result['numFound'], 'nicht nur 1 doc gefunden.');
		self::assertEquals(1, $result['items'][0]->record['uid'], 'uid falsch');
		self::assertEquals(
			'fegrouptest',
			$result['items'][0]->record['contentType'],
			'contentType falsch'
		);
	}

	/**
	 * @integration
	 */
	public function testDocIsFoundIfCorrectFeGroupSet() {
		$this->indexDocsFromYaml(tx_mksearch_tests_Util::getFixturePath('solr/fegroup/groupsetto1.yaml'));

		$GLOBALS['TSFE']->fe_user->groupData['uid'] = array(1);
		$options = $this->getOptionsFromFilter();

		self::assertStringStartsWith(
			'(-fe_group_mi:[* TO *] AND uid:[* TO *]) OR fe_group_mi:0',
			$options['fq'],
			'scheinbar falsche filter query'
		);

		$result = $this->search($options);

		self::assertEquals(1, $result['numFound'], 'nicht nur 1 doc gefunden.');
		self::assertEquals(1, $result['items'][0]->record['uid'], 'uid falsch');
		self::assertEquals(
			'fegrouptest',
			$result['items'][0]->record['contentType'],
			'contentType falsch'
		);
	}

	/**
	 * @integration
	 */
	public function testDocIsNotFoundIfIncorrectFeGroupSet() {
		$this->indexDocsFromYaml(tx_mksearch_tests_Util::getFixturePath('solr/fegroup/groupsetto1.yaml'));

		$GLOBALS['TSFE']->fe_user->groupData['uid'] = array(2);
		$options = $this->getOptionsFromFilter();

		self::assertStringStartsWith(
			'(-fe_group_mi:[* TO *] AND uid:[* TO *]) OR fe_group_mi:0',
			$options['fq'],
			'scheinbar falsche filter query'
		);

		$result = $this->search($options);

		self::assertEquals(0, $result['numFound'], 'doch etwas gefunden');
		self::assertEmpty($result['items'], 'doch items etwas gefunden');
	}

	private function getOptionsFromFilter() {
		$parameters = tx_rnbase::makeInstance('tx_rnbase_parameters');
		$config = tx_mksearch_tests_Util::loadPageTS4BE();
		$config['searchsolr.']['filter.']['default.']['force'] = 1;
		$filter = tx_rnbase::makeInstance(
			'tx_mksearch_filter_SolrBase',
			$parameters,
			tx_mksearch_tests_Util::loadConfig4BE($config),
			'searchsolr.'
		);
		$fields = $options = array();
		$filter->init($fields,$options);

		return $options;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/class.tx_mksearch_tests_SolrTestcase.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/class.tx_mksearch_tests_SolrTestcase.php']);
}