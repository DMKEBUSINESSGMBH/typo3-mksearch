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

/**
 * @author Hannes Bochmann
 */
class tx_mksearch_tests_solr_conf_Solrconfig_testcase extends tx_mksearch_tests_SolrTestcase {

	protected $instanceDir = 'EXT:mksearch/tests/solrtestcore/';
	protected $configFile = 'EXT:mksearch/solr/conf/solrconfig.xml';
	protected $schemaFile = 'EXT:mksearch/solr/conf/schema.xml';
	
	protected function initAbsolutePathsForConfigs() {
		parent::initAbsolutePathsForConfigs();
		
		$this->copyNeccessaryConfigFiles();
	}
	
	private function copyNeccessaryConfigFiles() {
		$this->createInstanceDir($this->instanceDir);
		
		$neccessaryConfigFiles = array(
			'elevate.xml','protwords.txt','stopwords.txt',
			'stopwordsGerman.txt','synonyms.txt','dictionaryGerman.txt'
		);
		
		foreach ($neccessaryConfigFiles as $neccessaryConfigFile) {
			copy(
				t3lib_div::getFileAbsFileName(
					'EXT:mksearch/solr/conf/'.$neccessaryConfigFile
				),$this->instanceDir . 'conf/'.$neccessaryConfigFile
			);
		}
	}
	
	public function testSomething() {
		$this->indexDocsFromYaml(tx_mksearch_tests_Util::getFixturePath('solr/docs.yaml'));
		$this->assertTrue(true);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/class.tx_mksearch_tests_SolrTestcase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/class.tx_mksearch_tests_SolrTestcase.php']);
}