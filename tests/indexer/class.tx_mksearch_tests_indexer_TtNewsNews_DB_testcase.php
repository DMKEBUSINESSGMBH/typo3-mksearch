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
tx_rnbase::load('tx_mksearch_model_IndexerDocumentBase');
tx_rnbase::load('tx_mksearch_tests_Util');
tx_rnbase::load('tx_mksearch_indexer_TtNewsNews');


require_once(t3lib_extMgm::extPath('mksearch') . 'lib/Apache/Solr/Document.php');

/**
 * Wir müssen in diesem Fall mit der DB testen da wir die pages
 * Tabelle benötigen
 * @author Hannes Bochmann
 */
class tx_mksearch_tests_indexer_TtNewsNews_DB_testcase extends tx_phpunit_database_testcase {
	protected $workspaceIdAtStart;
	protected $db;

	/**
	 * Klassenkonstruktor
	 *
	 * @param string $name
	 */
	public function __construct ($name=null) {
		global $TYPO3_DB, $BE_USER;

		parent::__construct ($name);
		$TYPO3_DB->debugOutput = TRUE;

		$this->workspaceIdAtStart = $BE_USER->workspace;
		$BE_USER->setWorkspace(0);
	}

	/**
	 * setUp() = init DB etc.
	 */
	public function setUp() {
		//das devlog stört nur bei der Testausführung im BE und ist da auch
		//vollkommen unnötig
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['devlog']['nolog'] = true;
		
		$this->createDatabase();
		// assuming that test-database can be created otherwise PHPUnit will skip the test
		$this->db = $this->useTestDatabase();
		$this->importStdDB();
		$aExtensions = array('cms','mksearch','tt_news');
		//templavoila und realurl brauchen wir da es im BE sonst Warnungen hagelt
		//und man die Testergebnisse nicht sieht ;-)
		if(t3lib_extMgm::isLoaded('templavoila')) $aExtensions[] = 'templavoila';
		if(t3lib_extMgm::isLoaded('realurl')) $aExtensions[] = 'realurl';
		$this->importExtensions($aExtensions,true);
		
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/tt_news_cat_mm.xml'));
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/tt_news_cat.xml'));
	}

	/**
	 * tearDown() = destroy DB etc.
	 */
	public function tearDown () {
		$this->cleanDatabase();
		$this->dropDatabase();
		$GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db);

		$GLOBALS['BE_USER']->setWorkspace($this->workspaceIdAtStart);
	}
	
	/**
	 * Prüft ob nur die Elemente indiziert werden, die im
	 * angegebenen Seitenbaum liegen
	 */
	public function testPrepareSearchDataWithIncludeCategoriesOption() {
		$indexer = new tx_mksearch_indexer_TtNewsNews();
		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$options = array(
			'include.' => array(
				'categories.' => array(
					0 => 1
				)
			)
		);

		$aResult = array('uid' => 1);
		$aIndexDoc = $indexer->prepareSearchData('tt_news', $aResult, $indexDoc, $options);
		$this->assertNotNull($aIndexDoc,'Das Element wurde doch nicht indziert!');
		
		$aResult = array('uid' => 2);
		$aIndexDoc = $indexer->prepareSearchData('tt_news', $aResult, $indexDoc, $options);
		$this->assertNull($aIndexDoc,'Das Element wurde doch indziert!');
	}
	
/**
	 * Prüft ob nur die Elemente indiziert werden, die im
	 * angegebenen Seitenbaum liegen
	 */
	public function testPrepareSearchDataWithExcludeCategoriesOption() {
		$indexer = new tx_mksearch_indexer_TtNewsNews();
		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = new tx_mksearch_model_IndexerDocumentBase($extKey, $cType);
		$options = array(
			'exclude.' => array(
				'categories.' => array(
					0 => 1
				)
			)
		);

		$aResult = array('uid' => 1);
		$aIndexDoc = $indexer->prepareSearchData('tt_news', $aResult, $indexDoc, $options);
		$this->assertNull($aIndexDoc,'Das Element wurde doch indziert!');
		
		$aResult = array('uid' => 2);
		$aIndexDoc = $indexer->prepareSearchData('tt_news', $aResult, $indexDoc, $options);
		$this->assertNotNull($aIndexDoc,'Das Element wurde doch nicht indziert!');
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContent_testcase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContent_testcase.php']);
}

?>