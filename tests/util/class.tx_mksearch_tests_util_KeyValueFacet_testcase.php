<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2014 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
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

require_once t3lib_extMgm::extPath('rn_base', 'class.tx_rnbase.php');
tx_rnbase::load('tx_mksearch_tests_Testcase');
tx_rnbase::load('tx_mksearch_util_KeyValueFacet');

/**
 *
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_tests
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_util_KeyValueFacet_testcase
	extends tx_mksearch_tests_Testcase {

	/**
	 *
	 * @param string $delimiter
	 * @return tx_mksearch_util_KeyValueFacet
	 */
	protected function getKeyValueFacetInstance($delimiter = NULL) {
		return tx_mksearch_util_KeyValueFacet::getInstance($delimiter);
	}

	/**
	 *
	 * @dataProvider providerBuildFacetValue
	 * @param string $key
	 * @param string $value
	 * @param string $expected
	 */
	public function testBuildFacetValue($key, $value, $sorting, $expected) {
		$builder = $this->getKeyValueFacetInstance();
		$actual = $builder->buildFacetValue($key, $value, $sorting);
		$this->assertEquals($expected, $actual);
	}
	/**
	 * Dataprovider for testBuildFacetValue
	 */
	public function providerBuildFacetValue() {
		return array(
			__LINE__ => array(
				'key' => 'key', 'value' => 'value', 'sorting' => NULL,
				'expected' => 'key<[DFS]>value',
			),
			__LINE__ => array(
				'key' => '50', 'value' => 'Test', 'sorting' => '2',
				'expected' => '50<[DFS]>Test<[DFS]>2',
			),
		);
	}
	/**
	 *
	 * @dataProvider providerCheckValue
	 * @param string $value
	 * @param string $expected
	 */
	public function testCheckValue($value, $expected) {
		$builder = $this->getKeyValueFacetInstance();
		$actual = $builder->checkValue($value);
		$this->assertEquals($expected, $actual);
	}
	/**
	 * Dataprovider for testCheckValue
	 */
	public function providerCheckValue() {
		return array(
			__LINE__ => array(
				'value' => 'key<[DFS]>value',
				'expected' => TRUE,
			),
			__LINE__ => array(
				'value' => 'Test',
				'expected' => FALSE,
			),
		);
	}
	/**
	 *
	 * @dataProvider providerExplodeFacetValue
	 * @param string $builded
	 * @param array $expected
	 */
	public function testExplodeFacetValue($builded, $expected) {
		$builder = $this->getKeyValueFacetInstance();
		$actual = $builder->explodeFacetValue($builded);
		$this->assertEquals($expected, $actual);
	}
	/**
	 * Dataprovider for testExplodeFacetValue
	 */
	public function providerExplodeFacetValue() {
		return array(
			__LINE__ => array(
				'builded' => 'key<[DFS]>value',
				'expected' => array('key' => 'key', 'value' => 'value', 'sorting' => NULL),
			),
			__LINE__ => array(
				'builded' => '50<[DFS]>Test<[DFS]>2',
				'expected' => array('key' => '50', 'value' => 'Test', 'sorting' => '2'),
			),
		);
	}
	/**
	 *
	 * @dataProvider providerExtractFacetValue
	 * @param string $builded
	 * @param array $expected
	 */
	public function testExtractFacetValue($builded, $expected) {
		$builder = $this->getKeyValueFacetInstance();
		$actual = $builder->extractFacetValue($builded);
		$this->assertEquals($expected, $actual);
	}
	/**
	 * Dataprovider for testExtractFacetValue
	 */
	public function providerExtractFacetValue() {
		return array(
			__LINE__ => array(
				'builded' => 'key<[DFS]>value',
				'expected' => 'value',
			),
			__LINE__ => array(
				'builded' => '50<[DFS]>Test',
				'expected' => 'Test',
			),
		);
	}
	/**
	 *
	 */
	public function testExplodeFacetValues() {
		$builder = $this->getKeyValueFacetInstance();
		$data = array(
			'key<[DFS]>value',
			'50<[DFS]>Test<[DFS]>2',
		);
		$actual = $builder->explodeFacetValues($data);
		$expected = array(
			array('key' => 'key', 'value' => 'value', 'sorting' => NULL),
			array('key' => '50', 'value' => 'Test', 'sorting' => '2'),
		);
		$this->assertEquals($expected, $actual);
	}
	/**
	 *
	 */
	public function testExtractFacetValues() {
		$builder = $this->getKeyValueFacetInstance();
		$data = array(
			'key<[DFS]>value',
			'50<[DFS]>Test',
		);
		$actual = $builder->extractFacetValues($data);
		$expected = array(
			'key' => 'value',
			'50' => 'Test',
		);
		$this->assertEquals($expected, $actual);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/util/class.tx_mksearch_tests_util_KeyValueFacet_testcase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/util/class.tx_mksearch_tests_util_KeyValueFacet_testcase.php']);
}
