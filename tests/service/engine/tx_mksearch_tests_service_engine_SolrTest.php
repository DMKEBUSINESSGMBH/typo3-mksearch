<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of the "mksearch" Extension for TYPO3 CMS.
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GNU Lesser General Public License can be found at
 * www.gnu.org/licenses/lgpl.html
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_service_engine_SolrTest extends tx_mksearch_tests_Testcase
{
    public function testGetHitsFromSolrResponseIfNotGrouped(): void
    {
        self::markTestSkipped('Test needs refactoring.');
        $firstDocument = new Apache_Solr_Document();
        $firstDocument->someField = 'someValue';

        $secondDocument = new Apache_Solr_Document();
        $secondDocument->someOtherField = 'someOtherValue';

        $response = $this->getMock('Apache_Solr_Response', [], [], '', false);

        $response->response = new stdClass();

        $response->response->docs = [0 => $firstDocument, 1 => $secondDocument];

        $hits = tx_mksearch_service_engine_Solr::getHitsFromSolrResponse($response, []);

        self::assertEquals(['someField' => 'someValue'], $hits[0]->getRecord());
        self::assertEquals(['someOtherField' => 'someOtherValue'], $hits[1]->getRecord());
    }

    public function testGetHitsFromSolrResponseIfGrouped(): void
    {
        self::markTestSkipped('Test needs refactoring.');
        $firstDocument = new Apache_Solr_Document();
        $firstDocument->someField = 'someValue';

        $secondDocument = new Apache_Solr_Document();
        $secondDocument->someOtherField = [2];

        $thirdDocument = new Apache_Solr_Document();
        $thirdDocument->againSomeOtherField = [1, 2];

        $response = $this->getMock('Apache_Solr_Response', [], [], '', false);

        $docList = new stdClass();
        $docList->doclist = new stdClass();
        $docList->doclist->docs = [0 => $firstDocument, 1 => $secondDocument, 2 => $thirdDocument];

        $response->grouped = new stdClass();
        $response->grouped->groupField = new stdClass();
        $response->grouped->groupField->groups = [0 => $docList];

        $hits = tx_mksearch_service_engine_Solr::getHitsFromSolrResponse($response, ['group' => 'true', 'group.field' => 'groupField']);

        self::assertEquals(['someField' => 'someValue'], $hits[0]->getRecord());
        self::assertEquals(['someOtherField' => ['2']], $hits[1]->getRecord());
        self::assertEquals(['againSomeOtherField' => [1, 2]], $hits[2]->getRecord());
    }

    public function testSearchSolrSetsCorrectNumberFoundDependingOnGroupedResult(): void
    {
        self::markTestSkipped('Test needs refactoring.');
        $response = $this->getMock('Apache_Solr_Response', [], [], '', false);
        $response->expects(self::any())
            ->method('getHttpStatus')
            ->willReturn(200);

        $response->response = new stdClass();
        $response->response->numFound = 123;
        $response->grouped = new stdClass();
        $response->grouped->someField = new stdClass();
        $response->grouped->someField->ngroups = 456;

        $solr = $this->getMock('Apache_Solr_Service', [], [], '', false);
        $solr->expects(self::any())
            ->method('search')
            ->willReturn($response);

        $service = $this->getMock('tx_mksearch_service_engine_Solr', ['getSolr']);
        $service->expects(self::any())
            ->method('getSolr')
            ->willReturn($solr);

        $options = ['group.field' => 'someField', 'group.ngroups' => 'true'];
        $searchResult = $this->callInaccessibleMethod($service, 'searchSolr', [], $options);
        self::assertSame(456, $searchResult['numFound']);

        $options = ['group.field' => 'someField'];
        $searchResult = $this->callInaccessibleMethod($service, 'searchSolr', [], $options);
        self::assertSame(123, $searchResult['numFound']);
    }

    public function testSearchSolrSetsHits(): void
    {
        self::markTestSkipped('Test needs refactoring.');
        $response = $this->getMock('Apache_Solr_Response', [], [], '', false);
        $response->expects(self::any())
            ->method('getHttpStatus')
            ->willReturn(200);

        $response->response = new stdClass();
        $response->response->docs = [0 => new Apache_Solr_Document()];

        $solr = $this->getMock('Apache_Solr_Service', [], [], '', false);
        $solr->expects(self::any())
            ->method('search')
            ->willReturn($response);

        $service = $this->getMock('tx_mksearch_service_engine_Solr', ['getSolr']);
        $service->expects(self::any())
            ->method('getSolr')
            ->willReturn($solr);

        $searchResult = $this->callInaccessibleMethod($service, 'searchSolr', [], []);

        self::assertTrue(is_array($searchResult['items']));
        self::assertInstanceOf('tx_mksearch_model_SolrHit', $searchResult['items'][0]);
    }
}
