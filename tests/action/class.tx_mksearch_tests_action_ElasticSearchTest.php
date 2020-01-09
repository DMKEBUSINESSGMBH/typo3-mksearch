<?php
/**
 * @author Hannes Bochmann
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

/**
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_action_ElasticSearchTest extends tx_mksearch_tests_Testcase
{
    /**
     * @group unit
     */
    public function testHandlePagebrowser()
    {
        $confId = 'elasticsearch.';
        $parameters = tx_rnbase::makeInstance(
            'tx_rnbase_parameters',
            ['pb-search456-pointer' => 2]
        );
        $configurations = $this->createConfigurations(
            [$confId => ['hit.' => ['pagebrowser.' => ['limit' => 20]]]],
            'mksearch',
            '',
            $parameters
        );
        $configurations->setParameters($parameters);

        $pluginUid = new ReflectionProperty('tx_rnbase_configurations', 'pluginUid');
        $pluginUid->setAccessible(true);
        $pluginUid->setValue($configurations, 456);

        $viewData = $configurations->getViewData();
        $fields = [];
        $options = ['limit' => 10];

        $searchEngine = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            ['getIndex']
        );
        $index = $this->getMock('stdClass', ['count']);
        $index->expects($this->once())
            ->method('count')
            ->will($this->returnValue(123));
        $searchEngine->expects($this->once())
            ->method('getIndex')
            ->will($this->returnValue($index));

        $action = tx_rnbase::makeInstance('tx_mksearch_action_ElasticSearch');
        $action->handlePageBrowser(
            $parameters,
            $configurations,
            $confId,
            $viewData,
            $fields,
            $options,
            $searchEngine
        );

        self::assertEquals(
            10,
            $options['limit'],
            'limit wurde verändert'
        );
        self::assertEquals(
            20,
            $options['offset'],
            'offset in options falsch'
        );

        $pageBrowser = $viewData->offsetGet('pagebrowser');
        $expectedPagebrowser = tx_rnbase::makeInstance(
            'tx_rnbase_util_PageBrowser',
            'search456'
        );
        $expectedPagebrowser->setState($parameters, 123, 10);
        self::assertEquals(
            $expectedPagebrowser,
            $pageBrowser,
            'pagebrowser falsch konfiguriert'
        );
    }

    /**
     * @group unit
     */
    public function testHandlePagebrowserWhenPageBrowserIdConfigured()
    {
        $confId = 'elasticsearch.';
        $parameters = tx_rnbase::makeInstance('tx_rnbase_parameters', []);
        $configurations = $this->createConfigurations(
            [$confId => ['hit.' => ['pagebrowser.' => [
                'limit' => 20,
                'pbid' => 'pagebrowserId',
            ]]]],
            'mksearch',
            '',
            $parameters
        );
        $configurations->setParameters($parameters);

        $viewData = $configurations->getViewData();
        $fields = [];
        $options = ['limit' => 10];

        $searchEngine = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            ['getIndex']
        );
        $index = $this->getMock('stdClass', ['count']);
        $index->expects($this->once())
            ->method('count')
            ->will($this->returnValue(123));
        $searchEngine->expects($this->once())
            ->method('getIndex')
            ->will($this->returnValue($index));

        $action = tx_rnbase::makeInstance('tx_mksearch_action_ElasticSearch');
        $action->handlePageBrowser(
            $parameters,
            $configurations,
            $confId,
            $viewData,
            $fields,
            $options,
            $searchEngine
        );

        self::assertEquals(
            10,
            $options['limit'],
            'limit wurde verändert'
        );
        self::assertEquals(
            0,
            $options['offset'],
            'offset in options falsch'
        );

        $pageBrowser = $viewData->offsetGet('pagebrowser');
        $expectedPagebrowser = tx_rnbase::makeInstance(
            'tx_rnbase_util_PageBrowser',
            'pagebrowserId'
        );
        $expectedPagebrowser->setState($parameters, 123, 10);
        self::assertEquals(
            $expectedPagebrowser,
            $pageBrowser,
            'pagebrowser falsch konfiguriert'
        );
    }

    /**
     * @group unit
     */
    public function testGetServiceRegistry()
    {
        self::assertEquals(
            'tx_mksearch_util_ServiceRegistry',
            $this->callInaccessibleMethod(
                tx_rnbase::makeInstance('tx_mksearch_action_ElasticSearch'),
                'getServiceRegistry'
            )
        );
    }

    /**
     * @group unit
     */
    public function testHandleRequestReturnsNullIfNosearchConfigured()
    {
        $confId = 'elasticsearch.';
        $parameters = tx_rnbase::makeInstance('tx_rnbase_parameters', []);
        $configurations = $this->createConfigurations(
            [$confId => ['nosearch' => true]],
            'mksearch',
            '',
            $parameters
        );
        $configurations->setParameters($parameters);

        $viewData = $configurations->getViewData();

        $action = $this->getMock(
            'tx_mksearch_action_ElasticSearch',
            ['getServiceRegistry', 'handlePageBrowser', 'getConfigurations']
        );
        $action->expects($this->never())
            ->method('getServiceRegistry');
        $action->expects($this->never())
            ->method('handlePageBrowser');
        $action->expects($this->any())
            ->method('getConfigurations')
            ->will($this->returnValue($configurations));

        $actionReturn = $action->handleRequest($parameters, $configurations, $viewData);

        self::assertFalse(
            $viewData->offsetExists('searchcount'),
            'doch searchcount in viewdata gesetzt'
        );
        self::assertFalse(
            $viewData->offsetExists('search'),
            'doch search in viewdata gesetzt'
        );
        self::assertNull(
            $actionReturn,
            'action gibt nicht NULL zurück'
        );
    }

    /**
     * @group unit
     */
    public function testHandleRequest()
    {
        $confId = 'elasticsearch.';
        $parameters = tx_rnbase::makeInstance('tx_rnbase_parameters', []);
        $configurations = $this->createConfigurations(
            [$confId => [
                'filter.' => [
                    'forceSearch' => true,
                    'class' => 'tx_mksearch_filter_ElasticSearchBase',
                    'fields.' => ['term' => 'testterm'],
                    'options.' => ['limt' => 123],
                ],
            ]],
            'mksearch',
            '',
            $parameters
        );
        $configurations->setParameters($parameters);

        $viewData = $configurations->getViewData();

        $action = $this->getMock(
            'tx_mksearch_action_ElasticSearch',
            ['getSearchIndex', 'getServiceRegistry', 'handlePageBrowser', 'getConfigurations']
        );
        $index = tx_rnbase::makeInstance('tx_mksearch_model_internal_Index', []);
        $action->expects($this->once())
            ->method('getSearchIndex')
            ->will($this->returnValue($index));
        $action->expects($this->any())
            ->method('getConfigurations')
            ->will($this->returnValue($configurations));

        $serviceRegistry = $this->getMock(
            'stdClass',
            ['getSearchEngine']
        );
        $searchEngine = $this->getMock(
            'tx_mksearch_service_engine_ElasticSearch',
            ['openIndex', 'search']
        );
        $searchEngine->expects($this->once())
            ->method('openIndex')
            ->with($index);
        $searchEngine->expects($this->once())
            ->method('search')
            ->with(['term' => 'testterm'], ['limt' => 123], $configurations)
            ->will($this->returnValue(
                ['items' => 'search hits', 'numFound' => 987]
            ));
        $serviceRegistry->expects($this->once())
            ->method('getSearchEngine')
            ->with($index)
            ->will($this->returnValue($searchEngine));
        $action->expects($this->once())
            ->method('getServiceRegistry')
            ->will($this->returnValue($serviceRegistry));

        $action->expects($this->once())
            ->method('handlePageBrowser')
            ->with(
                $parameters,
                $configurations,
                $confId,
                $viewData,
                ['term' => 'testterm'],
                ['limt' => 123],
                $searchEngine
            );

        $actionReturn = $action->handleRequest($parameters, $configurations, $viewData);

        self::assertEquals(
            '987',
            $viewData->offsetGet('searchcount'),
            'searchcount in viewdata nicht gesetzt'
        );
        self::assertEquals(
            'search hits',
            $viewData->offsetGet('search'),
            'search in viewdata nicht gesetzt'
        );
        self::assertNull(
            $actionReturn,
            'action gibt nicht NULL zurück'
        );
    }
}
