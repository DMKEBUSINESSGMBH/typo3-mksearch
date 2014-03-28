<?php
/**
 *	@package TYPO3
 *  @subpackage tx_mksearch
 *  @author Hannes Bochmann <hannes.bochmann@das-medienkombinat.de>
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
require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_mksearch_filter_LuceneBase');
tx_rnbase::load('tx_rnbase_tests_BaseTestCase');

/**
 * @package TYPO3
 * @subpackage tx_mksearch
 * @author Hannes Bochmann <hannes.bochmann@das-medienkombinat.de>
 */
class tx_mksearch_tests_filter_LuceneBase_testcase extends tx_rnbase_tests_BaseTestCase {

	/**
	 * @var string
	 */
	private $confId = 'searchlucene.';

	/**
	 * @group uinit
	 */
	public function testPrepareFormFieldsSetsDefaultFieldsIfNotInParameters() {
		$parameters = tx_rnbase::makeInstance('tx_rnbase_parameters');
		$formData = array();
		$reflectionObject = new ReflectionObject($this->getFilter());
		$reflectionMethod = $reflectionObject->getMethod('prepareFormFields');
		$reflectionMethod->setAccessible(TRUE);
		$reflectionMethod->invokeArgs(
			$this->getFilter(),
			array(&$formData, $parameters)
		);

		$this->assertArrayHasKey('zip', $formData, 'zip nicht vorhanden in formdata');
		$this->assertArrayHasKey('city', $formData, 'city nicht vorhanden in formdata');
		$this->assertArrayHasKey('company', $formData, 'company nicht vorhanden in formdata');
		$this->assertEquals('', $formData['zip'], 'zip nicht leer in formdata');
		$this->assertEquals('', $formData['city'], 'city nicht leer in formdata');
		$this->assertEquals('', $formData['company'], 'company nicht leer in formdata');
	}

	/**
	 * @group uinit
	 */
	public function testPrepareFormFieldsSetsDefaultFieldsNotIfAlreadyInFormData() {
		$parameters = tx_rnbase::makeInstance('tx_rnbase_parameters');
		$formData = array('zip' => 1, 'city' => 2, 'company' => 3);
		$reflectionObject = new ReflectionObject($this->getFilter());
		$reflectionMethod = $reflectionObject->getMethod('prepareFormFields');
		$reflectionMethod->setAccessible(TRUE);
		$reflectionMethod->invokeArgs(
			$this->getFilter(),
			array(&$formData, $parameters)
		);

		$this->assertEquals(1, $formData['zip'], 'zip leer in formdata');
		$this->assertEquals(2, $formData['city'], 'city leer in formdata');
		$this->assertEquals(3, $formData['company'], 'company leer in formdata');
	}

	/**
	 * @group uinit
	 */
	public function testInitAddsNoContentTypeIfNotDefinedInConfigurations() {
		$filter = $this->getFilter();

		$fields = array();
		$options = array();
		$filterReturn = $filter->init($fields,$options);
		$this->assertTrue($filterReturn, 'filter gibt nicht true zurück.');

		$expectedFields = $this->getDefaultFields();
		$this->assertEquals($expectedFields, $fields, 'fields nicht leer');
	}

	/**
	 * @group uinit
	 */
	public function testInitAddsContentTypeIfDefinedInConfigurations() {
		$configArray = array($this->confId => array('contentType' => 'test'));
		$filter = $this->getFilter($configArray);

		$fields = array();
		$options = array();
		$filterReturn = $filter->init($fields,$options);
		$this->assertTrue($filterReturn, 'filter gibt nicht true zurück.');

		$expectedFields = $this->getDefaultFields();
		$expectedFields['contentType']	=	array(array('term' => 'test', 'sign' => true));
		$this->assertEquals($expectedFields, $fields, 'fields nicht leer');
	}

	/**
	 * @group uinit
	 */
	public function testInitAddsNoContentTypeIfDefinedInConfigurationsButModeIsAdvanced() {
		$configArray = array($this->confId => array('contentType' => 'test'));
		$filter = $this->getFilter($configArray);

		$fields = array();
		$options = array();
		$filterReturn = $filter->init($fields,$options);
		$this->assertTrue($filterReturn, 'filter gibt nicht true zurück.');

		$expectedFields = $this->getDefaultFields();
		$expectedFields['contentType']	=	array(array('term' => 'test', 'sign' => true));
		$this->assertEquals($expectedFields, $fields, 'fields nicht leer');
	}

	/**
	 * @param array $configArray
	 *
	 * @return tx_mksearch_filter_LuceneBase
	 */
	private function getFilter(array $configArray = array()) {
		$configArray[$this->confId]['forceSearch'] = true;
		$configArray[$this->confId]['requiredFormFields'] = 'zip,company,city';
		$configurations = tx_mklib_util_TS::loadConfig4BE(
			'mksearch', 'mksearch',
			'/static/static_extensions_template/setup.txt',
			$configArray
		);

		$parameters = tx_rnbase::makeInstance('tx_rnbase_parameters');
		$configurations = $this->createConfigurations(
			$configurations->getConfigArray(), 'mksearch', 'mksearch',
			$parameters,
			tx_rnbase::makeInstance('tslib_cObj')
		);

		return tx_rnbase::makeInstance(
			'tx_mksearch_filter_LuceneBase',
			$parameters, $configurations, $this->confId
		);
	}

	/**
	 * @return array
	 */
	private function getDefaultFields() {
		return array( '__default__' => array ());
	}
}