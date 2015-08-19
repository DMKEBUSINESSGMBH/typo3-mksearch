<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2015 DMK E-Business GmbH
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

tx_rnbase::load('tx_mksearch_tests_Testcase');
tx_rnbase::load('tx_mksearch_util_Filter');
tx_rnbase::load('tx_rnbase_util_TS');



/**
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_tests
 * @author René Nitzsche <rene.nitzsche@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_util_Filter_testcase extends tx_mksearch_tests_Testcase {

	/** @var $filterUtil tx_mksearch_util_Filter */
	private $filterUtil;

	protected function setUp() {
		$this->filterUtil = tx_rnbase::makeInstance('tx_mksearch_util_Filter');
		parent::setUp();
	}

	public function test_parseCustomFilters() {
		$typoScript = '
searchsolr.filter.default.formfields {
  sort.default = score
  sort.activeMark = selected="selected"
  sort {
    values.10.value = score desc
    values.10.caption = Score
    values.20.value = tstamp asc
    values.20.caption = Aktualität aufsteigend
    values.30.value = tstamp desc
    values.30.caption = Aktualität absteigend
  }
}';
		$params = tx_rnbase::makeInstance('tx_rnbase_parameters');
		$params->offsetSet('sort', 'tstamp asc');
		$confArr = tx_rnbase_util_TS::parseTsConfig($typoScript);
		$conf = $this->createConfigurations($confArr, 'mksearch', 'mksearch', $params);
		$template = '<html>
	<!-- ###SEARCH_FILTER_SORTS### -->
			<label class="sort">
				Sortierung
				<select name="###SEARCH_FILTER_SORT_FORM_NAME###">
					<!-- ###SEARCH_FILTER_SORT### -->
					<option value="###SEARCH_FILTER_SORT_UID###" ###SEARCH_FILTER_SORT_SELECTED###>###SEARCH_FILTER_SORT_CAPTION###</option>
					<!-- ###SEARCH_FILTER_SORT### -->
				</select>
			</label>
		<!-- ###SEARCH_FILTER_SORTS### -->
</html>';

		$result = $this->filterUtil->parseCustomFilters($template, $conf, 'searchsolr.filter.default.', 'SEARCH_FILTER');
		$this->assertNotSame($template, $result);

		$this->assertEquals(3, substr_count($result, '<option'), 'Anzahl Optionen falsch');
		$this->assertEquals(1, substr_count($result, 'value="score desc"'), 'Score fehlt');
		$this->assertEquals(1, substr_count($result, 'value="tstamp asc"'), 'Zeit asc fehlt');
		$this->assertEquals(1, substr_count($result, 'value="tstamp desc"'), 'Zeit desc fehlt');
		$this->assertEquals(1, substr_count($result, 'selected="selected'), 'Aktives Feld nicht markiert');
		$this->assertEquals(1, substr_count($result, '<option value="tstamp asc" selected="selected">'), 'Mehr als 1 Feld als aktiv markiert.');

// <html> <label class="sort"> Sortierung <select name="mksearch[sort]"> <option value="score desc" >Score</option>
// <option value="tstamp asc" selected="selected">Aktualität aufsteigend</option> <option value="tstamp desc" >Aktualität absteigend</option>
// </select> </label> </html>
	}

	public function t1st_parseCustomFiltersWithNoConfiguration() {
		$typoScript = '
searchsolr.filter.default.formfields {
}';
		$confArr = tx_rnbase_util_TS::parseTsConfig($typoScript);
		$conf = $this->createConfigurations($confArr, 'mksearch', 'mksearch');
		$template = '<html>
###FILTER_SORT_TSTAMP_ORDER### - ###FILTER_SORT_TSTAMP_LINKURL###
</html>';

		$result = $this->filterUtil->parseCustomFilters($template, $conf, 'searchsolr.filter.default.', 'FILTER');
		$this->assertSame($template, $result);
	}

	/**
	 * @group integration
	 */
	public function test_getParseSortFields() {
		$typoScript = '
searchsolr.filter.default.sort {
  fields = score,tstamp
  link{
    pid = 10
    useKeepVars = 1
  }
}';
		$cObjMock = $this->getMockBuilder('tslib_cObj')->disableOriginalConstructor()->setMethods(array('typolink'))->getMock();
		// Den Token bekommen wir nicht rein...
		$cObjMock->expects($this->any())->method('typolink')->will(self::returnValue('<a href=""/>'));
		$confArr = tx_rnbase_util_TS::parseTsConfig($typoScript);
		$conf = $this->createConfigurations($confArr, 'mksearch', 'mksearch', null, $cObjMock);

		$template = '<html>
###SORT_TSTAMP_ORDER### - ###SORT_TSTAMP_LINKURL###
</html>';
		$markerArr = $subpartArray = $wrappedSubpartArray = array();

		$this->filterUtil->parseSortFields($template, $markerArr, $subpartArray, $wrappedSubpartArray,
				$conf->getFormatter(), 'searchsolr.filter.default.', 'FILTER');
		$this->assertEquals(3, count($markerArr));
		$this->assertEquals('<a href=""/>', $markerArr['###SORT_TSTAMP_LINKURL###']);

		$this->assertEquals(0, count($subpartArray));
		$this->assertTrue(array_key_exists('###SORT_TSTAMP_LINK###', $wrappedSubpartArray));
	}


}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/util/class.tx_mksearch_tests_util_Filter_testcase.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/util/class.tx_mksearch_tests_util_Filter_testcase.php']);
}

