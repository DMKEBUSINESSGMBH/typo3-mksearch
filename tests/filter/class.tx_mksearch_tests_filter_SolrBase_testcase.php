<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_tests_filter
 *  @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2010 Hannes Bochmann <hannes.bochmann@das-medienkombinat.de>
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
 */

/**
 * benötigte Klassen einbinden
 */
require_once t3lib_extMgm::extPath('rn_base', 'class.tx_rnbase.php');
tx_rnbase::load('tx_mksearch_tests_Testcase');
tx_rnbase::load('tx_mksearch_filter_SolrBase');
//damit die User func ausgeführt werden kann, muss sie geladen werden, was auf dem
//CLI und TYPO3 < 4.5 nicht der Fall ist
//im FE geschieht dies durch includeLibs im TS bzw. ab TYPO3 4.5 auch automatisch
//auf dem CLI
tx_rnbase::load('tx_mksearch_util_UserFunc');

/**
 * Testfälle für tx_mksearch_filter_SolrBase
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_tests
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_filter_SolrBase_testcase
	extends tx_mksearch_tests_Testcase {

	protected $oParameters;
	protected $groupDataBackup;

	/**
	 * setUp() = init DB etc.
	 */
	protected function setUp(){
		parent::setUp();
		$this->oParameters = tx_rnbase::makeInstance('tx_rnbase_parameters');
		$this->oParameters->setQualifier('mksearch');
	}

	protected function tearDown() {
		parent::tearDown();
		unset($_GET['mksearch']);
	}

	/**
	 * prüft ob dier Angebote auf die aktuelle Woche beschränkt werden
	 */
	public function testInitReturnsFalseIfNothingSubmittedAndNotForced() {
		//set noHash as we don't need it in tests
		$aConfig = tx_mksearch_tests_Util::loadPageTS4BE();
		$oFilter = tx_rnbase::makeInstance('tx_mksearch_filter_SolrBase',$this->oParameters,tx_mksearch_tests_Util::loadConfig4BE($aConfig),'searchsolr.');

		$fields = array();
		$options = array();
		$this->assertFalse($oFilter->init($fields,$options),'Filter ist scheinbar doch durchgelaufen!');

		//noch prüfen ob bei submit true zurück gegeben wird
		$this->oParameters->offsetSet('submit',true);
		$fields = array();
		$options = array();
		$this->assertTrue($oFilter->init($fields,$options),'Filter ist scheinbar doch nicht durchgelaufen!');
	}

	/**
	 * prüft ob dier Angebote auf die aktuelle Woche beschränkt werden
	 */
	public function testInitSetsCorrectRequestHandler() {
		$aConfig = tx_mksearch_tests_Util::loadPageTS4BE();
		$aConfig['searchsolr.']['filter.']['default.']['force'] = 1;
		$oFilter = tx_rnbase::makeInstance('tx_mksearch_filter_SolrBase',$this->oParameters,tx_mksearch_tests_Util::loadConfig4BE($aConfig),'searchsolr.');

		$fields = array();
		$options = array();
		$oFilter->init($fields,$options);
		$this->assertEmpty($options['qt'],'Request Handler scheinbar doch gesetzt!');

		//set noHash as we don't need it in tests
		$aConfig = tx_mksearch_tests_Util::loadPageTS4BE();
		$aConfig['searchsolr.']['filter.']['default.']['force'] = 1;
		$aConfig['searchsolr.']['requestHandler'] = 'testHandler';
		$oFilter = tx_rnbase::makeInstance('tx_mksearch_filter_SolrBase',$this->oParameters,tx_mksearch_tests_Util::loadConfig4BE($aConfig),'searchsolr.');

		$fields = array();
		$options = array();
		$oFilter->init($fields,$options);
		$this->assertEquals('testHandler',$options['qt'],'Request Handler scheinbar doch nicht gesetzt!');
	}

	/**
	 * prüft ob dier Angebote auf die aktuelle Woche beschränkt werden
	 */
	public function testInitSetsCorrectTerm() {
		$aConfig = tx_mksearch_tests_Util::loadPageTS4BE();
		//wir müssen fields extra kopieren da es über TS Anweisungen im BE nicht geht
		$aConfig['searchsolr.']['filter.']['default.'] = $aConfig['lib.']['mksearch.']['defaultsolrfilter.'];
		//force noch setzen
		$aConfig['searchsolr.']['filter.']['default.']['force'] = 1;
		//Test term setzen
		$_GET['mksearch']['term'] = 'test term';
		$oFilter = tx_rnbase::makeInstance('tx_mksearch_filter_SolrBase',$this->oParameters,tx_mksearch_tests_Util::loadConfig4BE($aConfig),'searchsolr.');

		$fields = array('term' => 'contentType:* ###PARAM_MKSEARCH_TERM###');
		$options = array();
		$oFilter->init($fields,$options);
		//Der term bleibt so da die UserFunc die den term bildet im Test nicht aufgerufen wird da wir im BE sind
		$this->assertEquals('contentType:* AND text:("test" "term")',$fields['term'],'Request Handler scheinbar doch nicht gesetzt!');
	}

	/**
	 * prüft ob dier Angebote auf die aktuelle Woche beschränkt werden
	 */
	public function testInitSetsCorrectTermIfTermEmpty() {
		$aConfig = tx_mksearch_tests_Util::loadPageTS4BE();
		//wir müssen fields extra kopieren da es über TS Anweisungen im BE nicht geht
		$aConfig['searchsolr.']['filter.']['default.'] = $aConfig['lib.']['mksearch.']['defaultsolrfilter.'];
		//force noch setzen
		$aConfig['searchsolr.']['filter.']['default.']['force'] = 1;
		//Test term setzen
		$_GET['mksearch']['term'] = '';
		$oFilter = tx_rnbase::makeInstance('tx_mksearch_filter_SolrBase',$this->oParameters,tx_mksearch_tests_Util::loadConfig4BE($aConfig),'searchsolr.');

		$fields = array('term' => 'contentType:* ###PARAM_MKSEARCH_TERM###');
		$options = array();
		$oFilter->init($fields,$options);
		//Der term bleibt so da die UserFunc die den term bildet im Test nicht aufgerufen wird da wir im BE sind
		$this->assertEquals('contentType:* ',$fields['term'],'Request Handler scheinbar doch nicht gesetzt!');
	}

	/**
	 * prüft ob dier Angebote auf die aktuelle Woche beschränkt werden
	 */
	public function testInitSetsCorrectTermIfNoTermParamSet() {
		$aConfig = tx_mksearch_tests_Util::loadPageTS4BE();
		//wir müssen fields extra kopieren da es über TS Anweisungen im BE nicht geht
		$aConfig['searchsolr.']['filter.']['default.'] = $aConfig['lib.']['mksearch.']['defaultsolrfilter.'];
		//force noch setzen
		$aConfig['searchsolr.']['filter.']['default.']['force'] = 1;
		$oFilter = tx_rnbase::makeInstance('tx_mksearch_filter_SolrBase',$this->oParameters,tx_mksearch_tests_Util::loadConfig4BE($aConfig),'searchsolr.');

		$fields = array('term' => 'contentType:* ###PARAM_MKSEARCH_TERM###');
		$options = array();
		$oFilter->init($fields,$options);
		//Der term bleibt so da die UserFunc die den term bildet im Test nicht aufgerufen wird da wir im BE sind
		$this->assertEquals('contentType:* ',$fields['term'],'Request Handler scheinbar doch nicht gesetzt!');
	}

	/**
	 * prüft ob dier Angebote auf die aktuelle Woche beschränkt werden
	 */
	public function testInitSetsCorrectTermIfTermContainsSolrControlCharacters() {
		$aConfig = tx_mksearch_tests_Util::loadPageTS4BE();
		//wir müssen fields extra kopieren da es über TS Anweisungen im BE nicht geht
		$aConfig['searchsolr.']['filter.']['default.'] = $aConfig['lib.']['mksearch.']['defaultsolrfilter.'];
		//force noch setzen
		$aConfig['searchsolr.']['filter.']['default.']['force'] = 1;
		//Test term setzen
		$_GET['mksearch']['term'] = '*';
		$oFilter = tx_rnbase::makeInstance('tx_mksearch_filter_SolrBase',$this->oParameters,tx_mksearch_tests_Util::loadConfig4BE($aConfig),'searchsolr.');

		$fields = array('term' => 'contentType:* ###PARAM_MKSEARCH_TERM###');
		$options = array();
		$oFilter->init($fields,$options);
		//Der term bleibt so da die UserFunc die den term bildet im Test nicht aufgerufen wird da wir im BE sind
		$this->assertEquals('contentType:* ',$fields['term'],'Request Handler scheinbar doch nicht gesetzt!');
	}

	/**
	 * prüft ob dier Angebote auf die aktuelle Woche beschränkt werden
	 */
	public function testInitSetsCorrectFqIfSetAndNoFqFieldDefinedForWrapping() {
		$aConfig = tx_mksearch_tests_Util::loadPageTS4BE();
		//wir müssen fields extra kopieren da es über TS Anweisungen im BE nicht geht
		$aConfig['searchsolr.']['filter.']['default.'] = $aConfig['lib.']['mksearch.']['defaultsolrfilter.'];
		//force noch setzen
		$aConfig['searchsolr.']['filter.']['default.']['force'] = 1;
		// das feld für den fq muss noch erlaubt werden
		$aConfig['searchsolr.']['filter.']['default.']['allowedFqParams'] = 'facet_field';
		//fq noch setzen
		$this->oParameters->offsetSet('fq','facet_field:"facet value"');
		$oFilter = tx_rnbase::makeInstance('tx_mksearch_filter_SolrBase',$this->oParameters,tx_mksearch_tests_Util::loadConfig4BE($aConfig),'searchsolr.');

		$fields = array('term' => 'contentType:* ###PARAM_MKSEARCH_TERM###');
		$options = array();
		$oFilter->init($fields,$options);

		$this->assertEquals(array(
			0 => '(-fe_group_mi:[* TO *] AND uid:[* TO *]) OR fe_group_mi:0',
			1 => 'facet_field:"facet value"'
		),$options['fq'],'fq wuede falsch übernommen!');
	}

	/**
	 * prüft ob dier Angebote auf die aktuelle Woche beschränkt werden
	 */
	public function testInitSetsCorrectFqIfSetAndFqFieldDefinedForWrapping() {
		$aConfig = tx_mksearch_tests_Util::loadPageTS4BE();
		//wir müssen fields extra kopieren da es über TS Anweisungen im BE nicht geht
		$aConfig['searchsolr.']['filter.']['default.'] = $aConfig['lib.']['mksearch.']['defaultsolrfilter.'];
		//force noch setzen
		$aConfig['searchsolr.']['filter.']['default.']['force'] = 1;
		//fqField setzen
		$aConfig['searchsolr.']['filter.']['default.']['fqField'] = 'facet_dummy';
		//fq noch setzen
		$this->oParameters->offsetSet('fq','"facet value"');
		$oFilter = tx_rnbase::makeInstance('tx_mksearch_filter_SolrBase',$this->oParameters,tx_mksearch_tests_Util::loadConfig4BE($aConfig),'searchsolr.');

		$fields = array('term' => 'contentType:* ###PARAM_MKSEARCH_TERM###');
		$options = array();
		$oFilter->init($fields,$options);

		$this->assertEquals(array(
			0 => '(-fe_group_mi:[* TO *] AND uid:[* TO *]) OR fe_group_mi:0',
			1 => 'facet_dummy:"facet value"'
		),$options['fq'],'fq wuede falsch übernommen!');
	}

	public function testAllowedFqParams(){
		$aConfig = tx_mksearch_tests_Util::loadPageTS4BE();
		//wir müssen fields extra kopieren da es über TS Anweisungen im BE nicht geht
		$aConfig['searchsolr.']['filter.']['default.'] = $aConfig['lib.']['mksearch.']['defaultsolrfilter.'];
		//force noch setzen
		$aConfig['searchsolr.']['filter.']['default.']['force'] = 1;
		//force noch setzen, das gegenteil wird bereits in testInitSetsCorrectFqIfSetAndNoFqFieldDefinedForWrapping geprüft
		$aConfig['searchsolr.']['filter.']['default.']['allowedFqParams'] = 'allowedfield';

		//fq noch setzen
		$this->oParameters->offsetSet('fq','field:"facet value"');

		$oFilter = tx_rnbase::makeInstance(
			'tx_mksearch_filter_SolrBase',
			$this->oParameters,
			tx_mksearch_tests_Util::loadConfig4BE($aConfig),
			'searchsolr.'
		);

		$fields = array('term' => 'contentType:* ###PARAM_MKSEARCH_TERM###');
		$options = array();
		$oFilter->init($fields, $options);

		$this->assertEquals('(-fe_group_mi:[* TO *] AND uid:[* TO *]) OR fe_group_mi:0',$options['fq'],'fq wuede gesetzt!');
	}

	public function testSettingOfFeGroupsToFilterQuery(){
		$tsFeBackup = $GLOBALS['TSFE']->fe_user->groupData['uid'];
		$GLOBALS['TSFE']->fe_user->groupData['uid'] = array(1,2);

		$aConfig = tx_mksearch_tests_Util::loadPageTS4BE();
		//wir müssen fields extra kopieren da es über TS Anweisungen im BE nicht geht
		$aConfig['searchsolr.']['filter.']['default.'] = $aConfig['lib.']['mksearch.']['defaultsolrfilter.'];
		//force noch setzen
		$aConfig['searchsolr.']['filter.']['default.']['force'] = 1;

		$oFilter = tx_rnbase::makeInstance(
			'tx_mksearch_filter_SolrBase',
			$this->oParameters,
			tx_mksearch_tests_Util::loadConfig4BE($aConfig),
			'searchsolr.'
		);

		$fields = array('term' => 'contentType:* ###PARAM_MKSEARCH_TERM###');
		$options = array();
		$oFilter->init($fields, $options);

		$this->assertEquals('(-fe_group_mi:[* TO *] AND uid:[* TO *]) OR fe_group_mi:0 OR fe_group_mi:1 OR fe_group_mi:2',$options['fq'],'fq wuede gesetzt!');

		$GLOBALS['TSFE']->fe_user->groupData['uid'] = $tsFeBackup;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkmarketplace/tests/filter/class.tx_mkmarketplace_tests_filter_SearchAds_testcase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mkmarketplace/tests/filter/class.tx_mkmarketplace_tests_filter_SearchAds_testcase.php']);
}

?>