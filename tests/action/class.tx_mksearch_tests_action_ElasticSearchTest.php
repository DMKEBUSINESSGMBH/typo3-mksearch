<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2020 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * @author Hannes Bochmann
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_action_ElasticSearchTest extends tx_mksearch_tests_Testcase
{
    protected function setUp(): void
    {
    }

    protected function tearDown(): void
    {
    }

    /**
     * @group unit
     */
    public function testHandlePagebrowser()
    {
        $confId = 'elasticsearch.';
        $parameters = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \Sys25\RnBase\Frontend\Request\Parameters::class,
            ['pb-search456-pointer' => 2]
        );
        $configurations = \Sys25\RnBase\Testing\TestUtility::createConfigurations(
            [$confId => ['hit.' => ['pagebrowser.' => ['limit' => 20]]]],
            'mksearch',
            '',
            $parameters
        );
        $configurations->setParameters($parameters);

        $pluginUid = new ReflectionProperty(Sys25\RnBase\Configuration\Processor::class, 'pluginUid');
        $pluginUid->setAccessible(true);
        $pluginUid->setValue($configurations, 456);

        $viewData = $configurations->getViewData();
        $fields = [];
        $options = ['limit' => 10];

        $searchEngine = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getIndex'])
            ->disableOriginalConstructor()
            ->getMock();
        $index = $this->getMockBuilder('stdClass')->addMethods(['count'])->getMock();
        $index->expects($this->once())
            ->method('count')
            ->will($this->returnValue(123));
        $searchEngine->expects($this->once())
            ->method('getIndex')
            ->will($this->returnValue($index));

        $action = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_action_ElasticSearch');
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
        $expectedPagebrowser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \Sys25\RnBase\Utility\PageBrowser::class,
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
        $parameters = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Frontend\Request\Parameters::class, []);
        $configurations = \Sys25\RnBase\Testing\TestUtility::createConfigurations(
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

        $searchEngine = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['getIndex'])
            ->disableOriginalConstructor()
            ->getMock();
        $index = $this->getMockBuilder('stdClass')->addMethods(['count'])->getMock();
        $index->expects($this->once())
            ->method('count')
            ->will($this->returnValue(123));
        $searchEngine->expects($this->once())
            ->method('getIndex')
            ->will($this->returnValue($index));

        $action = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_action_ElasticSearch');
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
        $expectedPagebrowser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \Sys25\RnBase\Utility\PageBrowser::class,
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
        $action = $this->getAccessibleMock(
            'tx_mksearch_action_ElasticSearch',
            ['handleRequest'],
            [],
            '',
            false
        );
        self::assertEquals(
            'tx_mksearch_util_ServiceRegistry',
            $action->_call('getServiceRegistry')
        );
    }

    /**
     * @group unit
     */
    public function testHandleRequestReturnsNullIfNosearchConfigured()
    {
        $confId = 'elasticsearch.';
        $parameters = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Frontend\Request\Parameters::class, []);
        $configurations = \Sys25\RnBase\Testing\TestUtility::createConfigurations(
            [$confId => ['nosearch' => true]],
            'mksearch',
            '',
            $parameters
        );
        $configurations->setParameters($parameters);

        $viewData = $configurations->getViewData();

        $action = $this->getMockBuilder('tx_mksearch_action_ElasticSearch')
            ->onlyMethods(['getServiceRegistry', 'handlePageBrowser'])
            ->disableOriginalConstructor()
            ->getMock();
        $action->expects($this->never())
            ->method('getServiceRegistry');
        $action->expects($this->never())
            ->method('handlePageBrowser');

        $request = new \Sys25\RnBase\Frontend\Request\Request($parameters, $configurations, '');
        $actionReturn = $action->handleRequest($request);

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
        $parameters = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Frontend\Request\Parameters::class, []);
        $configurations = \Sys25\RnBase\Testing\TestUtility::createConfigurations(
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

        $action = $this->getMockBuilder('tx_mksearch_action_ElasticSearch')
            ->onlyMethods(['getServiceRegistry', 'handlePageBrowser', 'getSearchIndex'])
            ->disableOriginalConstructor()
            ->getMock();
        $index = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_internal_Index', []);
        $action->expects($this->once())
            ->method('getSearchIndex')
            ->will($this->returnValue($index));

        $serviceRegistry = $this->getMockBuilder('stdClass')
            ->addMethods(['getSearchEngine'])
            ->disableOriginalConstructor()
            ->getMock();
        $searchEngine = $this->getMockBuilder('tx_mksearch_service_engine_ElasticSearch')
            ->onlyMethods(['openIndex', 'search'])
            ->disableOriginalConstructor()
            ->getMock();
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

        $request = new \Sys25\RnBase\Frontend\Request\Request($parameters, $configurations, $confId);
        $viewData = $request->getViewContext();
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

        $actionReturn = $action->handleRequest($request);

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
