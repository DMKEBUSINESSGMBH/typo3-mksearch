<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_tests
 *  @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2011 Hannes Bochmann <dev@dmk-ebusiness.de>
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

require_once tx_rnbase_util_Extensions::extPath('mksearch', 'lib/Apache/Solr/Document.php');
tx_rnbase::load('tx_mksearch_tests_Testcase');
tx_rnbase::load('tx_mksearch_marker_SearchResultSimple');

/**
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_tests
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_marker_SearchResultSimple_testcase
	extends tx_mksearch_tests_Testcase {

	/**
	 *
	 * Enter description here ...
	 * @var tx_mksearch_marker_Facet
	 */
	protected $oMarker;

	/**
	 * setUp() = init DB etc.
	 */
	protected function setUp(){
		parent::setUp();
		tx_rnbase_util_Misc::prepareTSFE();

		$this->oParameters = tx_rnbase::makeInstance('tx_rnbase_parameters');
		$this->oMarker = tx_rnbase::makeInstance('tx_mksearch_marker_SearchResultSimple');
	}

	/**
	 * prüfen ob die richtigen fields und options zurück gegeben werden
	 */
	public function testParseTemplateDoesntReturnUnparsedMarkersForRequiredFields() {
		//set noHash as we don't need it in tests
		$aConfig = tx_mksearch_tests_Util::loadPageTS4BE();
		$aConfig['searchsolr.']['hit.']['extrainfo.']['default.']['hit.']['initFields.'] = array('secondField');
		$this->oConfig = tx_mksearch_tests_Util::loadConfig4BE($aConfig);
		$this->oFormatter = $this->oConfig->getFormatter();

		//now test
		$doc = new Apache_Solr_Document();
		$doc->firstField = 'firstValue';

		$oItem = tx_rnbase::makeInstance('tx_mksearch_model_SolrHit',$doc);

		$sTemplate = '###ITEM_FIRSTFIELD### ###ITEM_SECONDFIELD### ###ITEM_THIRDFIELD###';
		$sParsedTemplate = $this->oMarker->parseTemplate($sTemplate, $oItem, $this->oFormatter, 'searchsolr.hit.extrainfo.default.hit.', 'ITEM');

		//erster Marker sollte mit wert ersetzt werden, 2. leer und 3. gar nicht
		self::assertEquals('firstValue  ###ITEM_THIRDFIELD###',$sParsedTemplate,'Die Marker wurden nicht korrekt ersetzt!');
	}
}