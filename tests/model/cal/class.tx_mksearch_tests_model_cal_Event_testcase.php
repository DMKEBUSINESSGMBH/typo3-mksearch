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
tx_rnbase::load('tx_mksearch_tests_Util');


require_once(t3lib_extMgm::extPath('mksearch') . 'lib/Apache/Solr/Document.php');

/**
 * Wir müssen in diesem Fall mit der DB testen da wir die pages
 * Tabelle benötigen
 * @author Hannes Bochmann
 * 
 */
class tx_mksearch_tests_model_cal_Event_testcase extends tx_phpunit_database_testcase {
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
	protected function setUp() {
		if(!t3lib_extMgm::isLoaded('cal')) {
			$this->markTestSkipped('cal ist nicht installiert');
		}
		
		//das devlog stört nur bei der Testausführung im BE und ist da auch
		//vollkommen unnötig
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['devlog']['nolog'] = true;
		
		$this->createDatabase();
		$this->db = $this->useTestDatabase();
		$this->importStdDB();
		$extensions = array('cms','mksearch','cal');
		
		//templavoila und realurl brauchen wir da es im BE sonst Warnungen hagelt
		//und man die Testergebnisse nicht sieht ;-)
		if(t3lib_extMgm::isLoaded('templavoila')) $extensions[] = 'templavoila';
		if(t3lib_extMgm::isLoaded('realurl')) $extensions[] = 'realurl';
		$this->importExtensions($extensions);
		
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/cal_event_category_mm.xml'));
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/cal_category.xml'));
	}

	/**
	 * tearDown() = destroy DB etc.
	 */
	protected function tearDown () {
		$this->cleanDatabase();
		$this->dropDatabase();
		$GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db);

		$GLOBALS['BE_USER']->setWorkspace($this->workspaceIdAtStart);
	}
	
	/**
	 */
	public function testgetCategoriesReturnsCorrectCategories() {
		$event = tx_rnbase::makeInstance(
			'tx_mksearch_model_cal_Event',array('uid' => 1)
		);
		
		$categories = $event->getCategories();
		
		$this->assertEquals(2, count($categories), 'Mehr Kategorien als erwartet.');
		$this->assertEquals(
			'First Category',
			$categories[0]->getTitle(),
			'Titel der 1. Kategorie falsch'
		);
		$this->assertEquals(
			'Second Category',
			$categories[1]->getTitle(),
			'Titel der 2. Kategorie falsch'
		);
	}
}