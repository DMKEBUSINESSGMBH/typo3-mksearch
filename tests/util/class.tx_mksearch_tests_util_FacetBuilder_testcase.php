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
tx_rnbase::load('tx_mksearch_util_FacetBuilder');


class tx_mksearch_tests_util_FacetBuilder_testcase extends tx_phpunit_testcase {

	public function testBuildFacetsWithEmptyFacetData() {
		$facetData = array();
		$facetData = tx_mksearch_util_FacetBuilder::getInstance()->buildFacets($facetData);
		$this->assertTrue(is_array($facetData),'es wurde kein array zurück gegeben!');
		$this->assertTrue(empty($facetData),'es wurde kein leeres array zurück gegeben!');
	}
	
	public function testBuildFacets() {
		$facetData = new stdClass();
		$facetData->contentType = new stdClass();
		$facetData->contentType->news = 2;
		$facetData->contentType->offer = 34;
		$facetData->contentType->product = 6;
		$facetData = tx_mksearch_util_FacetBuilder::getInstance()->buildFacets($facetData);
		
		$this->assertTrue(is_array($facetData),'es wurde kein array zurück gegeben!');
		$this->assertEquals(3,count($facetData),'Das array hat nicht die richtige Größe!');
		$this->assertEquals('contentType',$facetData[0]->record['field'],'Datensatz 1 - Feld:field hat den falschen Wert!');
		$this->assertEquals('news',$facetData[0]->record['id'],'Datensatz 1 - Feld:id hat den falschen Wert!');
		$this->assertEquals('news',$facetData[0]->record['label'],'Datensatz 1 - Feld:label hat den falschen Wert!');
		$this->assertEquals(2,$facetData[0]->record['count'],'Datensatz 1 - Feld:count hat den falschen Wert!');
		$this->assertEquals('contentType',$facetData[1]->record['field'],'Datensatz 2 - Feld:field hat den falschen Wert!');
		$this->assertEquals('offer',$facetData[1]->record['id'],'Datensatz 2 - Feld:id hat den falschen Wert!');
		$this->assertEquals('offer',$facetData[1]->record['label'],'Datensatz 2 - Feld:label hat den falschen Wert!');
		$this->assertEquals(34,$facetData[1]->record['count'],'Datensatz 2 - Feld:count hat den falschen Wert!');
		$this->assertEquals('contentType',$facetData[2]->record['field'],'Datensatz 3 - Feld:field hat den falschen Wert!');
		$this->assertEquals('product',$facetData[2]->record['id'],'Datensatz 3 - Feld:id hat den falschen Wert!');
		$this->assertEquals('product',$facetData[2]->record['label'],'Datensatz 3 - Feld:label hat den falschen Wert!');
		$this->assertEquals(6,$facetData[2]->record['count'],'Datensatz 3 - Feld:count hat den falschen Wert!');
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/util/class.tx_mksearch_tests_util_Misc_testcase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/util/class.tx_mksearch_tests_util_Misc_testcase.php']);
}

?>