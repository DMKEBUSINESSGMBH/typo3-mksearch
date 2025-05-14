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
 * Elastic search action.
 *
 * @author Hannes Bochmann
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_action_ElasticSearch extends tx_mksearch_action_AbstractSearch
{
    protected function handleRequest(Sys25\RnBase\Frontend\Request\RequestInterface $request)
    {
        $configurations = $request->getConfigurations();
        $parameters = $request->getParameters();
        $viewData = $request->getViewContext();
        $confId = $this->getConfId();
        $this->handleSoftLink($request);

        $filter = $this->createFilter($request);

        if ($configurations->get($confId.'nosearch')) {
            return null;
        }

        $fields = [];
        $options = [];

        $searchResult = [];
        if ($filter->init($fields, $options)) {
            $index = $this->getSearchIndex($request);

            // wir rufen die Methode mit call_user_func_array auf, da sie
            // statisch ist, womit wir diese nicht mocken könnten
            $searchEngine = call_user_func_array(
                [$this->getServiceRegistry(), 'getSearchEngine'],
                [$index]
            );
            $searchEngine->openIndex($index);

            $this->handlePageBrowser(
                $parameters,
                $configurations,
                $confId,
                $viewData,
                $fields,
                $options,
                $searchEngine
            );

            $searchResult = $searchEngine->search($fields, $options, $configurations);
        }

        $viewData->offsetSet('result', $searchResult);
        $viewData->offsetSet('searchcount', $searchResult['numFound'] ?? 0);
        $viewData->offsetSet('search', $searchResult['items'] ?? []);

        return null;
    }

    /**
     * @return string
     */
    protected function getSearchSolrAction(): object
    {
        return TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_action_SearchSolr');
    }

    protected function getServiceRegistry(): string
    {
        return 'tx_mksearch_util_ServiceRegistry';
    }

    public function handlePageBrowser(
        Sys25\RnBase\Frontend\Request\ParametersInterface $parameters,
        Sys25\RnBase\Configuration\ConfigurationInterface $configurations,
        string $confId,
        ArrayObject $viewdata,
        array &$fields,
        array &$options,
        tx_mksearch_service_engine_ElasticSearch $searchEngine,
    ): void {
        $typoScriptPathPageBrowser = $confId.'hit.pagebrowser.';
        if ((isset($options['limit']))
            && is_array($conf = $configurations->get($confId.'hit.pagebrowser.'))
        ) {
            // PageBrowser initialisieren
            $pageBrowserId = $conf['pbid'] ?? 'search'.$configurations->getPluginId();
            /* @var $pageBrowser \Sys25\RnBase\Utility\PageBrowser */
            $pageBrowser = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                Sys25\RnBase\Utility\PageBrowser::class,
                $pageBrowserId
            );

            $listSize = 0;
            if (($result = $searchEngine->getIndex()->count($fields['term'] ?? '')) !== 0) {
                $listSize = $result;
            }

            $pageBrowser->setState($parameters, $listSize, $options['limit']);
            $state = $pageBrowser->getState();

            $pageBrowser->markPageNotFoundIfPointerOutOfRange($configurations, $typoScriptPathPageBrowser);

            $options = array_merge($options, $state);
            $viewdata->offsetSet('pagebrowser', $pageBrowser);
        }
    }

    /**
     * (non-PHPdoc).
     *
     * @see \Sys25\RnBase\Frontend\Controller\AbstractAction::getTemplateName()
     */
    protected function getTemplateName()
    {
        return 'elasticsearch';
    }

    /**
     * (non-PHPdoc).
     *
     * @see \Sys25\RnBase\Frontend\Controller\AbstractAction::getViewClassName()
     */
    protected function getViewClassName()
    {
        return 'tx_mksearch_view_Search';
    }
}
