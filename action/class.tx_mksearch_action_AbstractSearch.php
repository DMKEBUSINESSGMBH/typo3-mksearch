<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2018 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
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
 * Abstract search action.
 *
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
abstract class tx_mksearch_action_AbstractSearch extends tx_rnbase_action_BaseIOC
{
    /**
     * The current search index to use.
     *
     * @var tx_mksearch_service_internal_Index
     */
    private $searchIndex = null;

    /**
     * Returns the model for the current used index.
     *
     * @param tx_rnbase_configurations $configurations
     * @param string                   $confId
     *
     * @throws Exception
     *
     * @return tx_mksearch_model_internal_Index
     */
    protected function getSearchIndex()
    {
        if (null == $this->searchIndex) {
            $configurations = $this->getConfigurations();
            $confId = $this->getConfId();

            $indexUid = $configurations->get($confId.'usedIndex');
            //let's see if we got a index to use via parameters
            if (empty($indexUid)) {
                $indexUid = $configurations->getParameters()->get('usedIndex');
            }

            $this->searchIndex = tx_mksearch_util_ServiceRegistry::getIntIndexService()->get($indexUid);
        }

        if (!$this->searchIndex->isValid()) {
            throw new Exception('Configured search index not found!');
        }

        return $this->searchIndex;
    }

    /**
     * Special links for configured keywords.
     */
    protected function handleSoftLink()
    {
        $parameters = $this->getParameters();
        $configurations = $this->getConfigurations();
        $confId = $this->getConfId();

        // Softlink Config beginnt direkt im Root, damit sie auch für andere
        // Suchen genutzt werden kann
        if (!$configurations->get('softlink.enable')) {
            return;
        }
        $paramName = $configurations->get($confId.'softlink.parameter');
        $paramName = $paramName ? $paramName : 'term';
        $value = $parameters->get($paramName);
        $value = $value ? substr($value, 0, 150) : '';
        $options = [];
        tx_rnbase_util_SearchBase::setConfigOptions($options, $configurations, 'softlink.options.');

        $database = Tx_Rnbase_Database_Connection::getInstance();
        $options['where'] = 'keyword='.$database->fullQuoteStr($value, 'tx_mksearch_keywords');
        $rows = $database->doSelect('link', 'tx_mksearch_keywords', $options);

        if (1 == count($rows)) {
            $link = $configurations->createLink(false);
            $link->destination($rows[0]['link']);

            // an own header name for the redirect can be useful if the redirect is done
            // during an ajax request. otherwise it's not possible to handle the redirect with
            // javascript as normal Location header is followed by the browser automatically.
            if ($redirectHeaderName = $configurations->get($confId.'softlink.redirectHeaderName')) {
                $utility = tx_rnbase_util_Typo3Classes::getHttpUtilityClass();
                header($utility::HTTP_STATUS_303);
                header($redirectHeaderName.': '.tx_rnbase_util_Network::locationHeaderUrl($link->makeUrl(false)));
            } else {
                $link->redirect();
            }
        }
    }

    /**
     * Creates a new Filter.
     *
     * @param string $confId
     *
     * @return tx_rnbase_IFilter
     */
    protected function createFilter($confId = null)
    {
        $confId = $confId ?: $this->getConfId().'filter.';

        $filter = tx_rnbase_filter_BaseFilter::createFilter(
            $this->getParameters(),
            $this->getConfigurations(),
            $this->getViewData(),
            $confId
        );

        if ($filter instanceof tx_mksearch_filter_IStoreIndex) {
            $filter->setSearchIndex($this->getSearchIndex());
        }

        return $filter;
    }
}
