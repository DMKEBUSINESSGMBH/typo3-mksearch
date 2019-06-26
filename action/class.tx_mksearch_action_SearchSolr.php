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
tx_rnbase::load('tx_mksearch_util_Misc');
tx_rnbase::load('tx_mksearch_util_SolrAutocomplete');

/**
 * Solr search action.
 *
 * @author Hannes Bochmann
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_action_SearchSolr extends tx_mksearch_action_AbstractSearch
{
    /**
     * @var string
     */
    protected $autocompleteConfId = 'autocomplete.';

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

        // Filter erstellen (z.B. Formular parsen)
        $filter = $this->createFilter($confId);

        // shall we prepare the javascript for the autocomplete feature
        $this->prepareAutocomplete();

        // manchmal will man nur ein Suchformular auf jeder Seite im Header einbinden
        // dieses soll dann aber nur auf eine Ergebnisseite verweisen ohne
        // selbst zu suchen
        if ($configurations->get($confId.'nosearch')) {
            return null;
        }

        $fields = array();
        $options = array();

        if ($parameters->get('debug')) {
            tx_rnbase::load('tx_mksearch_util_Misc');
            if (tx_mksearch_util_Misc::isDevIpMask()) {
                $options['debug'] = 1;
                $options['debugQuery'] = 'true';
            }
        }
        $result = null;
        if ($filter->init($fields, $options)) {
            // wenn der DisMaxRequestHandler genutzt wird, verursacht *:* ein leeres Ergebnis.
            // Hier muss sich dringend der Filter darum kümmern, was im Term steht!
            if (!array_key_exists('term', $fields)) {
                $fields['term'] = '*:*';
            }

            //get the index we shall search in
            $index = $this->getSearchIndex();
            $pageBrowser = $this->handlePageBrowser($parameters, $configurations, $confId, $viewData, $fields, $options, $index);

            if ($result = $this->searchSolr($fields, $options, $configurations, $index)) {
                // Jetzt noch die echte Listengröße im PageBrowser setzen
                if (is_object($pageBrowser)) {
                    $listSize = $result['numFound'];
                    $pageBrowser->setState($parameters, $listSize, $pageBrowser->getPageSize());
                }
            } else {
                $result = array('items' => array());
            }
        } // auch einen debug ausgeben, wenn nichts gesucht wird
        elseif ($options['debug']) {
            tx_rnbase::load('tx_rnbase_util_Debug');
            tx_rnbase_util_Debug::debug(
                array(
                    'Filter returns false, no search done.',
                    $fields, $options,
                ),
                'class.tx_mksearch_action_SearchSolr.php Line: '.__LINE__
            );
        }
        $viewData->offsetSet('result', $result);

        return $this->processAutocomplete();
    }

    /**
     * Generiert den Cache-Schlüssel für die aktuelle Anfrage.
     *
     * @param array $fields
     * @param array $options
     *
     * @return string
     */
    private function generateCacheKey(
        array $fields,
        array $options = array()
    ) {
        $data = array_merge($fields, $options);
        ksort($data);
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->generateCacheKey($value);
            }
            $data[$key] = $key.$value;
        }

        return 'search_'.md5(serialize($data));
    }

    /**
     * Sucht in Solr.
     *
     * @param array                            $fields
     * @param array                            $options
     * @param tx_rnbase_configurations         $configurations
     * @param tx_mksearch_model_internal_Index $index
     *
     * @return array|false
     */
    protected function searchSolr(&$fields, &$options, $configurations, $index)
    {
        // erstmal den cache fragen. Das ist vor allem interessant wenn
        // das plugin mehrfach auf einer Seite eingebunden wird. Dadurch
        // muss die Solr Suche nur einmal ausgeführt werden
        tx_rnbase::load('tx_rnbase_cache_Manager');
        $oCache = tx_rnbase_cache_Manager::getCache('mksearch');
        $sCacheKey = $this->generateCacheKey($fields, $options);
        if ($result = $oCache->get($sCacheKey)) {
            return $result;
        }
        //else nix im cache also Solr suchen lassen

        try {
            // in unserem fall sollte es der solr service sein!
            /* @var $searchEngine tx_mksearch_service_engine_Solr */
            $searchEngine = tx_mksearch_util_ServiceRegistry::getSearchEngine($index);
            $searchEngine->openIndex($index);
            $result = $searchEngine->search($fields, $options);
            $searchEngine->closeIndex();

            //der solr responce processor bearbeidet die results
            // es werden hits, facets, uws. erzeugt.
            tx_rnbase::load('tx_mksearch_util_SolrResponseProcessor');
            tx_mksearch_util_SolrResponseProcessor::processSolrResult(
                $result,
                $options,
                $configurations,
                $this->getConfId().'responseProcessor.'
            );
            $this->findCharBrowserData($result);
        } catch (Exception $e) {
            $lastUrl = $e instanceof tx_mksearch_service_engine_SolrException ? $e->getLastUrl() : '';
            //Da die Exception gefangen wird, würden die Entwickler keine Mail bekommen
            //also machen wir das manuell
            if ($addr = tx_rnbase_configurations::getExtensionCfgValue('rn_base', 'sendEmailOnException')) {
                tx_rnbase_util_Misc::sendErrorMail($addr, 'tx_mksearch_action_SearchSolr_searchSolr', $e);
            }
            tx_rnbase::load('tx_rnbase_util_Logger');
            if (tx_rnbase_util_Logger::isFatalEnabled()) {
                tx_rnbase_util_Logger::fatal(
                    'Solr search failed with Exception!',
                    'mksearch',
                    array('Exception' => $e->getMessage(), 'fields' => $fields, 'options' => $options, 'URL' => $lastUrl)
                );
            }
            if ($options['debug']) {
                tx_rnbase::load('tx_rnbase_util_Debug');
                tx_rnbase_util_Debug::debug(
                    array(
                        'Exception' => $e->getMessage(),
                        'fields' => $fields,
                        'options' => $options,
                        'URL' => $lastUrl,
                    ),
                    'class.tx_mksearch_action_SearchSolr.php Line: '.__LINE__
                );
            }
            if ($configurations->getBool($this->getConfId().'throwSolrSearchException')) {
                throw $e;
            }

            return false;
        }

        //alles gut gegangen. dann noch das ergebnis für den aktuellen
        //request in den cache
        $oCache->set($sCacheKey, $result);

        return $result;
    }

    /**
     * PageBrowser Integration für Solr
     * Solr liefert bei einer Suchanfrage immer die Gesamtzahl der Treffer mit.
     * Somit ist eine spezielle Suche für die Trefferzahl nicht notwendig.
     *
     * @param tx_rnbase_IParameters    $parameters
     * @param tx_rnbase_configurations $configurations
     *
     * @return tx_rnbase_util_PageBrowser
     */
    public function handlePageBrowser($parameters, $configurations, $confId, $viewdata, &$fields, &$options, $index)
    {
        // handle page browser only, if configured and the limit is greater than zero.
        $typoScriptPathPageBrowser = $confId.'hit.pagebrowser.';
        if ((isset($options['limit']) && $options['limit'] > 0)
            && is_array($conf = $configurations->get($typoScriptPathPageBrowser))
        ) {
            // PageBrowser initialisieren
            $pageBrowserId = $conf['pbid'] ? $conf['pbid'] : 'search'.$configurations->getPluginId();
            /* @var $pageBrowser tx_rnbase_util_PageBrowser */
            $pageBrowser = tx_rnbase::makeInstance('tx_rnbase_util_PageBrowser', $pageBrowserId);
            // wenn bereits ein limit gesetzt ist, dann nutzen wir dieses, nicht das des pagebrowsers
            $pageSize = isset($options['limit']) ? $options['limit'] : intval($conf['limit']);
            if ($conf['limitFromRequest']) {
                $limitParam = $conf['limitFromRequest.']['param'] ? $conf['limitFromRequest.']['param'] : 'limit';
                $limitQualifier = $conf['limitFromRequest.']['qualifier'] ? $conf['limitFromRequest.']['qualifier'] : '';
                $size = $parameters->getInt($limitParam, $limitQualifier);
                $pageSize = $size ? $size : $pageSize;

                // Wir schreiben das Limit in die Parameter, wenn diese noch nicht gesetzt wurden.
                // Das wird Beispielsweise für Limit-Links benötigt,
                // welche ihren Status anhand der Parameter erkennen.
                // Dabei müssen wir die bisherigen Parameter mergen,
                // da diese sonst überschrieben werden.

                //@todo limit über setRegsiter setzen um im TS darauf zugreifen
                //zu können. Dann werden hier auch nicht alle Parameter von
                //anderen Plugins überschrieben
                if (!$parameters->getInt($limitParam, $limitQualifier)) {
                    $params = tx_rnbase_parameters::getPostOrGetParameter($limitQualifier);
                    $params = $params ? $params : array();
                    $params = array_merge(array($limitParam => $pageSize), $params);
                    tx_rnbase_parameters::setGetParameter($params, $limitQualifier);
                    if ($limitQualifier == $parameters->getQualifier()) {
                        $parameters->offsetSet($limitParam, $pageSize);
                    }
                }
            }
            // Für den Pagebrowser müssen wir wissen wie viel gefunden werden wird.
            // Dafür reicht uns der Count, also setzen wir limit auf 0 zwecks performanz.
            $aOptionsForCountOnly = $options;
            $aOptionsForCountOnly['limit'] = 0;
            // die facetten benötigen wir nicht, für den count
            $aOptionsForCountOnly['facet'] = 'false';

            $listSize = 0;
            if ($result = $this->searchSolr($fields, $aOptionsForCountOnly, $configurations, $index)) {
                $listSize = $result['numFound'];
            }
            $pageBrowser->setState($parameters, $listSize, $pageSize);
            $state = $pageBrowser->getState();

            $pageBrowser->markPageNotFoundIfPointerOutOfRange($configurations, $typoScriptPathPageBrowser);

            $options = array_merge($options, $state);
            $viewdata->offsetSet('pagebrowser', $pageBrowser);

            return $pageBrowser;
        }
    }

    /**
     * Extracts the charbrowser data from the facets
     * and fills the viewdata for the tempate output.
     *
     * @param array $result
     */
    protected function findCharBrowserData(array &$result)
    {
        if (empty($result['facets'])) {
            return;
        }

        $facet = null;

        $facetField = $this->getConfigurations()->get($this->getConfId().'facetField') ?: 'first_letter_s';

        // check if there are a first_letter_s
        foreach ($result['facets'] as $key => $group) {
            if ($group->getField() === $facetField) {
                $facet = $group;
                unset($result['facets'][$key]);
                break;
            }
        }

        if (!$facet) {
            return;
        }

        $confId = $this->getConfId().'charbrowser.';
        $configurations = $this->getConfigurations();
        $viewData = $configurations->getViewData();
        $pointername = $configurations->get($confId.'cbid') ?: 'solr-cb';

        $list = array();
        // now build the pagerdate based on the faccets
        foreach ($facet->getItems() as $item) {
            $list[$item->getLabel()] = $item->getCount();
        }

        $pagerData = array(
            'list' => $list,
            'default' => empty($list) ? 0 : key($list),
            'pointername' => $pointername,
        );

        $firstChar = $viewData->offsetGet('charpointer');
        $firstChar = $firstChar ?: $configurations->getParameters()->offsetGet($pointername);

        $viewData->offsetSet('pagerData', $pagerData);
        $viewData->offsetSet('charpointer', $firstChar);
    }

    /**
     * Include the neccessary javascripts for the autocomplete feature.
     */
    protected function prepareAutocomplete()
    {
        $configurations = $this->getConfigurations();
        $autocompleteTsPath = $this->getConfId().$this->autocompleteConfId;

        if (!$configurations->get($autocompleteTsPath.'enable')) {
            return;
        }

        $configurationArray = $configurations->get($autocompleteTsPath);

        $javascriptsPath = tx_rnbase_util_Extensions::siteRelPath('mksearch').'res/js/';
        $jsScripts = array();

        if ($configurationArray['includeJquery']) {
            $jsScripts[] = 'jquery-1.6.2.min.js';
        }
        if ($configurationArray['includeJqueryUiCore']) {
            $jsScripts[] = 'jquery-ui-1.8.15.core.min.js';
        }
        if ($configurationArray['includeJqueryUiAutocomplete']) {
            $jsScripts[] = 'jquery-ui-1.8.15.autocomplete.min.js';
        }

        $pageRenderer = tx_rnbase_util_TYPO3::getPageRenderer();
        if (!empty($jsScripts)) {
            foreach ($jsScripts as $javaScriptFilename) {
                $pageRenderer->addJsLibrary($javaScriptFilename, $javascriptsPath.$javaScriptFilename);
            }
        }

        $link = tx_mksearch_util_SolrAutocomplete::getAutocompleteActionLinkByConfigurationsAndConfId(
            $configurations,
            $this->getConfId()
        );

        $autocompleteJS = tx_mksearch_util_SolrAutocomplete::getAutocompleteJavaScriptByConfigurationArrayAndLink(
            $configurations->get($this->getConfId().$this->autocompleteConfId),
            $link,
            false
        );

        $javaScriptSnippetSuffix =
            $configurations->get($this->getConfId().$this->autocompleteConfId.'javaScriptSnippetSuffix') ?
                $configurations->get($this->getConfId().$this->autocompleteConfId.'javaScriptSnippetSuffix') :
                $this->getConfigurations()->getPluginId();
        $pageRenderer->addJsFooterInlineCode('mksearch_autocomplete_'.$javaScriptSnippetSuffix, $autocompleteJS);
    }

    /**
     * Process a autocomplete call and return the json directly!
     */
    protected function processAutocomplete()
    {
        //shall we parse the content just as json
        if ($this->getParameters()->get('ajax')) {
            // if the frontend debug is enabled, so the json will be invalid.
            // so we has to disable the debug.
            $GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = 0;
            tx_rnbase::load('tx_rnbase_util_TYPO3');
            $tsfe = tx_rnbase_util_TYPO3::getTSFE();
            $tsfe->config['config']['debug'] = 0;
            $tsfe->TYPO3_CONF_VARS['FE']['debug'] = 0;

            $result = $this->getViewData()->offsetGet('result');
            $forbiddenResultItems = array('searchUrl' => null, 'searchTime' => null, 'response' => null);

            return json_encode(array_diff_key($result, $forbiddenResultItems));
        }
    }

    public function getTemplateName()
    {
        return 'searchsolr';
    }

    public function getViewClassName()
    {
        return 'tx_mksearch_view_SearchSolr';
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/action/class.tx_mksearch_action_SearchSolr.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/action/class.tx_mksearch_action_SearchSolr.php'];
}
