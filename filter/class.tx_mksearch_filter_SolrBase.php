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

use Sys25\RnBase\Frontend\Request\ParametersInterface;

/**
 * Der Filter liest seine Konfiguration passend zum Typ des Solr RequestHandlers. Der Typ
 * ist entweder "default" oder "dismax". Entsprechend baut sich auch die Typoscript-Konfiguration
 * auf:
 * searchsolr.filter.default.
 * searchsolr.filter.dismax.
 *
 * Es gibt Optionen, die für beide Requesttypen identisch sind. Als Beispiel seien die Templates
 * genannt. Um diese Optionen zentral über das Flexform konfigurieren zu können, werden die
 * betroffenen Werte zusätzlich noch über den Pfad
 *
 * searchsolr.filter._overwrite.
 *
 * ermittelt und wenn vorhanden bevorzugt verwendet.
 *
 * @author René Nitzsche
 */
class tx_mksearch_filter_SolrBase extends tx_mksearch_filter_BaseFilter
{
    protected $confIdExtended = '';

    /**
     * @var tx_mksearch_util_Filter
     */
    protected $filterUtility;

    protected function getConfIdOverwrite(): string
    {
        return $this->getConfId(false).'filter._overwrite.';
    }

    /**
     * Liefert die ConfId für den Filter
     * Die ID ist hier jeweils dismax oder default.
     * Wird im flexform angegeben!
     *
     * @return string
     */
    protected function getConfId($extended = true)
    {
        // $this->confId ist private, deswegen müssen wir deren methode aufrufen.
        $confId = parent::getConfId();
        if ($extended) {
            if (empty($this->confIdExtended)) {
                $this->confIdExtended = $this->getConfigurations()->get($confId.'filter.confid');
                $this->confIdExtended = 'filter.'.($this->confIdExtended ?: 'default').'.';
            }

            $confId .= $this->confIdExtended;
        }

        return $confId;
    }

    /**
     * Initialize filter.
     *
     * @param array $fields
     * @param array $options
     */
    public function init(&$fields, &$options): bool
    {
        $confId = $this->getConfId();
        $fields = $this->getConfigurations()->get($confId.'fields.');
        Sys25\RnBase\Search\SearchBase::setConfigOptions($options, $this->getConfigurations(), $confId.'options.');

        return $this->initFilter($fields, $options, $this->request);
    }

    /**
     * Liefert entweder die Konfiguration aus dem Flexform
     * oder aus dem Typoscript.
     *
     * @param string $confId
     *
     * @return Ambigous <multitype:, unknown>
     */
    protected function getConfValue($confId)
    {
        $configurations = $this->getConfigurations();
        // fallback: ursprünglich musste das configurationsobject als erster parameter übergeben werden.
        if (!is_scalar($confId)) {
            $configurations = $confId;
            $confId = func_get_arg(1);
        }

        // wert aus flexform holen (_override)
        $value = $configurations->get($this->getConfIdOverwrite().$confId);
        if (empty($value)) {
            // wert aus normalem ts holen.
            return $configurations->get($this->getConfId().$confId);
        }

        return $value;
    }

    /**
     * Filter for search form.
     *
     * @param array $fields
     * @param array $options
     *
     * @return bool Should subsequent query be executed at all?
     */
    protected function initFilter(&$fields, &$options, Sys25\RnBase\Frontend\Request\RequestInterface $request): bool
    {
        $configurations = $request->getConfigurations();
        $parameters = $request->getParameters();
        $confId = $this->getConfId();

        // Es muss ein Submit-Parameter im request liegen, damit der Filter greift
        if (!$parameters->offsetExists('submit') && !$this->getConfValue($configurations, 'force')) {
            return false;
        }

        // request handler setzen
        if ($requestHandler = $configurations->get($this->getConfId(false).'requestHandler')) {
            $options['qt'] = $requestHandler;
        }

        // das Limit setzen
        $this->handleLimit($options);

        // suchstring beachten
        $this->handleTerm($fields, $parameters, $configurations, $confId);

        // facetten setzen beachten
        $this->handleFacet($options, $parameters, $configurations, $confId);

        // Operatoren berücksichtigen
        $this->handleOperators($fields, $options, $parameters, $configurations, $confId);

        // sortierung beachten
        $this->handleSorting($options);

        // fq (filter query) beachten
        $this->handleFq($options, $parameters, $configurations, $confId);

        // umkreissuche prüfen
        $this->handleSpatial($fields, $options);

        $this->handleGrouping($options);

        $this->handleWhat($options);

        // Debug prüfen
        if ($configurations->get($this->getConfIdOverwrite().'options.debug')) {
            $options['debug'] = 1;
        }

        return true;
    }

    /**
     * Setzt die Anzahl der Treffer pro Seite.
     */
    protected function handleLimit(array &$options)
    {
        $options['limit'] = $this->getFilterUtility()->getPageLimit(
            $this->getParameters(),
            $this->getConfigurations(),
            $this->getConfId(),
            (int) $this->getConfValue('options.limit')
        );
    }

    /**
     * Fügt den Suchstring zu dem Filter hinzu.
     *
     * @param ParametersInterface                  $parameters
     * @param Sys25\RnBase\Configuration\Processor $configurations
     */
    protected function handleTerm(array &$fields, &$parameters, &$configurations, string $confId)
    {
        if ($termTemplate = ($fields['term'] ?? '')) {
            $termTemplate = $this->getFilterUtility()->parseTermTemplate(
                $termTemplate,
                $parameters,
                $configurations,
                $confId
            );
            $fields['term'] = $termTemplate;
        } // wenn der DisMaxRequestHandler genutzt muss der term leer sein, sonst wird nach *:* gesucht!
        elseif (!$configurations->get($confId.'useDisMax')) {
            $fields['term'] = '*:*';
        }
    }

    /**
     * @param ParametersInterface                  $parameters
     * @param Sys25\RnBase\Configuration\Processor $configurations
     * @param string                               $confId
     */
    protected function handleFacet(array &$options, &$parameters, &$configurations, $confId)
    {
        $fields = $this->getConfValue('options.facet.fields') ?? '';
        $fields = Sys25\RnBase\Utility\Strings::trimExplode(',', $fields, true);

        if (empty($fields)) {
            return;
        }

        // die felder für die facetierung setzen
        $options['facet.field'] = $fields;

        // facetten aktivieren, falls nicht explizit gesetzt.
        if (empty($options['facet']) || is_array($options['facet'])) {
            $options['facet'] = 'true';
        }

        // nur einträge, welche zu einem ergebnis führen würden.
        if (empty($options['facet.mincount'])) {
            // typoscript auf mincount checken.
            $facetMinCountConfig = $this->getConfValue('options.facet.mincount');
            $options['facet.mincount'] = 0 !== strlen((string) $facetMinCountConfig) ? (int) $facetMinCountConfig : '1';
        }

        $sortFacetsFromConfiguration = $this->getConfValue('options.facet.sort');
        if ($sortFacetsFromConfiguration && empty($options['facet.sort'])) {
            $options['facet.sort'] = $sortFacetsFromConfiguration;
        }

        // als fallback immer nach der anzahl sortieren, die alternative wäre index.
        if (empty($options['facet.sort'])) {
            $options['facet.sort'] = 'count';
        }
    }

    /**
     * Handelt operatoren wie AND OR.
     *
     * Die hauptaufgabe macht bereits fie userfunc in handleTerm.
     *
     * @see tx_mksearch_util_SearchBuilder::searchSolrOptions
     *
     * @param ParametersInterface                  $parameters
     * @param Sys25\RnBase\Configuration\Processor $configurations
     */
    protected function handleOperators(array &$fields, array &$options, &$parameters, &$configurations, string $confId)
    {
        // wenn der DisMaxRequestHandler genutzt wird, müssen wir ggf. den term und die options ändern.
        if ($configurations->get($confId.'useDisMax')) {
            tx_mksearch_util_SearchBuilder::handleMinShouldMatch($fields, $options, $parameters, $configurations, $confId);
            tx_mksearch_util_SearchBuilder::handleDismaxFuzzySearch($fields, $options, $parameters, $configurations, $confId);
        }
    }

    /**
     * Fügt eine Filter Query (Einschränkung) zu dem Filter hinzu.
     *
     * @param ParametersInterface                  $parameters
     * @param Sys25\RnBase\Configuration\Processor $configurations
     */
    protected function handleFq(array &$options, &$parameters, &$configurations, string $confId)
    {
        self::addFilterQuery($options, self::getFilterQueryForFeGroups());

        // respect Root Page
        $this->handleFqForSiteRootPage($options, $configurations, $confId);
        $this->handleFqForCharBrowser($options);

        // die erlaubten felder holen
        $allowedFqParams = $configurations->getExploded($confId.'allowedFqParams');
        // wenn keine konfiguriert sind, nehmen wir automatisch die facet fields
        if (empty($allowedFqParams) && !empty($options['facet.field'])) {
            $allowedFqParams = $options['facet.field'];
        }

        // parameter der filterquery prüfen
        // @see tx_mksearch_marker_Facet::prepareItem
        $fqParams = $parameters->get('fq');
        $fqParams = is_array($fqParams) ? $fqParams : ('' !== trim((string) $fqParams) && '0' !== trim((string) $fqParams) ? [trim((string) $fqParams)] : []);
        // @todo die if blöcke in eigene funktionen auslagern
        if ([] !== $fqParams) {
            // FQ field, for single queries
            // Das ist deprecated! Dadurch wäre nur ein festes Facet-Field möglich
            $sFqField = $configurations->get($confId.'fqField');
            foreach ($fqParams as $fqField => $fqValues) {
                $fieldOptions = [];
                $fqValues = is_array($fqValues) ? $fqValues : ('' !== trim((string) $fqValues) && '0' !== trim((string) $fqValues) ? [trim((string) $fqValues)] : []);
                if ([] === $fqValues) {
                    continue;
                }

                foreach ($fqValues as $fqName => $fqValue) {
                    $fqValue = trim((string) $fqValue);
                    if ($sFqField) {
                        // deprecated: sollte nicht mehr vorkommen
                        $fq = $sFqField.':"'.tx_mksearch_util_Misc::sanitizeFq($fqValue).'"';
                    } elseif (tx_mksearch_model_Facet::TYPE_QUERY === $fqField) {
                        // Query-Facet prüfen
                        $fq = $this->buildFq4QueryFacet($fqName, $configurations, $confId);
                    } else {
                        // check field facets
                        // field value konstellation prüfen
                        $fq = $this->parseFieldAndValue($fqValue, $allowedFqParams);
                        if (('' === $fq || '0' === $fq) && ('' !== $fqField && '0' !== $fqField && 0 !== $fqField) && in_array($fqField, $allowedFqParams)) {
                            $fq = tx_mksearch_util_Misc::sanitizeFq($fqField);
                            $fq .= ':"'.tx_mksearch_util_Misc::sanitizeFq($fqValue).'"';
                        }
                    }

                    self::addFilterQuery($fieldOptions, $fq);
                }

                if (!empty($fieldOptions['fq'])) {
                    $fieldQuery = $fieldOptions['fq'];
                    if (is_array($fieldQuery)) {
                        $fqOperator = $configurations->get($confId.'filterQuery.'.$fqField.'.operator');
                        $fqOperator = 'OR' == $fqOperator ? 'OR' : 'AND';
                        $fieldQuery = implode(' '.$fqOperator.' ', $fieldQuery);
                    }

                    self::addFilterQuery($options, $this->handleFqTags($fieldQuery));
                }
            }
        }

        $sAddFq = trim((string) $parameters->get('addfq'));
        if ('' !== $sAddFq && '0' !== $sAddFq) {
            // field value konstelation prüfen
            $sAddFq = $this->parseFieldAndValue($sAddFq, $allowedFqParams);
            self::addFilterQuery($options, $this->handleFqTags($sAddFq));
        }

        $sRemoveFq = trim((string) $parameters->get('remfq'));
        if ('' !== $sRemoveFq && '0' !== $sRemoveFq) {
            $aFQ = isset($options['fq']) ? (is_array($options['fq']) ? $options['fq'] : [$options['fq']]) : [];
            // hier steckt nur der feldname drin
            foreach ($aFQ as $iKey => $sFq) {
                [$sfield] = explode(':', (string) $sFq);
                // wir löschen das feld
                if (in_array($sRemoveFq, $allowedFqParams) && $sRemoveFq === $sfield) {
                    unset($aFQ[$iKey]);
                }
            }

            $options['fq'] = $aFQ;
        }
    }

    /**
     * Handles the filter query tags and prefix the tag to the query.
     *
     * @param string $query
     *
     * @return string
     */
    protected function handleFqTags($query)
    {
        // strip the field from the query
        $field = substr($query, 0, strpos($query, ':'));
        // get the tag config
        $tag = $this->getConfigurations()->get(
            $this->getConfId().'filterQuery.'.$field.'.tag'
        );
        // addd the tag to the query
        if ($tag) {
            $tag = '{!tag="'.$tag.'"}';
            $query = $tag.$query;
        }

        return $query;
    }

    /**
     * Baut den fq-Parameter für eine Query-Facette zusammen. Die Filter-Anweisung dafür muss im Typoscript
     * konfiguriert sein. Sie sollte identisch mit der Anweisung in der solrconfig.xml sein.
     *
     * @param string $queryFacetAlias
     *
     * @return string
     */
    private function buildFq4QueryFacet(int|string $queryFacetAlias, Sys25\RnBase\Configuration\Processor $configurations, string $confId)
    {
        return $configurations->get($confId.'facet.queries.'.$queryFacetAlias);
    }

    public static function getFilterQueryForFeGroups(): string
    {
        // wenigstens ein teil der query muss matchen. bei prüfen auf
        // nicht vorhandensein muss also noch auf ein feld geprüft werden
        // das garantiert existiert und damit ein match generiert.
        $filterQuery = '(-fe_group_mi:[* TO *] AND id:[* TO *])';
        if (Sys25\RnBase\Utility\TYPO3::getFEUserUID()) {
            $filterQueriesByFeGroup = ['fe_group_mi:0', 'fe_group_mi:"-2"'];
            $feUser = Sys25\RnBase\Utility\TYPO3::getFEUser();
            if (is_array($feUser->groupData['uid'])) {
                foreach ($feUser->groupData['uid'] as $feGroup) {
                    $filterQueriesByFeGroup[] = 'fe_group_mi:'.$feGroup;
                }
            }

            $filterQuery .= ' OR (('.implode(' OR ', $filterQueriesByFeGroup).') AND -fe_group_mi:"-1")';
        } else {
            $filterQuery .= ' OR fe_group_mi:0 OR fe_group_mi:"-1"';
        }

        return $filterQuery;
    }

    /**
     * Fügt die SiteRootPage zur Filter Query hinzu.
     *
     * @param Sys25\RnBase\Configuration\Processor $configurations
     */
    public function handleFqForSiteRootPage(
        array &$options,
        &$configurations,
        string $confId,
    ): void {
        if ($configurations->getBool($confId.'respectSiteRootPage')) {
            $siteRootPage = tx_mksearch_util_Indexer::getInstance()->getSiteRootPage(
                $GLOBALS['TSFE']->id
            );
            if (($options['siteRootPage'] ?? null) || !is_array($siteRootPage)) {
                $siteRootPage = $options['siteRootPage'];
            }

            if (is_array($siteRootPage) && [] !== $siteRootPage) {
                self::addFilterQuery(
                    $options,
                    // Alle Dokumente mit der passenden siteRootPage oder ohne dieses Feld.
                    // Wenigstens ein Teil der query muss matchen beim prüfen auf
                    // nicht vorhandensein eines Feldes. Also noch auf ein Feld prüfen,
                    // das garantiert existiert und damit ein match generiert.
                    '(-siteRootPage:[* TO *] AND id:[* TO *]) OR siteRootPage:'.$siteRootPage['uid']
                );
            }
        }
    }

    /**
     * Adds filter for the charbrowser.
     */
    public function handleFqForCharBrowser(
        array &$options,
    ): void {
        $confId = $this->getConfId(false).'charbrowser.';
        $configurations = $this->getConfigurations();

        $pointername = $configurations->get($confId.'cbid') ?: 'solr-cb';

        $firstChar = $this->getParameters()->get($pointername);

        if (empty($firstChar)) {
            return;
        }

        // crop pointer value
        $firstChar = substr(
            strtoupper((string) $firstChar),
            0,
            '0' == $firstChar[0] ? 3 : 1
        );

        // store firstchar
        $this->request->getViewContext()->offsetSet('charpointer', $firstChar);

        $facetField = $configurations->get($confId.'facetField') ?: 'first_letter_s';
        self::addFilterQuery(
            $options,
            $this->handleFqTags(
                $facetField.':"'.$firstChar.'"'
            )
        );
    }

    /**
     * Fügt einen Parameter zu der FilterQuery hinzu.
     *
     * @TODO: kann die fq nicht immer ein array sein!? dann könnten wir uns das sparen!
     *
     * @param string $sFQ
     */
    public static function addFilterQuery(array &$options, $sFQ): void
    {
        if (empty($sFQ)) {
            return;
        }

        // vorhandene fq berücksichtigen
        if (isset($options['fq'])) {
            // den neuen wert anhängen
            if (is_array($options['fq'])) {
                $options['fq'][] = $sFQ;
            } // aus fq ein array machen und den neuen wert anhängen
            else {
                $options['fq'] = [$options['fq'], $sFQ];
            }
        } // fq schreiben
        else {
            $options['fq'] = $sFQ;
        }
    }

    /**
     * Prüft den fq parameter auf richtigkeit.
     *
     * @param array $allowedFqParams
     */
    private function parseFieldAndValue(string $sFq, $allowedFqParams): string
    {
        return $this->getFilterUtility()->parseFqFieldAndValue($sFq, $allowedFqParams);
    }

    /**
     * Liefert die Koordinaten,
     * anhand der eine Umkreissuche durchgeführt werden soll.
     *
     * Dies muss Kommagetrennt ohne Leerzeichen geliefert werden:
     * "lat,lon" bzw. "50.83,12.93"
     *
     * Diese Methode sollte die Kindklasse überschreiben,
     * um Koordinaten zu liefern.
     */
    protected function getSpatialPoint(): string|false
    {
        return false;
    }

    /**
     * Liefert die Distanz in KM für die Umkreissuche.
     * Wird 0 geliefert, so wird der default value aus dem TS gezogen.
     *
     * @return int
     */
    protected function getSpatialDistance()
    {
        return $this->getParameters()->getInt('distance');
    }

    /**
     * Prüft Filter for eine Umkreissuche.
     * Damit dies Funktioniert, muss in den Solr-Dokumenten ein Feld
     * mit den Koordionaten existieren. Siehe schema.xml dynamicField *_latlon.
     *
     * Dieses Feld muss konfiguriert werden,
     * da darin die Umkreissuche stattfindet.
     *
     * @see EXT:mksearch/static/static_extension_template/setup.txt (lib.mksearch.defaultsolrfilter.spatial)
     * für alle Konfigurationsoptionen.
     *
     * @param array &$fields
     */
    protected function handleSpatial(&$fields, array &$options)
    {
        // Die Koordinaten, anhand der gesucht werden soll.
        $point = $this->getSpatialPoint();
        if ('' === $point || '0' === $point || false === $point) {
            return;
        }

        $confId = $this->getConfId().'spatial.';
        $configurations = $this->getConfigurations();

        // wir prüfen das feld wo die koordinaten im solr liegen
        $coordField = $configurations->get($confId.'sfield');
        if (empty($coordField)) {
            return;
        }

        $options['sfield'] = $coordField;

        // die distanz bzw. den umkreis für die umkreissuche ermitteln
        $options['d'] = (int) $this->getSpatialDistance();
        if (empty($options['d'])) {
            $options['d'] = $configurations->getInt($confId.'default.distance');
        }

        $options['pt'] = $point;

        // in die filter query muss nun noch der funktionsaufruf,
        // um die ergebnisse zu filtern.
        // @TODO: methode konfigurierbar machen ({!bbox}, {!geofilt}, ...)
        self::addFilterQuery($options, '{!geofilt}');

        if ($configurations->get($confId.'returnDistanceInResults')) {
            if (empty($options['fl'])) {
                $options['fl'] = '*,distance:geodist()';
            } else {
                $options['fl'] .= ',distance:geodist()';
            }
        }

        if ($configurations->get($confId.'sortByLowestDistance')) {
            $options['sort'] = 'geodist() asc';
        }

        if ($configurations->get($confId.'sortByHighestDistance')) {
            $options['sort'] = 'geodist() desc';
        }
    }

    /**
     * Fügt die Sortierung zu dem Filter hinzu.
     *
     * @TODO: das klappt zurzeit nur bei einfacher sortierung!
     */
    protected function handleSorting(array &$options)
    {
        if ($sortString = $this->getFilterUtility()->getSortString($options, $this->getParameters())) {
            $options['sort'] = $sortString;
        }
    }

    /**
     * @param string                                  $template  HTML template
     * @param Sys25\RnBase\Frontend\Marker\FormatUtil $formatter
     * @param string                                  $confId
     * @param string                                  $marker
     *
     * @return string
     */
    public function parseTemplate($template, &$formatter, $confId, $marker = 'FILTER')
    {
        $markArray = [];
        $subpartArray = [];
        $wrappedSubpartArray = [];
        $this->parseSearchForm(
            $template,
            $markArray,
            $subpartArray,
            $wrappedSubpartArray,
            $formatter,
            $confId,
            $marker
        );

        $this->parseSortFields(
            $template,
            $markArray,
            $subpartArray,
            $wrappedSubpartArray,
            $formatter,
            $confId,
            $marker
        );
        // Aufpassen: In $confId steht "searchsolr.hit.filter."
        // in $this->getConfId() steht "searchsolr.filter.default." (bzw. dismax)

        return Sys25\RnBase\Frontend\Marker\Templates::substituteMarkerArrayCached(
            $template,
            $markArray,
            $subpartArray,
            $wrappedSubpartArray
        );
    }

    /**
     * @param string $formTemplate
     */
    protected function renderSearchForm($formTemplate, Sys25\RnBase\Frontend\Marker\FormatUtil $formatter, string $confId, $templateConfId)
    {
        $configurations = $formatter->getConfigurations();
        $viewData = $this->request->getViewContext();
        if ($formTemplate) {
            $link = $configurations->createLink();
            $link->initByTS($configurations, $confId.'template.links.action.', []);
            // Prepare some form data
            $paramArray = $this->getParameters()->getArrayCopy();
            $formData = $this->getParameters()->get('submit') ? $paramArray : $this->getFormData();
            $formData['action'] = $link->makeUrl(false);
            $formData['searchterm'] = htmlspecialchars((string) $this->getParameters()->get('term'), ENT_QUOTES);
            $formData['listsize'] = $viewData->offsetExists('pagebrowser') ? $viewData->offsetGet('pagebrowser')->getListSize() : 0;
            $formData['hiddenfields'] = Sys25\RnBase\Backend\Form\FormUtil::getHiddenFieldsForUrlParams($formData['action']);

            $combinations = ['none', 'free', 'or', 'and', 'exact'];
            $currentCombination = $this->getParameters()->get('combination');
            $currentCombination = $currentCombination ?: 'none';
            foreach ($combinations as $combination) {
                // wenn anders benötigt, via ts ändern werden
                $formData['combination_'.$combination] = ($combination == $currentCombination) ? ' checked="checked"' : '';
            }

            $options = ['fuzzy'];
            $currentOptions = $this->getParameters()->get('options') ?? [];
            foreach ($options as $option) {
                // wenn anders benötigt, via ts ändern werden
                $formData['option_'.$option] = empty($currentOptions[$option]) ? '' : ' checked="checked"';
            }

            $availableModes = $this->getModeValuesAvailable();
            if ($currentOptions['mode'] ?? false) {
                foreach ($availableModes as $availableMode) {
                    $formData['mode_'.$availableMode.'_selected'] =
                    $currentOptions['mode'] == $availableMode ? 'checked=checked' : '';
                }
            } else {
                // Default
                $formData['mode_standard_selected'] = 'checked=checked';
            }

            $templateMarker = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_marker_General');
            // During marker template parsing stdWrap will be performed on every array member. The value passed to
            // stdWrap can never be an array so we remove every array as it can't be parsed anyways. 'fq' will be an
            // array for example.
            foreach ($formData as $key => $formDatum) {
                if (is_array($formDatum)) {
                    unset($formData[$key]);
                }
            }

            $formTemplate = $templateMarker->parseTemplate($formTemplate, $formData, $formatter, $confId.'form.', 'FORM');

            // Formularfelder
            $formTemplate = $this->getFilterUtility()->parseCustomFilters($formTemplate, $configurations, $confId);
        }

        return $formTemplate;
    }

    /**
     * protected
     * Treat search form.
     *
     * @param string $template            HTML template
     * @param array  $subpartArray
     * @param array  $wrappedSubpartArray
     * @param string $confId
     * @param string $marker
     *
     * @return string
     */
    public function parseSearchForm($template, array &$markArray, &$subpartArray, &$wrappedSubpartArray, Sys25\RnBase\Frontend\Marker\FormatUtil &$formatter, $confId, $marker = 'FILTER')
    {
        $markerName = 'SEARCH_FORM';
        if (!Sys25\RnBase\Frontend\Marker\BaseMarker::containsMarker($template, $markerName)) {
            return $template;
        }

        $confId = $this->getConfId();

        $configurations = $formatter->getConfigurations();
        $formTemplate = $this->getConfValue($configurations, 'template.file');
        $subpart = $this->getConfValue($configurations, 'template.subpart');
        $formTemplate = Sys25\RnBase\Frontend\Marker\Templates::getSubpartFromFile($formTemplate, $subpart);

        $formTemplate = $this->renderSearchForm($formTemplate, $formatter, $confId, 'template.');
        $markArray['###'.$markerName.'###'] = $formTemplate;
        // Gibt es noch weitere Templates
        $templateKeys = $configurations->getKeyNames($confId.'templates.');
        if ($templateKeys) {
            foreach ($templateKeys as $templateKey) {
                $templateConfId = 'templates.'.$templateKey.'.';
                $markerName = strtoupper($configurations->get($confId.$templateConfId.'name'));
                if (Sys25\RnBase\Frontend\Marker\BaseMarker::containsMarker($template, $markerName)) {
                    $templateFile = $this->getConfValue($configurations, $templateConfId.'file');
                    $subpart = $this->getConfValue($configurations, $templateConfId.'subpart');
                    $formTemplate = Sys25\RnBase\Frontend\Marker\Templates::getSubpartFromFile($templateFile, $subpart);
                    $formTemplate = $this->renderSearchForm($formTemplate, $formatter, $confId, $templateConfId);
                    $markArray['###'.$markerName.'###'] = $formTemplate;
                }
            }
        }

        return $template;
    }

    /**
     * Returns all values possible for form field mksearch[options][mode].
     * Makes it possible to easily add more modes in other filters/forms.
     */
    protected function getModeValuesAvailable(): array
    {
        $availableModes = Sys25\RnBase\Utility\Strings::trimExplode(
            ',',
            $this->getConfValue($this->getConfigurations(), 'availableModes') ?? ''
        );

        return (array) $availableModes;
    }

    /**
     * die methode ist nur noch da für abwärtskompatiblität.
     *
     * @param string                                  $template     HTML template
     * @param array                                   $markArray
     * @param array                                   $subpartArray
     * @param Sys25\RnBase\Frontend\Marker\FormatUtil $formatter
     * @param string                                  $confId
     * @param string                                  $marker
     */
    public function parseSortFields($template, &$markArray, &$subpartArray, array &$wrappedSubpartArray, &$formatter, $confId, $marker = 'FILTER'): void
    {
        $this->getFilterUtility()->parseSortFields(
            $template,
            $markArray,
            $subpartArray,
            $wrappedSubpartArray,
            $formatter,
            $this->getConfId(),
            $marker
        );
    }

    protected function getFormData(): array
    {
        return [];
    }

    /**
     * @return tx_mksearch_util_Filter
     */
    protected function getFilterUtility()
    {
        if (!$this->filterUtility) {
            $this->filterUtility = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_util_Filter');
        }

        return $this->filterUtility;
    }

    protected function handleGrouping(array &$options)
    {
        if ($this->getConfValue('options.group.enable')) {
            $options['group'] = 'true';
            $options['group.field'] = $this->getConfValue('options.group.field');

            if ($this->getConfValue('options.group.useNumberOfGroupsAsSearchResultCount')) {
                $options['group.ngroups'] = 'true';
                $options['group.truncate'] = 'true';
            }
        } elseif (is_array($options['group'] ?? null)) {
            // remove group config from options array so useless parameters
            // are not taken into the request. this can happen if grouping not enabled
            // but options.group.useNumberOfGroupsAsSearchResultCount is set.
            unset($options['group']);
        }
    }

    protected function handleWhat(array &$options)
    {
        $fieldlist = $this->getConfValue('options.what');
        if (!empty($fieldlist)) {
            $options['fl'] = $fieldlist;
        }
    }
}
