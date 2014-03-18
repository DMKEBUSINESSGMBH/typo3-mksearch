<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2014 DMK-EBUSINESS GmbH <rene.nitzsche@dmk-ebusiness.de>
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

class tx_mksearch_tests_service_engine_lucene_DataTypeMapper_testcase extends tx_phpunit_testcase {


	/* @var $mapper tx_mksearch_service_engine_lucene_DataTypeMapper */
	private $mapper;

	/**
	 *
	 */
	protected function setUp() {
		$this->mapper = tx_rnbase::makeInstance('tx_mksearch_service_engine_lucene_DataTypeMapper');

	}

	public function testFieldWithSpecialConfig() {

		// Zuerst den Default testen
		$this->assertEquals('keyword', $this->mapper->getDataType('tstamp'), 'Wrong data type found');

		// Und jetzt per Config überschreiben
		$cfg = array();
		$cfg['fields.']['tstamp.']['type'] = 'unindexed';
		$mapper = tx_rnbase::makeInstance('tx_mksearch_service_engine_lucene_DataTypeMapper', $cfg);
		$this->assertEquals('unindexed', $mapper->getDataType('tstamp'), 'Wrong data type found');
		// Die anderen sollten weiter normal funktionieren
		$this->assertEquals('keyword', $mapper->getDataType('uid'), 'Wrong data type found');

	}

	/**
	 * @dataProvider getSolrLikeFieldNames
	 */
	public function testAutoTypesFromSolr($fieldName, $expectedType) {
		// 'text', 'keyword', 'unindexed', 'unstored', 'binary'
		$this->assertEquals($expectedType, $this->mapper->getDataType($fieldName), 'Wrong data type found for fieldname '.$fieldName);
	}

	public function getSolrLikeFieldNames() {
		return array(
				array('test_s', 'text'),
				array('test_i', 'keyword'),
				array('pid', 'keyword'),
				array('someotherfield', 'text'),
				array('someotherfield_mi', 'text'),
		);
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/service/engine/lucene/class.tx_mksearch_tests_service_engine_lucene_DataTypeMapper_testcase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/service/engine/lucene/class.tx_mksearch_tests_service_engine_lucene_DataTypeMapper_testcase.php']);
}
