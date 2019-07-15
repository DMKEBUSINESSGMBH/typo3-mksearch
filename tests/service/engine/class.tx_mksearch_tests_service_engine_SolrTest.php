<?php

/*
 *  @package tx_mksearch
 *  @subpackage tx_mksearch_tests
 *  @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2011 Hannes Bochmann <dev@dmk-ebusiness.de>
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
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_service_engine_SolrTest extends tx_mksearch_tests_Testcase
{
    /**
     * @group unit
     */
    public function testGetHitsFromSolrResponseIfNotGrouped()
    {
        $firstDocument = new Apache_Solr_Document();
        $firstDocument->someField = 'someValue';
        $secondDocument = new Apache_Solr_Document();
        $secondDocument->someOtherField = 'someOtherValue';

        $response = $this->getMock('Apache_Solr_Response', array(), array(), '', false);

        $response->response = new stdClass();
        $response->response->docs = array(0 => $firstDocument, 1 => $secondDocument);

        $hits = tx_mksearch_service_engine_Solr::getHitsFromSolrResponse($response, array());

        self::assertEquals(array('someField' => 'someValue'), $hits[0]->getRecord());
        self::assertEquals(array('someOtherField' => 'someOtherValue'), $hits[1]->getRecord());
    }

    /**
     * @group unit
     */
    public function testGetHitsFromSolrResponseIfGrouped()
    {
        $firstDocument = new Apache_Solr_Document();
        $firstDocument->someField = 'someValue';
        $secondDocument = new Apache_Solr_Document();
        $secondDocument->someOtherField = array(2);
        $thirdDocument = new Apache_Solr_Document();
        $thirdDocument->againSomeOtherField = array(1, 2);

        $response = $this->getMock('Apache_Solr_Response', array(), array(), '', false);

        $docList = new stdClass();
        $docList->doclist = new stdClass();
        $docList->doclist->docs = array(0 => $firstDocument, 1 => $secondDocument, 2 => $thirdDocument);
        $response->grouped = new stdClass();
        $response->grouped->groupField = new stdClass();
        $response->grouped->groupField->groups = array(0 => $docList);

        $hits = tx_mksearch_service_engine_Solr::getHitsFromSolrResponse($response, array('group' => 'true', 'group.field' => 'groupField'));

        self::assertEquals(array('someField' => 'someValue'), $hits[0]->getRecord());
        self::assertEquals(array('someOtherField' => ['2']), $hits[1]->getRecord());
        self::assertEquals(array('againSomeOtherField' => array(1, 2)), $hits[2]->getRecord());
    }

    /**
     * @group unit
     */
    public function testSearchSolrSetsCorrectNumberFoundDependingOnGroupedResult()
    {
        $response = $this->getMock('Apache_Solr_Response', array(), array(), '', false);
        $response->expects(self::any())
            ->method('getHttpStatus')
            ->will(self::returnValue(200));

        $response->response = new stdClass();
        $response->response->numFound = 123;
        $response->grouped = new stdClass();
        $response->grouped->someField = new stdClass();
        $response->grouped->someField->ngroups = 456;

        $solr = $this->getMock('Apache_Solr_Service', array(), array(), '', false);
        $solr->expects(self::any())
            ->method('search')
            ->will(self::returnValue($response));

        $service = $this->getMock('tx_mksearch_service_engine_Solr', array('getSolr'));
        $service->expects(self::any())
            ->method('getSolr')
            ->will(self::returnValue($solr));

        $options = array('group.field' => 'someField', 'group.ngroups' => 'true');
        $searchResult = $this->callInaccessibleMethod($service, 'searchSolr', array(), $options);
        self::assertSame(456, $searchResult['numFound']);

        $options = array('group.field' => 'someField');
        $searchResult = $this->callInaccessibleMethod($service, 'searchSolr', array(), $options);
        self::assertSame(123, $searchResult['numFound']);
    }

    /**
     * @group unit
     */
    public function testSearchSolrSetsHits()
    {
        $response = $this->getMock('Apache_Solr_Response', array(), array(), '', false);
        $response->expects(self::any())
            ->method('getHttpStatus')
            ->will(self::returnValue(200));

        $response->response = new stdClass();
        $response->response->docs = array(0 => new Apache_Solr_Document());

        $solr = $this->getMock('Apache_Solr_Service', array(), array(), '', false);
        $solr->expects(self::any())
            ->method('search')
            ->will(self::returnValue($response));

        $service = $this->getMock('tx_mksearch_service_engine_Solr', array('getSolr'));
        $service->expects(self::any())
            ->method('getSolr')
            ->will(self::returnValue($solr));

        $searchResult = $this->callInaccessibleMethod($service, 'searchSolr', array(), array());

        self::assertTrue(is_array($searchResult['items']));
        self::assertInstanceOf('tx_mksearch_model_SolrHit', $searchResult['items'][0]);
    }
}
