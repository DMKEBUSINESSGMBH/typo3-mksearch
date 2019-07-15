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

/**
 * @author René Nitzsche <rene.nitzsche@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_util_FilterTest extends tx_mksearch_tests_Testcase
{
    /**
     * @var tx_mksearch_util_Filter
     */
    private $filterUtil;

    protected function setUp()
    {
        $this->filterUtil = tx_rnbase::makeInstance('tx_mksearch_util_Filter');
        parent::setUp();
    }

    public function test_parseCustomFilters()
    {
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

    public function test_parseCustomFiltersWithNoConfiguration()
    {
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
    public function test_getPageLimit()
    {
        $typoScript = '
searchsolr.filter.default.formfields.pagelimit {
  activeMark = selected="selected"
  values.10.value = 10
  values.10.caption = 10 hits
  values.20.value = 20
  values.20.caption = 20 hits
  values.30.value = 50
  values.30.caption = 50 hits
}';
        $params = tx_rnbase::makeInstance('tx_rnbase_parameters');
        $params->offsetSet('pagelimit', '50');
        $confArr = tx_rnbase_util_TS::parseTsConfig($typoScript);
        $conf = $this->createConfigurations($confArr, 'mksearch', 'mksearch', $params);

        $result = $this->filterUtil->getPageLimit($params, $conf, 'searchsolr.filter.default.', 5);

        $this->assertEquals(50, $result, 'Anzahl Treffer nicht aus Parametern übernommen');

        $params->offsetSet('pagelimit', '500');
        $result = $this->filterUtil->getPageLimit($params, $conf, 'searchsolr.filter.default.', 5);
        $this->assertEquals(5, $result, 'Anzahl nicht auf default gesetzt');

        $params->offsetUnset('pagelimit');
        $result = $this->filterUtil->getPageLimit($params, $conf, 'searchsolr.filter.default.', 5);
        $this->assertEquals(5, $result, 'Anzahl nicht auf default gesetzt');

        // Unlimited ist im TS nicht vorhanden, also ist es über das FE nicht setzbar
        $params->offsetSet('pagelimit', '-1');
        $result = $this->filterUtil->getPageLimit($params, $conf, 'searchsolr.filter.default.', 5);
        $this->assertEquals(5, $result, 'Anzahl nicht auf default gesetzt');

        $params->offsetSet('pagelimit', '-2');
        $result = $this->filterUtil->getPageLimit($params, $conf, 'searchsolr.filter.default.', 5);
        $this->assertEquals(5, $result, 'Anzahl nicht auf default gesetzt');
    }

    /**
     * @group integration
     */
    public function test_getPageLimitWithSpecialValues()
    {
        $typoScript = '
searchsolr.filter.default.formfields.pagelimit {
  default = 10
  activeMark = selected="selected"
  values.10.value = 10
  values.10.caption = 10 hits
  values.20.value = 20
  values.20.caption = 20 hits
  values.30.value = 50
  values.30.caption = 50 hits
  values.50.value = -1
  values.50.caption = Unlimited hits
  values.60.value = -2
  values.60.caption = No results
}';
        $params = tx_rnbase::makeInstance('tx_rnbase_parameters');
        $params->offsetSet('pagelimit', '-1');
        $confArr = tx_rnbase_util_TS::parseTsConfig($typoScript);
        $conf = $this->createConfigurations($confArr, 'mksearch', 'mksearch', $params);

        $result = $this->filterUtil->getPageLimit($params, $conf, 'searchsolr.filter.default.', 5);
        $this->assertEquals(999999, $result, 'Anzahl Treffer nicht auf unendlich gesetzt');

        $params->offsetSet('pagelimit', '-2');
        $result = $this->filterUtil->getPageLimit($params, $conf, 'searchsolr.filter.default.', 5);
        $this->assertEquals(0, $result, 'Anzahl Treffer nicht auf 0 gesetzt');
    }

    /**
     * Hier muss immer der Fallback-Wert aus dem Flexform genutzt werden.
     *
     * @group integration
     */
    public function test_getPageLimitWithoutTsConfiguration()
    {
        $typoScript = '
searchsolr.filter.default.formfields.pagelimit {
}';
        $params = tx_rnbase::makeInstance('tx_rnbase_parameters');
        $params->offsetSet('pagelimit', '50');
        $confArr = tx_rnbase_util_TS::parseTsConfig($typoScript);
        $conf = $this->createConfigurations($confArr, 'mksearch', 'mksearch', $params);

        $result = $this->filterUtil->getPageLimit($params, $conf, 'searchsolr.filter.default.', 5);

        $this->assertEquals(5, $result, 'Anzahl Treffer auf fallback gesetzt');

        $params->offsetSet('pagelimit', '500');
        $result = $this->filterUtil->getPageLimit($params, $conf, 'searchsolr.filter.default.', 5);
        $this->assertEquals(5, $result, 'Anzahl Treffer auf fallback gesetzt');

        $params->offsetUnset('pagelimit');
        $result = $this->filterUtil->getPageLimit($params, $conf, 'searchsolr.filter.default.', 5);
        $this->assertEquals(5, $result, 'Anzahl Treffer auf fallback gesetzt');

        // Das FE darf hier nie greifen
        $params->offsetSet('pagelimit', '-1');
        $result = $this->filterUtil->getPageLimit($params, $conf, 'searchsolr.filter.default.', 5);
        $this->assertEquals(5, $result, 'Anzahl nicht auf default gesetzt');

        $params->offsetSet('pagelimit', '-2');
        $result = $this->filterUtil->getPageLimit($params, $conf, 'searchsolr.filter.default.', 5);
        $this->assertEquals(5, $result, 'Anzahl nicht auf default gesetzt');

        // Sonderfälle prüfen
        $result = $this->filterUtil->getPageLimit($params, $conf, 'searchsolr.filter.default.', -1);
        $this->assertEquals(999999, $result, 'Anzahl nicht auf unendlich gesetzt');
        $result = $this->filterUtil->getPageLimit($params, $conf, 'searchsolr.filter.default.', -2);
        $this->assertEquals(0, $result, 'Anzahl nicht auf 0 gesetzt');
    }

    /**
     * @group integration
     */
    public function test_getParseSortFields()
    {
        $typoScript = '
searchsolr.filter.default.sort {
  fields = score,tstamp
  link{
    pid = 10
    useKeepVars = 1
  }
}';
        $cObjMock = $this->getMockBuilder(tx_rnbase_util_Typo3Classes::getContentObjectRendererClass())
            ->disableOriginalConstructor()->setMethods(array('typolink'))->getMock();
        // Den Token bekommen wir nicht rein...
        $cObjMock->expects($this->any())->method('typolink')->will(self::returnValue('<a href=""/>'));
        $confArr = tx_rnbase_util_TS::parseTsConfig($typoScript);
        $conf = $this->createConfigurations($confArr, 'mksearch', 'mksearch', null, $cObjMock);

        $template = '<html>
###SORT_TSTAMP_ORDER### - ###SORT_TSTAMP_LINKURL###
</html>';
        $markerArr = $subpartArray = $wrappedSubpartArray = array();

        $this->filterUtil->parseSortFields(
            $template,
            $markerArr,
            $subpartArray,
            $wrappedSubpartArray,
            $conf->getFormatter(),
            'searchsolr.filter.default.',
            'FILTER'
        );
        $this->assertEquals(3, count($markerArr));
        $this->assertEquals('<a href=""/>', $markerArr['###SORT_TSTAMP_LINKURL###']);

        $this->assertEquals(0, count($subpartArray));
        $this->assertTrue(array_key_exists('###SORT_TSTAMP_LINK###', $wrappedSubpartArray));
    }

    /**
     * Tests tx_mksearch_util_Filter::parseFqFieldAndValue.
     *
     *
     * @group unit
     * @test
     * @dataProvider getParseFqFieldAndValueData
     */
    public function testParseFqFieldAndValue(
        $fq,
        $expected,
        array $allowedFqParams = array()
    ) {
        if (empty($allowedFqParams)) {
            $allowedFqParams[] = 'contentType';
        }
        $util = tx_rnbase::makeInstance('tx_mksearch_util_Filter');
        $actual = $util->parseFqFieldAndValue($fq, $allowedFqParams);
        self::assertSame($expected, $actual);
    }

    /**
     * The test data for testParseFqFieldAndValue testcase.
     *
     * @return array
     */
    public function getParseFqFieldAndValueData()
    {
        return array(
            __LINE__ => array(
                'fq' => 'contentType:5',
                'expected' => 'contentType:"5"',
            ),
            __LINE__ => array(
                'fq' => 'contentType:tt_content',
                'expected' => 'contentType:"tt_content"',
            ),
            __LINE__ => array(
                'fq' => 'category_s:Foo Bar',
                'expected' => 'category_s:"Foo Bar"',
                'allowedFqParams' => array('category_s'),
            ),
            __LINE__ => array(
                'fq' => 'field1_uid_i:57',
                'expected' => 'field1_uid_i:"57"',
                'allowedFqParams' => array('field1_uid_i'),
            ),
            __LINE__ => array(
                'fq' => 'upperCaseField:7',
                'expected' => 'upperCaseField:"7"',
                'allowedFqParams' => array('upperCaseField'),
            ),
            __LINE__ => array(
                'fq' => 'unknowd_ms:5',
                'expected' => '',
            ),
            __LINE__ => array(
                'fq' => 'contentType:frühstück',
                'expected' => 'contentType:"frühstück"',
        ),
            __LINE__ => array(
                'fq' => 'frühstück',
                'expected' => '',
            ),
        );
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/util/class.tx_mksearch_tests_util_FilterTest.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/util/class.tx_mksearch_tests_util_FilterTest.php'];
}
