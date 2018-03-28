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
 * Base search action
 *
 * @package TYPO3
 * @subpackage tx_mksearch
 * @author Hannes Bochmann
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_action_Search extends tx_mksearch_action_AbstractSearch
{

    /**
     *
     * @param array_object $parameters
     * @param tx_rnbase_configurations $configurations
     * @param array_object $viewData
     * @return string error msg or null
     */
    public function handleRequest(&$parameters, &$configurations, &$viewData)
    {
        $confId = $this->getConfId();
        $this->handleSoftLink();

        $filter = $this->createFilter($confId);

        //manchmal will man nur ein Suchformular auf jeder Seite im Header einbinden
        //dieses soll dann aber nur auf eine Ergebnisseite verweisen ohne
        //selbst zu suchen
        if ($configurations->get($confId.'nosearch')) {
            return null;
        }

        $fields = array();
        $options = array();
        $items = array();
        if ($filter->init($fields, $options)) {
            // Adjust Pagebrowser
            $this->handlePageBrowser($parameters, $configurations, $viewData, $fields, $options);
            $index = $this->getSearchIndex();

            $searchEngine = tx_mksearch_util_ServiceRegistry::getSearchEngine($index);
            $searchEngine->openIndex($index);
            $items = $searchEngine->search($fields, $options, $configurations);
            $searchEngine->closeIndex();
        }

        $viewData->offsetSet('searchcount', count($items));
        $viewData->offsetSet('search', $items);

        return null;
    }

    /**
     * In Lucene funktioniert die Pagination etwas anders. Lucene liefert nämlich immer alle
     * Suchergebnisse. Daher muss man selbst den passenden Ausschnitt auswählen. Damit das trotzdem
     * performant bleibt, liefert Lucene nur kleine Flyweight-Objekte für die Treffer. Der Abruf der
     * eigentlichen Dokumente erfolgt erst in einem zweiten Schritt. Somit wird für die Pagination nur
     * mit den Flyweights gearbeitet. Und das übernimmt der Lucene-Engine gleich selbst, wenn man ihr
     * eine Instanz des PageBrowsers übergibt.
     *
     * @param unknown $parameters
     * @param unknown $configurations
     * @param unknown $viewdata
     * @param unknown $fields
     * @param unknown $options
     *
     * @todo 404 werfen wenn Seite außerhalb des Bereichs -> siehe $pageBrowser->markPageNotFoundIfPointerOutOfRange()
     */
    protected function handlePageBrowser($parameters, $configurations, $viewdata, &$fields, &$options)
    {
        $confId = $this->getConfId();
        $typoScriptPathPageBrowser = $confId . 'hit.pagebrowser.';
        if (is_array($conf = $configurations->get($typoScriptPathPageBrowser))) {
            // PageBrowser initialisieren
            $pageBrowserId = $conf['pbid'] ? $conf['pbid'] : 'search'.$configurations->getPluginId();
            /* @var $pageBrowser tx_rnbase_util_PageBrowser */
            $pageBrowser = tx_rnbase::makeInstance('tx_rnbase_util_PageBrowser', $pageBrowserId);
            $pageSize = intval($conf['limit']);
            // Den PageBrowser mit PageSize und der aktuellen Seite füllen
            $pageBrowser->setPageSize($pageSize);
            $pageBrowser->setPointerByParameters($parameters);
            $options['pb'] = $pageBrowser;
            $viewdata->offsetSet('pagebrowser', $pageBrowser);
        }
    }


    public function getTemplateName()
    {
        return 'searchlucene';
    }
    public function getViewClassName()
    {
        return 'tx_mksearch_view_Search';
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/action/class.tx_mksearch_action_Search.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/action/class.tx_mksearch_action_Search.php']);
}
