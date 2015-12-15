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

	public function testBuildFacetsWithQueryFacets() {
		$facetCount = new stdClass();
		$facetCount->facet_queries = $this->buildQueryFacets();

		$facetGroups = tx_mksearch_util_FacetBuilder::getInstance()->buildFacets($facetCount);

		self::assertTrue(is_array($facetGroups), 'es wurde kein array zurück gegeben!');
		self::assertEquals(2, count($facetGroups), 'Das array hat nicht die richtige Größe!');

		$facetGroup = array_shift($facetGroups);
		self::assertInstanceOf('tx_rnbase_model_base', $facetGroup);
		self::assertEquals('date', $facetGroup->getField());

		$facetItems = $facetGroup->getItems();

		self::assertEquals(3, count($facetItems));
		self::assertEquals(tx_mksearch_model_Facet::TYPE_QUERY, $facetItems[0]->getFacetType());

		$facetGroup = array_shift($facetGroups);
		self::assertInstanceOf('tx_rnbase_model_base', $facetGroup);
		self::assertEquals('price', $facetGroup->getField());

		$facetItems = $facetGroup->getItems();
		self::assertEquals(2, count($facetItems));
		self::assertEquals(tx_mksearch_model_Facet::TYPE_QUERY, $facetItems[0]->getFacetType());
	}

	public function testBuildFacetsWithPivotFacets() {
		$facetCount = new stdClass();
		$facetCount->facet_pivot = $this->buildPivotFacets();

		$facetGroups = tx_mksearch_util_FacetBuilder::getInstance()->buildFacets($facetCount);

		self::assertTrue(is_array($facetGroups));
		self::assertEquals(2, count($facetGroups));

		$facetGroup = array_shift($facetGroups);
		self::assertInstanceOf('tx_rnbase_model_base', $facetGroup);
		// fiel_one,fiel_two,fiel_three has to be converted to fiel_one-fiel_two-fiel_three!
		self::assertEquals('fiel_one-fiel_two-fiel_three', $facetGroup->getField());

		$facetItems = $facetGroup->getItems();

		self::assertEquals(1, count($facetItems));
		self::assertEquals(tx_mksearch_model_Facet::TYPE_PIVOT, $facetItems[0]->getFacetType());
		self::assertEquals('fiel_one', $facetItems[0]->getField());
		self::assertEquals('One', $facetItems[0]->getLabel());
		self::assertEquals('1', $facetItems[0]->getCount());
		self::assertTrue(is_array($facetItems[0]->getChilds()));
		self::assertTrue($facetItems[0]->hasChilds());

		$facetChilds = $facetItems[0]->getChilds();

		self::assertEquals(2, count($facetChilds));
		self::assertEquals(tx_mksearch_model_Facet::TYPE_PIVOT, $facetChilds[0]->getFacetType());
		self::assertEquals('fiel_two', $facetChilds[0]->getField());
		self::assertEquals('Two 1', $facetChilds[0]->getLabel());
		self::assertEquals('5', $facetChilds[0]->getCount());
		self::assertTrue($facetChilds[0]->hasChilds());
		self::assertEquals(tx_mksearch_model_Facet::TYPE_PIVOT, $facetChilds[1]->getFacetType());
		self::assertEquals('fiel_two', $facetChilds[1]->getField());
		self::assertEquals('Two 2', $facetChilds[1]->getLabel());
		self::assertEquals('2', $facetChilds[1]->getCount());
		self::assertFalse($facetChilds[1]->hasChilds());

		$facetChilds = $facetChilds[0]->getChilds();

		self::assertEquals(1, count($facetChilds));
		self::assertEquals(tx_mksearch_model_Facet::TYPE_PIVOT, $facetChilds[0]->getFacetType());
		self::assertEquals('fiel_three', $facetChilds[0]->getField());
		self::assertEquals('Three', $facetChilds[0]->getLabel());
		self::assertEquals('7', $facetChilds[0]->getCount());
		self::assertFalse($facetChilds[0]->hasChilds());


		$facetGroup = array_shift($facetGroups);
		self::assertInstanceOf('tx_rnbase_model_base', $facetGroup);
		self::assertEquals('field_main-fiel_sub', $facetGroup->getField());

		$facetItems = $facetGroup->getItems();

		self::assertEquals(1, count($facetItems));
		self::assertEquals(tx_mksearch_model_Facet::TYPE_PIVOT, $facetItems[0]->getFacetType());
		self::assertEquals('field_main', $facetItems[0]->getField());
		self::assertEquals('Main', $facetItems[0]->getLabel());
		self::assertEquals('7', $facetItems[0]->getCount());
		self::assertTrue(is_array($facetItems[0]->getChilds()));
		self::assertTrue($facetItems[0]->hasChilds());

		$facetChilds = $facetItems[0]->getChilds();

		self::assertEquals(1, count($facetChilds));
		self::assertEquals(tx_mksearch_model_Facet::TYPE_PIVOT, $facetChilds[0]->getFacetType());
		self::assertEquals('fiel_sub', $facetChilds[0]->getField());
		self::assertEquals('Sub', $facetChilds[0]->getLabel());
		self::assertEquals('5', $facetChilds[0]->getCount());
		self::assertFalse($facetChilds[0]->hasChilds());
	}


	public function testBuildFacetsWithFieldFacets() {
		$facetCount = new stdClass();
		$facetCount->facet_fields = $this->buildFieldFacets();

		$facetGroups = tx_mksearch_util_FacetBuilder::getInstance()->buildFacets($facetCount);
		self::assertTrue(is_array($facetGroups), 'es wurde kein array zurück gegeben!');
		self::assertEquals(1, count($facetGroups), 'Das array hat nicht die richtige Größe!');

		$facetGroup = reset($facetGroups);
		self::assertInstanceOf('tx_rnbase_model_base', $facetGroup);
		self::assertEquals('contentType', $facetGroup->getField());

		$this->doFieldFacetAssertations($facetGroup);
	}

	public function testBuildFacets() {
		$facetData = new stdClass();
		$facetData->facet_fields = $this->buildFieldFacets();
		// die facetten kommen immer grupiert!
		$facetGroups = tx_mksearch_util_FacetBuilder::getInstance()->buildFacets($facetData);


		self::assertTrue(is_array($facetGroups), 'es wurde kein array zurück gegeben!');
		self::assertEquals(1, count($facetGroups), 'Das array hat nicht die richtige Größe!');

		$facetGroup = reset($facetGroups);
		self::assertInstanceOf('tx_rnbase_model_base', $facetGroup);
		self::assertEquals('contentType', $facetGroup->getField());

		$this->doFieldFacetAssertations($facetGroup);
	}
	public function testSortFacets() {
		$facets = array(
			// field facets
			$this->getModel(
				array(
					'uid' => ++$uid,
					'field' => $field,
					'items' => array(
						tx_rnbase::makeInstance(
							'tx_mksearch_model_Facet',
							'category_dfs_ms',
							1,
							array(
								'label' => 'A',
								'sorting' => 5,
							),
							50
						),
						tx_rnbase::makeInstance(
							'tx_mksearch_model_Facet',
							'category_dfs_ms',
							1,
							array(
								'label' => 'B',
								'sorting' => 3,
							),
							30
						),
						tx_rnbase::makeInstance(
							'tx_mksearch_model_Facet',
							'category_dfs_ms',
							1,
							array(
								'label' => 'C',
								'sorting' => 1,
							),
							10
						),
					),
				)
			),
			// @TODO: write sorting tests for the other facet types!
		);

		$sorted = $this->callInaccessibleMethod(
			tx_mksearch_util_FacetBuilder::getInstance(),
			'sortFacets',
			$facets
		);


		self::assertTrue(is_array($sorted));
		self::assertInstanceOf(tx_rnbase_model_base, $sorted[0]);
		self::assertTrue(is_array($sorted[0]->record['items']));
		self::assertCount(3, $sorted[0]->record['items']);
		self::assertSame('C', $sorted[0]->record['items'][0]->record['label']);
		self::assertSame('B', $sorted[0]->record['items'][1]->record['label']);
		self::assertSame('A', $sorted[0]->record['items'][2]->record['label']);
	}
	/**
	 * Check assertations for field facet contentType
	 * @param unknown $facetGroup
	 */
	private function doFieldFacetAssertations($facetGroup) {
		// in einer gruppe sind die eigentlichen facetten enthalten
		$array = $facetGroup->getItems();

		self::assertTrue(is_array($array), 'es wurde kein array zurück gegeben!');
		self::assertEquals(3, count($array), 'Das array hat nicht die richtige Größe!');
		self::assertEquals('contentType', $array[0]->record['field'], 'Datensatz 1 - Feld:field hat den falschen Wert!');
		self::assertEquals('news', $array[0]->record['id'], 'Datensatz 1 - Feld:id hat den falschen Wert!');
		self::assertEquals('news', $array[0]->record['label'], 'Datensatz 1 - Feld:label hat den falschen Wert!');
		self::assertEquals(tx_mksearch_model_Facet::TYPE_FIELD, $array[0]->getFacetType());
		self::assertEquals(2, $array[0]->record['count'], 'Datensatz 1 - Feld:count hat den falschen Wert!');
		self::assertEquals('contentType', $array[1]->record['field'], 'Datensatz 2 - Feld:field hat den falschen Wert!');
		self::assertEquals('offer', $array[1]->record['id'], 'Datensatz 2 - Feld:id hat den falschen Wert!');
		self::assertEquals('offer', $array[1]->record['label'], 'Datensatz 2 - Feld:label hat den falschen Wert!');
		self::assertEquals(tx_mksearch_model_Facet::TYPE_FIELD, $array[1]->getFacetType());
		self::assertEquals(34, $array[1]->record['count'], 'Datensatz 2 - Feld:count hat den falschen Wert!');
		self::assertEquals('contentType', $array[2]->record['field'], 'Datensatz 3 - Feld:field hat den falschen Wert!');
		self::assertEquals('product', $array[2]->record['id'], 'Datensatz 3 - Feld:id hat den falschen Wert!');
		self::assertEquals('product', $array[2]->record['label'], 'Datensatz 3 - Feld:label hat den falschen Wert!');
		self::assertEquals(tx_mksearch_model_Facet::TYPE_FIELD, $array[2]->getFacetType());
		self::assertEquals(6, $array[2]->record['count'], 'Datensatz 3 - Feld:count hat den falschen Wert!');
	}

	private function buildFieldFacets() {
		$facetData = new stdClass();
		$facetData->contentType = new stdClass();
		$facetData->contentType->news = 2;
		$facetData->contentType->offer = 34;
		$facetData->contentType->product = 6;
		return $facetData;
	}
	private function buildQueryFacets() {
		$facetData = new stdClass();
		$facetData->date_query1 = 2;
		$facetData->date_query2 = 34;
		$facetData->date_query3 = 6;
		$facetData->price_query1 = 12;
		$facetData->price_query2 = 25;
		return $facetData;
	}
	private function buildPivotFacets() {
		$facetData = (object) array(
			'fiel_one,fiel_two,fiel_three' => array(
				(object) array(
					'field' => 'fiel_one',
					'value' => 'One',
					'count' => 1,
					'pivot' => array(
						(object) array(
							'field' => 'fiel_two',
							'value' => 'Two 1',
							'count' => 5,
							'pivot' => array(
								(object) array(
									'field' => 'fiel_three',
									'value' => 'Three',
									'count' => 7,
								),
							)
						),
						(object) array(
							'field' => 'fiel_two',
							'value' => 'Two 2',
							'count' => 2,
						),
					)
				),
			),
			'field_main,fiel_sub' => array(
				(object) array(
					'field' => 'field_main',
					'value' => 'Main',
					'count' => 7,
					'pivot' => array(
						(object) array(
							'field' => 'fiel_sub',
							'value' => 'Sub',
							'count' => 5,
						),
					)
				),
			),
		);
		return $facetData;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/util/class.tx_mksearch_tests_util_Misc_testcase.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/util/class.tx_mksearch_tests_util_Misc_testcase.php']);
}

