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
 * @author Hannes Bochmann
 */
class tx_mksearch_tests_indexer_seminars_Seminar_testcase extends tx_phpunit_database_testcase {
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
		$this->createDatabase();
		// assuming that test-database can be created otherwise PHPUnit will skip the test
		$this->db = $this->useTestDatabase();
		$this->importStdDB();
		$aExtensions = array('cms','mksearch','seminars');
		//templavoila und realurl brauchen wir da es im BE sonst Warnungen hagelt
		//und man die Testergebnisse nicht sieht
		if(t3lib_extMgm::isLoaded('templavoila')) $aExtensions[] = 'templavoila';
		if(t3lib_extMgm::isLoaded('realurl')) $aExtensions[] = 'realurl';
		$this->importExtensions($aExtensions,true);
		
		//if we got here all extensions got successfully imported
		//so now we can load the appropriate classes
		tx_rnbase::load('tx_seminars_objectfromdb');
		
		//das devlog stört nur bei der Testausführung im BE und ist da auch
		//vollkommen unnötig
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['devlog']['nolog'] = true;
		
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/seminars.xml'));
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/seminars_speakers.xml'));
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/seminars_speakers_mm.xml'));
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/seminars_target_groups.xml'));
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/seminars_target_groups_mm.xml'));
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/seminars_sites.xml'));
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/seminars_place_mm.xml'));
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/seminars_categories.xml'));
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/seminars_categories_mm.xml'));
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/seminars_organizers.xml'));
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/seminars_organizers_mm.xml'));
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/seminars_timeslots.xml'));
		$this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/seminars_timeslots_speakers_mm.xml'));
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
	 * prüft ob ein seminar vom typ 0 richtig indiziert wird
	 */
	public function testPrepareSearchDataWithTableSeminarsAndTypeIs0() {
		//Testdaten aus DB holen
		$aOptions = array(
			'where' => 'tx_seminars_seminars.uid=1',
			'enablefieldsoff' => true
		);
		$aResult = tx_rnbase_util_DB::doSelect('*', 'tx_seminars_seminars', $aOptions);

		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_seminars_Seminar');
		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options = array();
		
		$aIndexDoc = $indexer->prepareSearchData('tx_seminars_seminars', $aResult[0], $indexDoc, $options)->getData();
		
		$this->assertEquals('1. Fortbildung',$aIndexDoc['title_s']->getValue(),'Es wurde nicht der richtige Titel indiziert!');
		$this->assertEquals('1. Fortbildung Untertitel',$aIndexDoc['subtitle_s']->getValue(),'Es wurde nicht der richtige Untertitel indiziert!');
		$this->assertEquals('1. Fortbildung',$aIndexDoc['realtitle_s']->getValue(),'Es wurde nicht der richtige Realtitel indiziert!');
		$this->assertEquals('Hier gibt es viel zu lernen',$aIndexDoc['description_s']->getValue(),'Es wurde nicht die richtige Beschreibung indiziert!');
		$this->assertEquals(74,$aIndexDoc['accreditationnumber_s']->getValue(),'Es wurde nicht der richtige Akkreditierungsnummer indiziert!');
		$this->assertEquals(5.00,$aIndexDoc['currentpriceregular_s']->getValue(),'Es wurde nicht der richtige aktuelle normale Preis indiziert!');
		$this->assertEquals(100,$aIndexDoc['credit_points_s']->getValue(),'Es wurden nicht die richtigen Credit Points indiziert!');
		$this->assertEquals('1. Fortbildung, ',$aIndexDoc['titleanddate_s']->getValue(),'Es wurde nicht der richtige Titel mit Zeit indiziert!');
		$this->assertEquals(array(0=>'1304267200'),$aIndexDoc['begin_date_ms']->getValue(),'Es wurde nicht der richtige Startzeitpunkt  indiziert!');
		$this->assertEquals(array(0=>'1306287200'),$aIndexDoc['end_date_ms']->getValue(),'Es wurde nicht der richtige Endzeitpunkt indiziert!');
		$this->assertEquals(array(0=>'1. Kategorie',1=>'2. Kategorie'),$aIndexDoc['categories_title_ms']->getValue(),'Es wurden nicht die richtigen Kategorietitel indiziert!');
		$this->assertEquals(array(0=>'1. Veranstalter'),$aIndexDoc['organizers_title_ms']->getValue(),'Es wurden nicht die richtigen Veranstaltertitel indiziert!');
		$this->assertEquals(array(0=>'1. Stadt',1=>'2. Stadt'),$aIndexDoc['place_city_ms']->getValue(),'Es wurden nicht die richtigen Städte indiziert!');
		$this->assertEquals(array(0=>'1. Site',1=>'2. Site'),$aIndexDoc['place_title_ms']->getValue(),'Es wurden nicht die richtigen Titel der Sites indiziert!');
		$this->assertEquals(array(0=>'1. Zielgruppe',1=>'2. Zielgruppe'),$aIndexDoc['targetgroups_title_ms']->getValue(),'Es wurden nicht die richtigen Zielgruppen indiziert!');
		$this->assertEquals(array(0=>'1. Referent',1=>'2. Referent'),$aIndexDoc['speakers_title_ms']->getValue(),'Es wurden nicht die richtigen Sprechertitel indiziert!');
		$this->assertEquals(array(0=>'1',1=>'2'),$aIndexDoc['speakers_gender_ms']->getValue(),'Es wurden nicht die richtigen Sprechergeschlechte indiziert!');
		$this->assertEquals(array(0=>'1. Organisation',1=>'2. Organisation'),$aIndexDoc['speakers_organization_ms']->getValue(),'Es wurden nicht die richtigen Sprecherorganisationen indiziert!');
		$this->assertEquals(array(0=>'www.first.de',1=>'www.second.de'),$aIndexDoc['speakers_homepage_ms']->getValue(),'Es wurden nicht die richtigen Sprecherhomepages indiziert!');
		$this->assertEquals(array(0=>'1. Notizen',1=>'2. Notizen'),$aIndexDoc['speakers_notes_ms']->getValue(),'Es wurden nicht die richtigen Sprechernotizen indiziert!');
		$this->assertEquals(array(0=>'1. Addresse',1=>'2. Addresse'),$aIndexDoc['speakers_address_ms']->getValue(),'Es wurden nicht die richtigen Sprecheradressen indiziert!');
		$this->assertEquals(array(0=>'0001',1=>'0005'),$aIndexDoc['speakers_phone_work_ms']->getValue(),'Es wurden nicht die richtigen Sprechertelefon Arbeit indiziert!');
		$this->assertEquals(array(0=>'0002',1=>'0006'),$aIndexDoc['speakers_phone_home_ms']->getValue(),'Es wurden nicht die richtigen Sprechertelefon zu Hause indiziert!');
		$this->assertEquals(array(0=>'0003',1=>'0007'),$aIndexDoc['speakers_phone_mobile_ms']->getValue(),'Es wurden nicht die richtigen Sprechertelefon Mobil indiziert!');
		$this->assertEquals(array(0=>'0004',1=>'0008'),$aIndexDoc['speakers_fax_ms']->getValue(),'Es wurden nicht die richtigen Sprecherfax indiziert!');
		$this->assertEquals(array(0=>'my@email.de',1=>'your@email.de'),$aIndexDoc['speakers_email_ms']->getValue(),'Es wurden nicht die richtigen Sprecheremail indiziert!');
		$this->assertEquals(array(0=>'will be announced'),$aIndexDoc['timeslots_entry_date_ms']->getValue(),'Es wurden nicht die richtigen Timeslot Entry Dates indiziert!');
		$this->assertEquals(array(0=>'1. Site',1=>'2. Site'),$aIndexDoc['timeslots_place_ms']->getValue(),'Es wurden nicht die richtigen Timeslot Places indiziert!');
		$this->assertEquals(array(0=>'1. Referent',1=>'2. Referent'),$aIndexDoc['timeslots_speakers_ms']->getValue(),'Es wurden nicht die richtigen Timeslot Speakers indiziert!');
	}
	
	/**
	 * Prüft ob das seminar auf gelöscht gesetzt wird
	 */
	public function testPrepareSearchDataWithTableSeminarsAndTypeIs0AndIsHiddenOrDeleted() {
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_seminars_Seminar');
		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options = array();
		
		//Testdaten aus DB holen
		$aOptions = array(
			'where' => 'tx_seminars_seminars.uid=1',
			'enablefieldsoff' => true
		);
		$aResult = tx_rnbase_util_DB::doSelect('*', 'tx_seminars_seminars', $aOptions);

		//manipulate the seminar and set it to deleted
		$aResult[0]['deleted'] = 1;
		
		$oIndexDoc = $indexer->prepareSearchData('tx_seminars_seminars', $aResult[0], $indexDoc, $options);
		$this->assertTrue($oIndexDoc->getDeleted(), 'Element sollte gelöscht werden, da auf deleted');
		
		//manipulate the seminar and set it to hidden
		$aResult[0]['deleted'] = 0;
		$aResult[0]['hidden'] = 1;
		
		$oIndexDoc = $indexer->prepareSearchData('tx_seminars_seminars', $aResult[0], $indexDoc, $options);
		$this->assertTrue($oIndexDoc->getDeleted(), 'Element sollte gelöscht werden, da auf deleted');
	}
	
	/**
	 * Prüft ob nur die Elemente indiziert werden, die im
	 * angegebenen Seitenbaum liegen
	 */
	public function testPrepareSearchDataWithTableSeminarsAndTypeIs1() {
		//Testdaten aus DB holen
		$aOptions = array(
			'where' => 'tx_seminars_seminars.uid=2',
			'enablefieldsoff' => true
		);
		$aResult = tx_rnbase_util_DB::doSelect('*', 'tx_seminars_seminars', $aOptions);

		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_seminars_Seminar');
		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options = array();
		
		$aIndexDoc = $indexer->prepareSearchData('tx_seminars_seminars', $aResult[0], $indexDoc, $options)->getData();
		
		$this->assertEquals('1. Fortbildungsthema',$aIndexDoc['title_s']->getValue(),'Es wurde nicht der richtige Titel indiziert!');
		$this->assertEquals('1. Fortbildungsthema Untertitel',$aIndexDoc['subtitle_s']->getValue(),'Es wurde nicht der richtige Untertitel indiziert!');
		$this->assertEquals('1. Fortbildungsthema',$aIndexDoc['realtitle_s']->getValue(),'Es wurde nicht der richtige Realtitel indiziert!');
		$this->assertEquals('Hier gibt es viel mehr zu lernen',$aIndexDoc['description_s']->getValue(),'Es wurde nicht die richtige Beschreibung indiziert!');
		$this->assertEquals(54,$aIndexDoc['accreditationnumber_s']->getValue(),'Es wurde nicht der richtige Akkreditierungsnummer indiziert!');
		$this->assertEquals(8.00,$aIndexDoc['currentpriceregular_s']->getValue(),'Es wurde nicht der richtige aktuelle normale Preis indiziert!');
		$this->assertEquals('1. Fortbildungsthema',$aIndexDoc['titleanddate_s']->getValue(),'Es wurde nicht der richtige Titel mit Zeit indiziert!');
		$this->assertEquals(array(0=>'1304267200',1=>'1304268200'),$aIndexDoc['begin_date_ms']->getValue(),'Es wurde nicht der richtige Startzeitpunkt  indiziert!');
		$this->assertEquals(array(0=>'1306287200',1=>'1306288200'),$aIndexDoc['end_date_ms']->getValue(),'Es wurde nicht der richtige Endzeitpunkt indiziert!');
		$this->assertEquals(array(0=>'1. Kategorie',1=>'2. Kategorie',2=>'3. Kategorie'),$aIndexDoc['categories_title_ms']->getValue(),'Es wurden nicht die richtigen Kategorietitel indiziert!');
		$this->assertEquals(array(0=>'1. Veranstalter',1=>'2. Veranstalter'),$aIndexDoc['organizers_title_ms']->getValue(),'Es wurden nicht die richtigen Veranstaltertitel indiziert!');
		$this->assertEquals(array(0=>'1. Zielgruppe',1=>'2. Zielgruppe'),$aIndexDoc['targetgroups_title_ms']->getValue(),'Es wurden nicht die richtigen Zielgruppen indiziert!');
		$this->assertEquals(array(0=>'1. Stadt',1=>'2. Stadt'),$aIndexDoc['place_city_ms']->getValue(),'Es wurden nicht die richtigen Städte indiziert!');
		$this->assertEquals(array(0=>'1. Site',1=>'2. Site'),$aIndexDoc['place_title_ms']->getValue(),'Es wurden nicht die richtigen Titel der Sites indiziert!');
		$this->assertEquals(array(0=>'1. Referent',1=>'2. Referent'),$aIndexDoc['speakers_title_ms']->getValue(),'Es wurden nicht die richtigen Sprechertitel indiziert!');
		$this->assertEquals(array(0=>'1',1=>'2'),$aIndexDoc['speakers_gender_ms']->getValue(),'Es wurden nicht die richtigen Sprechergeschlechte indiziert!');
		$this->assertEquals(array(0=>'1. Organisation',1=>'2. Organisation'),$aIndexDoc['speakers_organization_ms']->getValue(),'Es wurden nicht die richtigen Sprecherorganisationen indiziert!');
		$this->assertEquals(array(0=>'www.first.de',1=>'www.second.de'),$aIndexDoc['speakers_homepage_ms']->getValue(),'Es wurden nicht die richtigen Sprecherhomepages indiziert!');
		$this->assertEquals(array(0=>'1. Notizen',1=>'2. Notizen'),$aIndexDoc['speakers_notes_ms']->getValue(),'Es wurden nicht die richtigen Sprechernotizen indiziert!');
		$this->assertEquals(array(0=>'1. Addresse',1=>'2. Addresse'),$aIndexDoc['speakers_address_ms']->getValue(),'Es wurden nicht die richtigen Sprecheradressen indiziert!');
		$this->assertEquals(array(0=>'0001',1=>'0005'),$aIndexDoc['speakers_phone_work_ms']->getValue(),'Es wurden nicht die richtigen Sprechertelefon Arbeit indiziert!');
		$this->assertEquals(array(0=>'0002',1=>'0006'),$aIndexDoc['speakers_phone_home_ms']->getValue(),'Es wurden nicht die richtigen Sprechertelefon zu Hause indiziert!');
		$this->assertEquals(array(0=>'0003',1=>'0007'),$aIndexDoc['speakers_phone_mobile_ms']->getValue(),'Es wurden nicht die richtigen Sprechertelefon Mobil indiziert!');
		$this->assertEquals(array(0=>'0004',1=>'0008'),$aIndexDoc['speakers_fax_ms']->getValue(),'Es wurden nicht die richtigen Sprecherfax indiziert!');
		$this->assertEquals(array(0=>'my@email.de',1=>'your@email.de'),$aIndexDoc['speakers_email_ms']->getValue(),'Es wurden nicht die richtigen Sprecheremail indiziert!');
		$this->assertEquals(array(0=>'will be announced'),$aIndexDoc['timeslots_entry_date_ms']->getValue(),'Es wurden nicht die richtigen Timeslot Entry Dates indiziert!');
		$this->assertEquals(array(0=>'1. Site',1=>'2. Site'),$aIndexDoc['timeslots_place_ms']->getValue(),'Es wurden nicht die richtigen Timeslot Places indiziert!');
		$this->assertEquals(array(0=>'1. Referent',1=>'2. Referent'),$aIndexDoc['timeslots_speakers_ms']->getValue(),'Es wurden nicht die richtigen Timeslot Speakers indiziert!');
	}
	
	/**
	 * Prüft ob nur die Elemente indiziert werden, die im
	 * angegebenen Seitenbaum liegen
	 */
	public function testPrepareSearchDataWithTableSeminarsAndTypeIs1ButNoDates() {
		//Testdaten aus DB holen
		$aOptions = array(
			'where' => 'tx_seminars_seminars.uid=5',
			'enablefieldsoff' => true
		);
		$aResult = tx_rnbase_util_DB::doSelect('*', 'tx_seminars_seminars', $aOptions);

		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_seminars_Seminar');
		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options = array();
		
		$aIndexDoc = $indexer->prepareSearchData('tx_seminars_seminars', $aResult[0], $indexDoc, $options);
		
		$this->assertNull($aIndexDoc,'Es wurde nicht null zurück gegeben!');
	}
	
	/**
	 * Prüft ob das seminar auf gelöscht gesetzt wird
	 */
	public function testPrepareSearchDataWithTableSeminarsAndTypeIs1AndIsHiddenOrDeleted() {
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_seminars_Seminar');
		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options = array();
		
		//Testdaten aus DB holen
		$aOptions = array(
			'where' => 'tx_seminars_seminars.uid=1',
			'enablefieldsoff' => true
		);
		$aResult = tx_rnbase_util_DB::doSelect('*', 'tx_seminars_seminars', $aOptions);

		//manipulate the seminar and set it to deleted
		$aResult[0]['deleted'] = 1;
		
		$oIndexDoc = $indexer->prepareSearchData('tx_seminars_seminars', $aResult[0], $indexDoc, $options);
		$this->assertTrue($oIndexDoc->getDeleted(), 'Element sollte gelöscht werden, da auf deleted');
		
		//manipulate the seminar and set it to hidden
		$aResult[0]['deleted'] = 0;
		$aResult[0]['hidden'] = 1;
		
		$oIndexDoc = $indexer->prepareSearchData('tx_seminars_seminars', $aResult[0], $indexDoc, $options);
		$this->assertTrue($oIndexDoc->getDeleted(), 'Element sollte gelöscht werden, da auf deleted');
	}
	
	/**
	 * Prüft ob das richtige seminar in die queue gelegt wurde
	 */
	public function testPrepareSearchDataWithTableSeminarsAndTypeIs2() {
		//Testdaten aus DB holen
		$aOptions = array(
			'where' => 'tx_seminars_seminars.uid=3',
			'enablefieldsoff' => true
		);
		$aResult = tx_rnbase_util_DB::doSelect('*', 'tx_seminars_seminars', $aOptions);

		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_seminars_Seminar');
		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options = array();
		
		$aIndexDoc = $indexer->prepareSearchData('tx_seminars_seminars', $aResult[0], $indexDoc, $options);
		//nichtzs direkt indiziert?
		$this->assertNull($aIndexDoc,'Es wurde nicht null zurück gegeben!');
		
		$aOptions = array(
			'enablefieldsoff' => true
		);
		$aResult = tx_rnbase_util_DB::doSelect('*', 'tx_mksearch_queue', $aOptions);
		
		$this->assertEquals(1,count($aResult),'Es wurde nicht der richtige Anzahl in die queue gelegt!');
		$this->assertEquals('tx_seminars_seminars',$aResult[0]['tablename'],'Es wurde nicht das richtige Seminar (tablename) in die queue gelegt!');
		$this->assertEquals(2,$aResult[0]['recid'],'Es wurde nicht das richtige Seminar (recid) in die queue gelegt!');
	}
	
	/**
	 * Prüft ob der indexer den korrekten Bereich in die queue legt bei geänderten Typ
	 */
	public function testPrepareSearchDataPutsCorrectDocsInQueueWhenTableCategories() {
		$aResult = array('uid'=>1);
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_seminars_Seminar');
		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options = array();
		
		$aIndexDoc = $indexer->prepareSearchData('tx_seminars_categories', $aResult, $indexDoc, $options);
		//nichtzs direkt indiziert?
		$this->assertNull($aIndexDoc,'Es wurde nicht null zurück gegeben!');
		
		$aOptions = array(
			'enablefieldsoff' => true
		);
		$aResult = tx_rnbase_util_DB::doSelect('*', 'tx_mksearch_queue', $aOptions);
		
		$this->assertEquals(2,count($aResult),'Es wurde nicht der richtige Anzahl in die queue gelegt!');
		$this->assertEquals('tx_seminars_seminars',$aResult[0]['tablename'],'Es wurde nicht das richtige Seminar (tablename) in die queue gelegt!');
		$this->assertEquals(1,$aResult[0]['recid'],'Es wurde nicht das richtige Seminar (recid) in die queue gelegt!');
		$this->assertEquals('tx_seminars_seminars',$aResult[1]['tablename'],'Es wurde nicht das richtige Seminar (tablename) in die queue gelegt!');
		$this->assertEquals(2,$aResult[1]['recid'],'Es wurde nicht das richtige Seminar (recid) in die queue gelegt!');
	}
	
	/**
	 * Prüft ob der indexer den korrekten Bereich in die queue legt bei geänderten Typ
	 */
	public function testPrepareSearchDataPutsCorrectDocsInQueueWhenTableOrganizers() {
		$aResult = array('uid'=>1);
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_seminars_Seminar');
		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options = array();
		
		$aIndexDoc = $indexer->prepareSearchData('tx_seminars_organizers', $aResult, $indexDoc, $options);
		//nichtzs direkt indiziert?
		$this->assertNull($aIndexDoc,'Es wurde nicht null zurück gegeben!');
		
		$aOptions = array(
			'enablefieldsoff' => true
		);
		$aResult = tx_rnbase_util_DB::doSelect('*', 'tx_mksearch_queue', $aOptions);
		
		$this->assertEquals(2,count($aResult),'Es wurde nicht der richtige Anzahl in die queue gelegt!');
		$this->assertEquals('tx_seminars_seminars',$aResult[0]['tablename'],'Es wurde nicht das richtige Seminar (tablename) in die queue gelegt!');
		$this->assertEquals(1,$aResult[0]['recid'],'Es wurde nicht das richtige Seminar (recid) in die queue gelegt!');
		$this->assertEquals('tx_seminars_seminars',$aResult[1]['tablename'],'Es wurde nicht das richtige Seminar (tablename) in die queue gelegt!');
		$this->assertEquals(3,$aResult[1]['recid'],'Es wurde nicht das richtige Seminar (recid) in die queue gelegt!');
	}
	
	/**
	 * Prüft ob der indexer den korrekten Bereich in die queue legt bei geänderten Typ
	 */
	public function testPrepareSearchDataPutsCorrectDocsInQueueWhenTableSites() {
		$aResult = array('uid'=>2);
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_seminars_Seminar');
		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options = array();
		
		$aIndexDoc = $indexer->prepareSearchData('tx_seminars_sites', $aResult, $indexDoc, $options);
		//nichtzs direkt indiziert?
		$this->assertNull($aIndexDoc,'Es wurde nicht null zurück gegeben!');
		
		$aOptions = array(
			'enablefieldsoff' => true
		);
		$aResult = tx_rnbase_util_DB::doSelect('*', 'tx_mksearch_queue', $aOptions);
		
		$this->assertEquals(2,count($aResult),'Es wurde nicht der richtige Anzahl in die queue gelegt!');
		$this->assertEquals('tx_seminars_seminars',$aResult[0]['tablename'],'Es wurde nicht das richtige Seminar (tablename) in die queue gelegt!');
		$this->assertEquals(1,$aResult[0]['recid'],'Es wurde nicht das richtige Seminar (recid) in die queue gelegt!');
		$this->assertEquals('tx_seminars_seminars',$aResult[1]['tablename'],'Es wurde nicht das richtige Seminar (tablename) in die queue gelegt!');
		$this->assertEquals(4,$aResult[1]['recid'],'Es wurde nicht das richtige Seminar (recid) in die queue gelegt!');
	}
	
	/**
	 * Prüft ob der indexer den korrekten Bereich in die queue legt bei geänderten Typ
	 */
	public function testPrepareSearchDataPutsCorrectDocsInQueueWhenTableSpeakers() {
		$aResult = array('uid'=>2);
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_seminars_Seminar');
		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options = array();
		
		$aIndexDoc = $indexer->prepareSearchData('tx_seminars_speakers', $aResult, $indexDoc, $options);
		//nichtzs direkt indiziert?
		$this->assertNull($aIndexDoc,'Es wurde nicht null zurück gegeben!');
		
		$aOptions = array(
			'enablefieldsoff' => true
		);
		$aResult = tx_rnbase_util_DB::doSelect('*', 'tx_mksearch_queue', $aOptions);
		
		$this->assertEquals(2,count($aResult),'Es wurde nicht der richtige Anzahl in die queue gelegt!');
		$this->assertEquals('tx_seminars_seminars',$aResult[0]['tablename'],'Es wurde nicht das richtige Seminar (tablename) in die queue gelegt!');
		$this->assertEquals(1,$aResult[0]['recid'],'Es wurde nicht das richtige Seminar (recid) in die queue gelegt!');
		$this->assertEquals('tx_seminars_seminars',$aResult[1]['tablename'],'Es wurde nicht das richtige Seminar (tablename) in die queue gelegt!');
		$this->assertEquals(4,$aResult[1]['recid'],'Es wurde nicht das richtige Seminar (recid) in die queue gelegt!');
	}
	
	/**
	 * Prüft ob der indexer den korrekten Bereich in die queue legt bei geänderten Typ
	 */
	public function testPrepareSearchDataPutsCorrectDocsInQueueWhenTableTargetGroups() {
		$aResult = array('uid'=>2);
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_seminars_Seminar');
		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options = array();
		
		$aIndexDoc = $indexer->prepareSearchData('tx_seminars_target_groups', $aResult, $indexDoc, $options);
		//nichtzs direkt indiziert?
		$this->assertNull($aIndexDoc,'Es wurde nicht null zurück gegeben!');
		
		$aOptions = array(
			'enablefieldsoff' => true
		);
		$aResult = tx_rnbase_util_DB::doSelect('*', 'tx_mksearch_queue', $aOptions);
		
		$this->assertEquals(2,count($aResult),'Es wurde nicht der richtige Anzahl in die queue gelegt!');
		$this->assertEquals('tx_seminars_seminars',$aResult[0]['tablename'],'Es wurde nicht das richtige Seminar (tablename) in die queue gelegt!');
		$this->assertEquals(1,$aResult[0]['recid'],'Es wurde nicht das richtige Seminar (recid) in die queue gelegt!');
		$this->assertEquals('tx_seminars_seminars',$aResult[1]['tablename'],'Es wurde nicht das richtige Seminar (tablename) in die queue gelegt!');
		$this->assertEquals(2,$aResult[1]['recid'],'Es wurde nicht das richtige Seminar (recid) in die queue gelegt!');
	}
	
/**
	 * Prüft ob der indexer den korrekten Bereich in die queue legt bei geänderten Typ
	 */
	public function testPrepareSearchDataPutsCorrectDocsInQueueWhenTableTimeslots() {
		$aResult = array('uid'=>4,'seminar'=>4);
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_seminars_Seminar');
		list($extKey, $cType) = $indexer->getContentType();
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options = array();
		
		$aIndexDoc = $indexer->prepareSearchData('tx_seminars_timeslots', $aResult, $indexDoc, $options);
		//nichtzs direkt indiziert?
		$this->assertNull($aIndexDoc,'Es wurde nicht null zurück gegeben!');
		
		$aOptions = array(
			'enablefieldsoff' => true
		);
		$aResult = tx_rnbase_util_DB::doSelect('*', 'tx_mksearch_queue', $aOptions);
		
		$this->assertEquals(1,count($aResult),'Es wurde nicht der richtige Anzahl in die queue gelegt!');
		$this->assertEquals('tx_seminars_seminars',$aResult[0]['tablename'],'Es wurde nicht das richtige Seminar (tablename) in die queue gelegt!');
		$this->assertEquals(4,$aResult[0]['recid'],'Es wurde nicht das richtige Seminar (recid) in die queue gelegt!');
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContent_testcase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContent_testcase.php']);
}

?>