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
	
	
	/**
	 * @group integration
	 */
	public function test_getParseSortFieldsLegacy() {
		$typoScript = '
searchsolr.filter.default.sort {
  fields = score,tstamp
  link{
    pid = 10
    useKeepVars = 1
  }
}';
		$confArr = tx_rnbase_util_TS::parseTsConfig($typoScript);
		$conf = $this->createConfigurations($confArr, 'mksearch', 'mksearch');
		$cObjMock = $this->getMockBuilder('tslib_cObj')->disableOriginalConstructor()->setMethods(array('typolink'))->getMock();
		// Den Token bekommen wir nicht rein...
		$cObjMock->expects($this->any())->method('typolink')->willReturn('<a href=""/>');
		
		$conf->cObj = $cObjMock; 

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

