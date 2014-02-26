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
tx_rnbase::load('tx_mksearch_util_Misc');


class tx_mksearch_tests_util_Misc_testcase extends tx_phpunit_testcase {

	/**
	 * @dataProvider providerHtml2Plain
	 */
	public function testHtml2Plain($before, $options, $after) {
		$res = tx_mksearch_util_Misc::html2plain($before, $options);
		$this->assertEquals($res, $after);
	}
	public function providerHtml2Plain() {
		$return = array();
		foreach(array(
//				array($before, $after),
				__LINE__ => array(
						'Kapstadt Messetrailer <object width="5" height="5"><param name="movie" value="hier muss eigentlich die url rein ^^" /><param name="allowFullScreen" value="true" /><param name="allowscriptaccess" value="always" /><embed src="hier gehts zum player" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="5" height="5"></embed></object> Eternit Schönes Beschützen',
						array(), // emty option
						'Kapstadt Messetrailer   Eternit Schönes Beschützen',
					),
				__LINE__ => array(
						'Hallo Welt',
						array(), // emty option
						'Hallo Welt',
					),
				__LINE__ => array(
						'Hallo <!-- Kommentar --> Welt',
						array(), // emty option
						'Hallo   Welt',
					),
				__LINE__ => array(
						'<br>Hallo</br> <i>Welt</i>',
						array(), // emty option
						'Hallo   Welt',
					),
				__LINE__ => array(
						'Umlaute encoded F&ouml;rderm&ouml;glichkeiten',
						array(), // emty option
						'Umlaute encoded Fördermöglichkeiten',
					),
				__LINE__ => array(
						'Zeile1' . PHP_EOL . 'Zeile2',
						array(), // emty option
						'Zeile1 Zeile2',
					),
				__LINE__ => array(
						'Zeile1' . PHP_EOL . 'Zeile2',
						array('lineendings' => true),
						'Zeile1' . PHP_EOL . 'Zeile2',
					),
			) as $key => $row) {
			$key = 'Line:'.$key;
			$return[$key] = $row;
		}
		return $return;
	}
	public function test_isIndexable_NoConfig() {
		$record = array('uid'=>123, 'pid'=>'1');
		$options = array();
		$this->assertEquals(true, tx_mksearch_util_Misc::isOnValidPage($record, $options));
	}

	public function test_isIndexable_IncludeWrongSinglePid() {
		$record = array('uid'=>123, 'pid'=>'1');
		$options = array();
		$options['include.']['pages.']['0'] = 2;
		$this->assertEquals(false, tx_mksearch_util_Misc::isOnValidPage($record, $options));

		$options = array();
		$options['include.']['pages'] = '2,10,145';
		$this->assertEquals(false, tx_mksearch_util_Misc::isOnValidPage($record, $options));
	}

	public function test_isIndexable_IncludeRightSinglePid() {
		$record = array('uid'=>123, 'pid'=>'1');
		$options = array();
		$options['include.']['pages.'] = array(2,10,1,1145);
		$this->assertEquals(true, tx_mksearch_util_Misc::isOnValidPage($record, $options));

		$options = array();
		$options['include.']['pages'] = '2,10,1,145';
		$this->assertEquals(true, tx_mksearch_util_Misc::isOnValidPage($record, $options));
	}

	public function test_isIndexable_ExcludeWrongSinglePid() {
		$record = array('uid'=>123, 'pid'=>'1');
		$options = array();
		$options['exclude.']['pages.']['0'] = 2;
		$this->assertEquals(true, tx_mksearch_util_Misc::isOnValidPage($record, $options));

		$options = array();
		$options['exclude.']['pages'] = '2,10,145';
		$this->assertEquals(true, tx_mksearch_util_Misc::isOnValidPage($record, $options));
	}

	public function test_isIndexable_ExcludeRightSinglePid() {
		$record = array('uid'=>123, 'pid'=>'11');
		$options = array();
		$options['exclude.']['pages.'] = array(2,10,11,1145);
		$this->assertEquals(false, tx_mksearch_util_Misc::isOnValidPage($record, $options));

		$options = array();
		$options['exclude.']['pages'] = '2,10,11,145';
		$this->assertEquals(false, tx_mksearch_util_Misc::isOnValidPage($record, $options));
	}

	public function test_isIndexable_CheckMismachingConfig() {
		$record = array('uid'=>123, 'pid'=>'11');
//		$options = array();
//		$options['include.']['pages'] = '2,10,145';
//		$options['exclude.']['pages.'] = array(2,10,1145);
//		$this->assertEquals(false, tx_mksearch_util_Misc::isOnValidPage($record, $options), 'Element indexed but not in include');
//
//		$options = array();
//		$options['include.']['pages'] = '';
//		$options['exclude.']['pages'] = '2,10,11,145';
//		$this->assertEquals(false, tx_mksearch_util_Misc::isOnValidPage($record, $options));

		$options = array();
		$options['include.']['pages'] = '';
		$options['exclude.']['pages'] = '2,10,145';
		$this->assertEquals(true, tx_mksearch_util_Misc::isOnValidPage($record, $options));
	}

	/**
	 * @dataProvider termProvider
	 */
	public function testSanitizeTerm($sTerm, $sExpected) {
		$this->assertEquals($sExpected, tx_mksearch_util_Misc::sanitizeTerm($sTerm),'Der Term wurde nicht korrekt bereinigt!');
	}
	public function termProvider() {
		return array(
			array('test', 'test'),
			array('\'test\'+-&|!(){}\[]^"~+*?<>:', 'test'),
		);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/util/class.tx_mksearch_tests_util_Misc_testcase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/util/class.tx_mksearch_tests_util_Misc_testcase.php']);
}