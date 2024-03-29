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

use Sys25\RnBase\Frontend\Request\ParametersInterface;

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

    public function handleRequest(\Sys25\RnBase\Frontend\Request\RequestInterface $request)
    {
        $configurations = $request->getConfigurations();
        $parameters = $request->getParameters();
        $viewData = $request->getViewContext();

        $confId = $this->getConfId();

        $this->handleSoftLink($request);

        // Filter erstellen (z.B. Formular parsen)
        $filter = $this->createFilter($request, $confId);

        // shall we prepare the javascript for the autocomplete feature
        $this->prepareAutocomplete($request);

        // manchmal will man nur ein Suchformular auf jeder Seite im Header einbinden
        // dieses soll dann aber nur auf eine Ergebnisseite verweisen ohne
        // selbst zu suchen
        if ($configurations->get($confId.'nosearch')) {
            return null;
        }

        $fields = [];
        $options = [];

        if ($parameters->get('debug')) {
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

            // get the index we shall search in
            $index = $this->getSearchIndex($request);
            $pageBrowser = $this->handlePageBrowser($request, $confId, $fields, $options, $index);

            if ($result = $this->searchSolr($fields, $options, $request, $index)) {
                // Jetzt noch die echte Listengröße im PageBrowser setzen
                if (is_object($pageBrowser)) {
                    $listSize = $result['numFound'];
                    $pageBrowser->setState($parameters, $listSize, $pageBrowser->getPageSize());
                }
            } else {
                $result = ['items' => []];
            }
        } // auch einen debug ausgeben, wenn nichts gesucht wird
        elseif ($options['debug'] ?? false) {
            \Sys25\RnBase\Utility\Debug::debug(
                [
                    'Filter returns false, no search done.',
                    $fields, $options,
                ],
                'class.tx_mksearch_action_SearchSolr.php Line: '.__LINE__
            );
        }
        $viewData->offsetSet('result', $result);

        return $this->processAutocomplete($request);
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
        array $options = []
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
     * @param \Sys25\RnBase\Configuration\Processor         $configurations
     * @param tx_mksearch_model_internal_Index $index
     *
     * @return array|false
     */
    protected function searchSolr(&$fields, &$options, \Sys25\RnBase\Frontend\Request\RequestInterface $request, $index)
    {
        $configurations = $request->getConfigurations();
        // erstmal den cache fragen. Das ist vor allem interessant wenn
        // das plugin mehrfach auf einer Seite eingebunden wird. Dadurch
        // muss die Solr Suche nur einmal ausgeführt werden
        $oCache = \Sys25\RnBase\Cache\CacheManager::getCache('mksearch');
        $sCacheKey = $this->generateCacheKey($fields, $options);
        if ($result = $oCache->get($sCacheKey)) {
            return $result;
        }
        // else nix im cache also Solr suchen lassen

        try {
            // in unserem fall sollte es der solr service sein!
            /* @var $searchEngine tx_mksearch_service_engine_Solr */
            $searchEngine = tx_mksearch_util_ServiceRegistry::getSearchEngine($index);
            $searchEngine->openIndex($index);
            $result = $searchEngine->search($fields, $options);
            $searchEngine->closeIndex();

            // der solr responce processor bearbeidet die results
            // es werden hits, facets, uws. erzeugt.
            tx_mksearch_util_SolrResponseProcessor::processSolrResult(
                $result,
                $options,
                $configurations,
                $this->getConfId().'responseProcessor.'
            );
            $this->findCharBrowserData($result, $request);
        } catch (Exception $e) {
            $lastUrl = $e instanceof tx_mksearch_service_engine_SolrException ? $e->getLastUrl() : '';
            // Da die Exception gefangen wird, würden die Entwickler keine Mail bekommen
            // also machen wir das manuell
            if ($addr = \Sys25\RnBase\Configuration\Processor::getExtensionCfgValue('rn_base', 'sendEmailOnException')) {
                \Sys25\RnBase\Utility\Misc::sendErrorMail($addr, 'tx_mksearch_action_SearchSolr_searchSolr', $e);
            }
            if (\Sys25\RnBase\Utility\Logger::isFatalEnabled()) {
                \Sys25\RnBase\Utility\Logger::fatal(
                    'Solr search failed with Exception!',
                    'mksearch',
                    ['Exception' => $e->getMessage(), 'fields' => $fields, 'options' => $options, 'URL' => $lastUrl]
                );
            }
            if ($options['debug'] ?? false) {
                \Sys25\RnBase\Utility\Debug::debug(
                    [
                        'Exception' => $e->getMessage(),
                        'fields' => $fields,
                        'options' => $options,
                        'URL' => $lastUrl,
                    ],
                    'class.tx_mksearch_action_SearchSolr.php Line: '.__LINE__
                );
            }
            if ($configurations->getBool($this->getConfId().'throwSolrSearchException')) {
                throw $e;
            }

            return false;
        }

        // alles gut gegangen. dann noch das ergebnis für den aktuellen
        // request in den cache
        $oCache->set($sCacheKey, $result);

        return $result;
    }

    /**
     * PageBrowser Integration für Solr
     * Solr liefert bei einer Suchanfrage immer die Gesamtzahl der Treffer mit.
     * Somit ist eine spezielle Suche für die Trefferzahl nicht notwendig.
     *
     * @param ParametersInterface      $parameters
     * @param \Sys25\RnBase\Configuration\Processor $configurations
     *
     * @return \Sys25\RnBase\Utility\PageBrowser|null
     */
    public function handlePageBrowser(\Sys25\RnBase\Frontend\Request\RequestInterface $request, $confId, &$fields, &$options, $index)
    {
        $configurations = $request->getConfigurations();
        $parameters = $request->getParameters();
        $viewdata = $request->getViewContext();

        // handle page browser only, if configured and the limit is greater than zero.
        $typoScriptPathPageBrowser = $confId.'hit.pagebrowser.';
        if ((isset($options['limit']) && $options['limit'] > 0)
            && is_array($conf = $configurations->get($typoScriptPathPageBrowser))
        ) {
            // PageBrowser initialisieren
            $pageBrowserId = $conf['pbid'] ?? 'search'.$configurations->getPluginId();
            /* @var $pageBrowser \Sys25\RnBase\Utility\PageBrowser */
            $pageBrowser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Utility\PageBrowser::class, $pageBrowserId);
            // wenn bereits ein limit gesetzt ist, dann nutzen wir dieses, nicht das des pagebrowsers
            $pageSize = isset($options['limit']) ? $options['limit'] : intval($conf['limit']);
            if ($conf['limitFromRequest'] ?? false) {
                $limitParam = $conf['limitFromRequest.']['param'] ?? 'limit';
                $limitQualifier = $conf['limitFromRequest.']['qualifier'] ?? '';
                $size = $parameters->getInt($limitParam, $limitQualifier);
                $pageSize = $size ? $size : $pageSize;

                // Wir schreiben das Limit in die Parameter, wenn diese noch nicht gesetzt wurden.
                // Das wird Beispielsweise für Limit-Links benötigt,
                // welche ihren Status anhand der Parameter erkennen.
                // Dabei müssen wir die bisherigen Parameter mergen,
                // da diese sonst überschrieben werden.

                // @todo limit über setRegsiter setzen um im TS darauf zugreifen
                // zu können. Dann werden hier auch nicht alle Parameter von
                // anderen Plugins überschrieben
                if (!$parameters->getInt($limitParam, $limitQualifier)) {
                    $params = \Sys25\RnBase\Frontend\Request\Parameters::getPostOrGetParameter($limitQualifier);
                    $params = $params ? $params : [];
                    $params = array_merge([$limitParam => $pageSize], $params);
                    \Sys25\RnBase\Frontend\Request\Parameters::setGetParameter($params, $limitQualifier);
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
            if ($result = $this->searchSolr($fields, $aOptionsForCountOnly, $request, $index)) {
                $listSize = $result['numFound'];
            }
            $pageBrowser->setState($parameters, $listSize, $pageSize);
            $state = $pageBrowser->getState();

            $pageBrowser->markPageNotFoundIfPointerOutOfRange($configurations, $typoScriptPathPageBrowser);

            $options = array_merge($options, $state);
            $viewdata->offsetSet('pagebrowser', $pageBrowser);

            return $pageBrowser;
        }

        return null;
    }

    /**
     * Extracts the charbrowser data from the facets
     * and fills the viewdata for the tempate output.
     *
     * @param array $result
     */
    protected function findCharBrowserData(array &$result, \Sys25\RnBase\Frontend\Request\RequestInterface $request)
    {
        if (empty($result['facets'] ?? null)) {
            return;
        }

        $facet = null;

        $facetField = $request->getConfigurations()->get($this->getConfId().'facetField') ?: 'first_letter_s';

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
        $configurations = $request->getConfigurations();
        $viewData = $request->getViewContext();
        $pointername = $configurations->get($confId.'cbid') ?: 'solr-cb';

        $list = [];
        // now build the pagerdate based on the faccets
        foreach ($facet->getItems() as $item) {
            $list[$item->getLabel()] = $item->getCount();
        }

        $pagerData = [
            'list' => $list,
            'default' => empty($list) ? 0 : key($list),
            'pointername' => $pointername,
        ];

        $firstChar = $viewData->offsetGet('charpointer');
        $firstChar = $firstChar ?: $configurations->getParameters()->offsetGet($pointername);

        $viewData->offsetSet('pagerData', $pagerData);
        $viewData->offsetSet('charpointer', $firstChar);
    }

    /**
     * Include the neccessary javascripts for the autocomplete feature.
     */
    protected function prepareAutocomplete(\Sys25\RnBase\Frontend\Request\RequestInterface $request)
    {
        $configurations = $request->getConfigurations();
        $autocompleteTsPath = $this->getConfId().$this->autocompleteConfId;

        if (!$configurations->get($autocompleteTsPath.'enable')) {
            return;
        }

        $configurationArray = $configurations->get($autocompleteTsPath);

        $jsScripts = [];

        if ($configurationArray['includeJquery']) {
            $jsScripts[] = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(
                'EXT:mksearch/Resources/Public/JavaScript/jquery-1.6.2.min.js'
            );
        }
        if ($configurationArray['includeJqueryUiCore']) {
            $jsScripts[] = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(
                'EXT:mksearch/Resources/Public/JavaScript/jquery-ui-1.8.15.core.min.js'
            );
        }
        if ($configurationArray['includeJqueryUiAutocomplete']) {
            $jsScripts[] = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(
                'EXT:mksearch/Resources/Public/JavaScript/jquery-ui-1.8.15.autocomplete.min.js'
            );
        }

        $pageRenderer = \Sys25\RnBase\Utility\TYPO3::getPageRenderer();
        if (!empty($jsScripts)) {
            foreach ($jsScripts as $javaScriptFilename) {
                $pageRenderer->addJsLibrary($javaScriptFilename, $javaScriptFilename);
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
                $request->getConfigurations()->getPluginId();
        $pageRenderer->addJsFooterInlineCode('mksearch_autocomplete_'.$javaScriptSnippetSuffix, $autocompleteJS);
    }

    /**
     * Process a autocomplete call and return the json directly!
     */
    protected function processAutocomplete(\Sys25\RnBase\Frontend\Request\RequestInterface $request)
    {
        // shall we parse the content just as json
        if ($request->getParameters()->get('ajax')) {
            // if the frontend debug is enabled, so the json will be invalid.
            // so we has to disable the debug.
            $GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = 0;
            $tsfe = \Sys25\RnBase\Utility\TYPO3::getTSFE();
            $tsfe->config['config']['debug'] = 0;

            $result = $request->getViewContext()->offsetGet('result');
            $forbiddenResultItems = ['searchUrl' => null, 'searchTime' => null, 'response' => null];

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
