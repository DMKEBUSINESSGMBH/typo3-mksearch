<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2014 DMK E-BUSINESS GmbH
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
tx_rnbase::load('tx_mksearch_util_FacetBuilder');

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
class tx_mksearch_tests_util_FacetBuilder_testcase
	extends tx_mksearch_tests_Testcase {

	public function testBuildFacetsWithEmptyFacetData() {
		$facetData = array();
		$facetData = tx_mksearch_util_FacetBuilder::getInstance()->buildFacets($facetData);
		self::assertTrue(is_array($facetData),'es wurde kein array zurück gegeben!');
		self::assertTrue(empty($facetData),'es wurde kein leeres array zurück gegeben!');
	}

	public function testBuildFacets() {
		$facetData = new stdClass();
		$facetData->contentType = new stdClass();
		$facetData->contentType->news = 2;
		$facetData->contentType->offer = 34;
		$facetData->contentType->product = 6;
		// die facetten kommen immer grupiert!
		$facetGroups = tx_mksearch_util_FacetBuilder::getInstance()->buildFacets($facetData);


		self::assertTrue(is_array($facetGroups), 'es wurde kein array zurück gegeben!');
		self::assertEquals(1, count($facetGroups), 'Das array hat nicht die richtige Größe!');

		$facetGroup = reset($facetGroups);
		self::assertInstanceOf('tx_rnbase_model_base', $facetGroup);
		self::assertEquals('contentType', $facetGroup->getField());

		// in einer gruppe sind die eigentlichen facetten enthalten
		$array = $facetGroup->getItems();

		self::assertTrue(is_array($array), 'es wurde kein array zurück gegeben!');
		self::assertEquals(3, count($array), 'Das array hat nicht die richtige Größe!');
		self::assertEquals('contentType', $array[0]->record['field'], 'Datensatz 1 - Feld:field hat den falschen Wert!');
		self::assertEquals('news', $array[0]->record['id'], 'Datensatz 1 - Feld:id hat den falschen Wert!');
		self::assertEquals('news', $array[0]->record['label'], 'Datensatz 1 - Feld:label hat den falschen Wert!');
		self::assertEquals(2, $array[0]->record['count'], 'Datensatz 1 - Feld:count hat den falschen Wert!');
		self::assertEquals('contentType', $array[1]->record['field'], 'Datensatz 2 - Feld:field hat den falschen Wert!');
		self::assertEquals('offer', $array[1]->record['id'], 'Datensatz 2 - Feld:id hat den falschen Wert!');
		self::assertEquals('offer', $array[1]->record['label'], 'Datensatz 2 - Feld:label hat den falschen Wert!');
		self::assertEquals(34, $array[1]->record['count'], 'Datensatz 2 - Feld:count hat den falschen Wert!');
		self::assertEquals('contentType', $array[2]->record['field'], 'Datensatz 3 - Feld:field hat den falschen Wert!');
		self::assertEquals('product', $array[2]->record['id'], 'Datensatz 3 - Feld:id hat den falschen Wert!');
		self::assertEquals('product', $array[2]->record['label'], 'Datensatz 3 - Feld:label hat den falschen Wert!');
		self::assertEquals(6, $array[2]->record['count'], 'Datensatz 3 - Feld:count hat den falschen Wert!');
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/util/class.tx_mksearch_tests_util_Misc_testcase.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/util/class.tx_mksearch_tests_util_Misc_testcase.php']);
}

?>
