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
 * @author Hannes Bochmann
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_action_ElasticSearchTest extends tx_mksearch_tests_Testcase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__.'/../../Resources/Private/PHP/Elastica/Composer/autoload.php';
    }

    public function testHandlePagebrowser(): void
    {
        $confId = 'elasticsearch.';
        $parameters = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Frontend\Request\Parameters::class,
            ['pb-search456-pointer' => 2]
        );
        $configurations = Sys25\RnBase\Testing\TestUtility::createConfigurations(
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
        $index = $this->getMockBuilder(Elastica\Index::class)
            ->onlyMethods(['count'])
            ->disableOriginalConstructor()
            ->getMock();
        $index->expects($this->once())
            ->method('count')
            ->willReturn(123);
        $searchEngine->expects($this->once())
            ->method('getIndex')
            ->willReturn($index);

        $action = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_action_ElasticSearch');
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
        $expectedPagebrowser = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Utility\PageBrowser::class,
            'search456'
        );
        $expectedPagebrowser->setState($parameters, 123, 10);
        self::assertEquals(
            $expectedPagebrowser,
            $pageBrowser,
            'pagebrowser falsch konfiguriert'
        );
    }

    public function testHandlePagebrowserWhenPageBrowserIdConfigured(): void
    {
        $confId = 'elasticsearch.';
        $parameters = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Sys25\RnBase\Frontend\Request\Parameters::class, []);
        $configurations = Sys25\RnBase\Testing\TestUtility::createConfigurations(
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
        $index = $this->getMockBuilder(Elastica\Index::class)
            ->onlyMethods(['count'])
            ->disableOriginalConstructor()
            ->getMock();
        $index->expects($this->once())
            ->method('count')
            ->willReturn(123);
        $searchEngine->expects($this->once())
            ->method('getIndex')
            ->willReturn($index);

        $action = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_action_ElasticSearch');
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
        $expectedPagebrowser = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            Sys25\RnBase\Utility\PageBrowser::class,
            'pagebrowserId'
        );
        $expectedPagebrowser->setState($parameters, 123, 10);
        self::assertEquals(
            $expectedPagebrowser,
            $pageBrowser,
            'pagebrowser falsch konfiguriert'
        );
    }

    public function testGetServiceRegistry(): void
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

    public function testHandleRequestReturnsNullIfNosearchConfigured(): void
    {
        $confId = 'elasticsearch.';
        $parameters = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Sys25\RnBase\Frontend\Request\Parameters::class, []);
        $configurations = Sys25\RnBase\Testing\TestUtility::createConfigurations(
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

        $request = new Sys25\RnBase\Frontend\Request\Request($parameters, $configurations, '');
        $actionReturn = $this->callInaccessibleMethod($action, 'handleRequest', $request);

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

    public function testHandleRequest(): void
    {
        self::markTestSkipped(
            'Test needs refactoring. Mocking of tx_mksearch_util_ServiceRegistry::getSearchEngine() does not work
            because it is static. Maybe we need prophecy.'
        );
        $confId = 'elasticsearch.';
        $parameters = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(Sys25\RnBase\Frontend\Request\Parameters::class, []);
        $configurations = Sys25\RnBase\Testing\TestUtility::createConfigurations(
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
        $index = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_internal_Index', []);
        $action->expects($this->once())
            ->method('getSearchIndex')
            ->willReturn($index);

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
            ->willReturn(
                ['items' => 'search hits', 'numFound' => 987]
            );
        $serviceRegistry->expects($this->once())
            ->method('getSearchEngine')
            ->with($index)
            ->willReturn($searchEngine);
        $action->expects($this->once())
            ->method('getServiceRegistry')
            ->willReturn($serviceRegistry);

        $request = new Sys25\RnBase\Frontend\Request\Request($parameters, $configurations, $confId);
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

        $actionReturn = $this->callInaccessibleMethod($action, 'handleRequest', $request);

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
