<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2009-2018 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
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

tx_rnbase::load('tx_mksearch_action_AbstractSearch');
tx_rnbase::load('tx_rnbase_filter_BaseFilter');
tx_rnbase::load('tx_mksearch_util_ServiceRegistry');
tx_rnbase::load('tx_mksearch_action_SearchSolr');

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
    /**
     * @param array_object             $parameters
     * @param tx_rnbase_configurations $configurations
     * @param array_object             $viewData
     *
     * @return string error msg or null
     */
    public function handleRequest(&$parameters, &$configurations, &$viewData)
    {
        $confId = $this->getConfId();
        $this->handleSoftLink();

        $filter = $this->createFilter();

        if ($configurations->get($confId.'nosearch')) {
            return null;
        }

        $fields = array();
        $options = array();
        $items = array();

        if ($filter->init($fields, $options)) {
            $index = $this->getSearchIndex();

            // wir rufen die Methode mit call_user_func_array auf, da sie
            // statisch ist, womit wir diese nicht mocken könnten
            $searchEngine = call_user_func_array(
                array($this->getServiceRegistry(), 'getSearchEngine'),
                array($index)
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
        $viewData->offsetSet('searchcount', $searchResult['numFound']);
        $viewData->offsetSet('search', $searchResult['items']);

        return null;
    }

    /**
     * @return string
     */
    protected function getSearchSolrAction()
    {
        return tx_rnbase::makeInstance('tx_mksearch_action_SearchSolr');
    }

    /**
     * @return string
     */
    protected function getServiceRegistry()
    {
        return tx_mksearch_util_ServiceRegistry;
    }

    /**
     * @param tx_rnbase_parameters                     $parameters
     * @param tx_rnbase_configurations                 $configurations
     * @param unknown                                  $confId
     * @param ArrayObject                              $viewdata
     * @param array                                    $fields
     * @param array                                    $options
     * @param tx_mksearch_service_engine_ElasticSearch $index
     */
    public function handlePageBrowser(
        tx_rnbase_parameters $parameters,
        tx_rnbase_configurations $configurations,
        $confId,
        ArrayObject $viewdata,
        array &$fields,
        array &$options,
        tx_mksearch_service_engine_ElasticSearch $searchEngine
    ) {
        $typoScriptPathPageBrowser = $confId.'hit.pagebrowser.';
        if ((isset($options['limit']))
            && is_array($conf = $configurations->get($confId.'hit.pagebrowser.'))
        ) {
            // PageBrowser initialisieren
            $pageBrowserId = $conf['pbid'] ? $conf['pbid'] :
                            'search'.$configurations->getPluginId();
            /* @var $pageBrowser tx_rnbase_util_PageBrowser */
            $pageBrowser = tx_rnbase::makeInstance(
                'tx_rnbase_util_PageBrowser',
                $pageBrowserId
            );

            $listSize = 0;
            if ($result = $searchEngine->getIndex()->count($fields['term'])) {
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
     * @see tx_rnbase_action_BaseIOC::getTemplateName()
     */
    public function getTemplateName()
    {
        return 'elasticsearch';
    }

    /**
     * (non-PHPdoc).
     *
     * @see tx_rnbase_action_BaseIOC::getViewClassName()
     */
    public function getViewClassName()
    {
        return 'tx_mksearch_view_Search';
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/action/class.tx_mksearch_action_ElasticSearch.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/action/class.tx_mksearch_action_ElasticSearch.php'];
}
