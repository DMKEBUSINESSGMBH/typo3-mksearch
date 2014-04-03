<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 das Medienkombinat GmbH
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

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_mksearch_tests_SolrTestcase');
require_once(t3lib_extMgm::extPath('mksearch') . 'tests/filter/class.tx_mksearch_tests_filter_SolrBase_solr_testcase.php');

/**
 * @author Hannes Bochmann
 * @integration
 */
class tx_mksearch_tests_solr_conf_Solrconfig_testcase extends tx_mksearch_tests_SolrTestcase {

	protected $instanceDir = 'EXT:mksearch/tests/solrtestcore/';
	protected $configFile = 'EXT:mksearch/solr/conf/solrconfig-3.x.xml';
	protected $configFileForSolr4 = 'EXT:mksearch/solr/conf/solrconfig-4.x.xml';
	protected $schemaFile = 'EXT:mksearch/solr/conf/schema.xml';
	protected $defaultContentType = 'fegrouptest';

	/**
	 * (non-PHPdoc)
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();
		$this->initAbsolutePathsForConfigs();
		t3lib_div::rmdir($this->instanceDir,true);

		$filterTestcase = new tx_mksearch_tests_filter_SolrBase_solr_testcase();
		$filterTestcase->copyNeccessaryConfigFiles($this->instanceDir);
		$filterTestcase->copyNeccessaryLibFiles($this->instanceDir);

		$this->createCore();
	}

	/**
	 * @integration
	 */
	public function testDocIsFoundIfNoStarttimeSet() {
		$this->indexDocsFromYaml($this->getFixturePath('solr/start-endtime/nostarttimeset.yaml'));

		$result = $this->search($this->getOptions());

		$this->assertEquals(1, $result['numFound'], 'nicht nur 1 doc gefunden.');
		$this->assertEquals(1, $result['items'][0]->record['uid'], 'uid falsch');
		$this->assertEquals(
			$this->defaultContentType,
			$result['items'][0]->record['contentType'],
			'contentType falsch'
		);
	}

	/**
	 * @integration
	 */
	public function testDocIsFoundIfStarttimeSetToPast() {
		$this->indexDocsFromYaml($this->getFixturePath('solr/start-endtime/starttimesettopast.yaml'));

		$result = $this->search($this->getOptions());

		$this->assertEquals(1, $result['numFound'], 'nicht nur 1 doc gefunden.');
		$this->assertEquals(1, $result['items'][0]->record['uid'], 'uid falsch');
		$this->assertEquals(
			$this->defaultContentType,
			$result['items'][0]->record['contentType'],
			'contentType falsch'
		);
	}

	/**
	 * @integration
	 */
	public function testDocIsNotFoundIfStarttimeSetToFuture() {
		$this->indexDocsFromYaml($this->getFixturePath('solr/start-endtime/starttimesettofuture.yaml'));

		$result = $this->search($this->getOptions());

		$this->assertNothingFound($result);
	}

	/**
	 * @integration
	 */
	public function testDocIsFoundIfNoEndtimeSet() {
		$this->indexDocsFromYaml($this->getFixturePath('solr/start-endtime/noendtimeset.yaml'));

		$result = $this->search($this->getOptions());

		$this->assertEquals(1, $result['numFound'], 'nicht nur 1 doc gefunden.');
		$this->assertEquals(1, $result['items'][0]->record['uid'], 'uid falsch');
		$this->assertEquals(
			$this->defaultContentType,
			$result['items'][0]->record['contentType'],
			'contentType falsch'
		);
	}

	/**
	 * @integration
	 */
	public function testDocIsNotFoundIfEndtimeSetToPast() {
		$this->indexDocsFromYaml($this->getFixturePath('solr/start-endtime/endtimesettopast.yaml'));

		$result = $this->search($this->getOptions());

		$this->assertNothingFound($result);
	}

	/**
	 * @integration
	 */
	public function testDocIsFoundIfStarttimeSetToFuture() {
		$this->indexDocsFromYaml($this->getFixturePath('solr/start-endtime/endtimesettofuture.yaml'));

		$result = $this->search($this->getOptions());

		$this->assertEquals(1, $result['numFound'], 'nicht nur 1 doc gefunden.');
		$this->assertEquals(1, $result['items'][0]->record['uid'], 'uid falsch');
		$this->assertEquals(
			$this->defaultContentType,
			$result['items'][0]->record['contentType'],
			'contentType falsch'
		);
	}

	/**
	 * @integration
	 */
	public function testDocIsNotFoundIfStarttimeOkayButEndtimeNot() {
		$this->indexDocsFromYaml($this->getFixturePath('solr/start-endtime/starttimeokaybutendtimenot.yaml'));

		$result = $this->search($this->getOptions());

		$this->assertNothingFound($result);
	}

	/**
	 * @integration
	 */
	public function testDocIsNotFoundIfEndtimeOkayButStarttimeNot() {
		$this->indexDocsFromYaml($this->getFixturePath('solr/start-endtime/endtimeokaybutstarttimenot.yaml'));

		$result = $this->search($this->getOptions());

		$this->assertNothingFound($result);
	}

	/**
	 * @integration
	 */
	public function testDocIsFoundIfStarttimeAndEndtimeOkay() {
		$this->indexDocsFromYaml($this->getFixturePath('solr/start-endtime/starttimeandendtimeokay.yaml'));

		$result = $this->search($this->getOptions());

		$this->assertEquals(1, $result['numFound'], 'nicht nur 1 doc gefunden.');
		$this->assertEquals(1, $result['items'][0]->record['uid'], 'uid falsch');
		$this->assertEquals(
			$this->defaultContentType,
			$result['items'][0]->record['contentType'],
			'contentType falsch'
		);
	}

	protected function getFixturePath($file) {
		return tx_mksearch_tests_Util::getFixturePath($file);
	}

	protected function getOptions() {
		return array('qt' => 'search');
	}
}