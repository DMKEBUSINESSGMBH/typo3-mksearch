<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 DMK E-Business GmbH
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


require_once tx_rnbase_util_Extensions::extPath('mksearch', 'lib/Apache/Solr/Document.php');
tx_rnbase::load('tx_mksearch_tests_DbTestcase');
tx_rnbase::load('tx_mksearch_tests_Util');



/**
 * Wir müssen in diesem Fall mit der DB testen da wir die pages
 * Tabelle benötigen
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_tests
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_model_cal_Event_testcase
	extends tx_mksearch_tests_DbTestcase {

	/**
	 * Constructs a test case with the given name.
	 *
	 * @param string $name the name of a testcase
	 * @param array $data ?
	 * @param string $dataName ?
	 */
	public function __construct($name = NULL, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);

		$this->importDataSets[] = tx_mksearch_tests_Util::getFixturePath('db/cal_event_category_mm.xml');
		$this->importDataSets[] = tx_mksearch_tests_Util::getFixturePath('db/cal_category.xml');
	}

	/**
	 * setUp() = init DB etc.
	 */
	protected function setUp() {
		$this->importExtensions[] = 'cal';
		parent::setUp();
	}

	/**
	 */
	public function testgetCategoriesReturnsCorrectCategories() {
		$event = tx_rnbase::makeInstance(
			'tx_mksearch_model_cal_Event',array('uid' => 1)
		);

		$categories = $event->getCategories();

		self::assertEquals(2, count($categories), 'Mehr Kategorien als erwartet.');
		self::assertEquals(
			'First Category',
			$categories[0]->getTitle(),
			'Titel der 1. Kategorie falsch'
		);
		self::assertEquals(
			'Second Category',
			$categories[1]->getTitle(),
			'Titel der 2. Kategorie falsch'
		);
	}
}