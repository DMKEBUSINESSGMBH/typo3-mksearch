<?php
/**
 *	@package TYPO3
 *  @subpackage tx_mksearch
 *  @author Hannes Bochmann <dev@dmk-ebusiness.de>
 *
 *  Copyright notice
 *
 *  (c) 2010 Hannes Bochmann <dev@dmk-ebusiness.de>
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
tx_rnbase::load('tx_mksearch_service_irfaq_Expert');
tx_rnbase::load('tx_mksearch_tests_Testcase');

/**
 * tx_mksearch_tests_service_irfaq_Expert_testcase
 *
 * @package 		TYPO3
 * @subpackage	 	mksearch
 * @author 			Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license 		http://www.gnu.org/licenses/lgpl.html
 * 					GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_service_irfaq_Expert_testcase extends tx_mksearch_tests_Testcase {

	/**
	 * {@inheritDoc}
	 * @see tx_mksearch_tests_Testcase::setUp()
	 */
	protected function setUp() {
		if (!tx_rnbase_util_Extensions::isLoaded('irfaq')) {
			self::markTestSkipped('irfaq nicht installiert');
		}
	}

	/**
	 * @group unit
	 */
	public function testGetSearchClass() {
		self::assertEquals('tx_mksearch_search_irfaq_Expert', tx_mksearch_util_ServiceRegistry::getIrfaqExpertService()->getSearchClass());
	}

	/**
	 * Damit testen wir nur ob der Datenbankzugriff keine Fehler verursacht
	 * @group integration
	 */
	public function testSearch() {
		tx_mksearch_util_ServiceRegistry::getIrfaqExpertService()->search(array(), array());
	}
}