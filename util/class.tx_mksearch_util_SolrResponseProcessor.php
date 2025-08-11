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
 * Der FacetBuilder erstellt aus den Rohdaten der Facets passende Objekte für das Rendering.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_util_SolrResponseProcessor
{
    /**
     * Enter description here ...
     *
     * @param array                                $options
     * @param Sys25\RnBase\Configuration\Processor $configurations
     */
    public static function processSolrResult(array &$result, $options, &$configurations, string $confId): bool
    {
        static $instance = null;

        if (!array_key_exists('response', $result)
        || !($result['response'] instanceof Apache_Solr_Response)
        ) {
            return false;
        }

        if (!$instance) {
            $processorClass = $configurations->get($confId.'class');
            $processorClass = $processorClass ?: static::class;
            $instance = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($processorClass, $configurations, $confId);
        }

        $response = &$result['response'];
        $result = $instance->processSolrResponse($response, $options, $result);

        return true;
    }

    /**
     * @param Sys25\RnBase\Configuration\Processor $configurations
     */
    public function __construct(
        /**
         * Konfigurations Objekt.
         */
        private $configurations,
        private $confId,
    ) {
    }

    /**
     * @return Sys25\RnBase\Configuration\Processor
     */
    protected function getConfigurations()
    {
        return $this->configurations;
    }

    /**
     * @return string
     */
    protected function getConfId()
    {
        return $this->confId;
    }

    /**
     * Enter description here ...
     */
    public function processSolrResponse(Apache_Solr_Response &$response, array $options, array $result = []): array
    {
        $result['items'] = $this->processHits($response, $options, empty($result['items']) ? [] : $result['items']);
        $result['facets'] = $this->processFacets($response);
        $result['suggestions'] = $this->processSuggestions($response);

        return $result;
    }

    /**
     * @TODO: sollte es hierfür nicht auch eine klasse wie tx_mksearch_util_HitBuilder geben?
     */
    public function processHits(Apache_Solr_Response &$response, array $options, array $hits = []): array
    {
        $confId = $this->getConfId().'hit.';

        // highlighting einfügen
        $highlights = $this->getHighlighting($response);

        // hier wird nur highlighting gesetzt
        // wenn keins existiert brauchen wir nichts machen
        if ([] === $highlights) {
            return $hits;
        }

        foreach ($hits as &$hit) {
            // highlighting hinzufügen für alle Felder
            if (!empty($highlights[$hit->getProperty('id')])) {
                foreach ($highlights[$hit->getProperty('id')] as $docField => $highlightValue) {
                    // Solr liefert die Highlightings gesondert weshalb wir diese in das
                    // eigentliche Dokument bekommen müssen. Dafür gibts es 2 Möglichkeiten:
                    // 1. wenn overrideWithHl auf true gesetzt ist werden die jeweiligen Inhaltsfelder
                    // mit den korrespondierenden Highlighting Snippets überschrieben. Dabei muss man auf
                    // hl.fragsize achten da die Snippets nur so lang sind wie in hl.fragsize angegeben
                    // 2. ist overrideWithHl nicht gesetzt dann werden die Highlighting Snippets
                    // in ein eigenes Feld nach folgendem Schema ins Dokument geschrieben: $Feldname_hl
                    // dabei wäre es dann möglich die Felder flexibel über TS überschrieben zu lassen
                    // indem bspw. ein TS wie content.override.field = content_hl angegeben wird ;)
                    $overrideWithHl = $this->getConfigurations()->get($confId.'overrideWithHl');
                    $overrideWithHl = $overrideWithHl ?: isset($options['overrideWithHl']) && $options['overrideWithHl'];
                    $highlightField = ($overrideWithHl) ? $docField : $docField.'_hl';

                    if ($this->getConfigurations()->getBool($confId.'hellip')) {
                        $highlightValue = $this->handleHellip(
                            $hit->getProperty($docField),
                            $highlightValue,
                            $this->getConfigurations()->get($confId.'hellip.')
                        );
                    }

                    $hit->setProperty($highlightField, $highlightValue);
                }
            }
        }

        return $hits;
    }

    /**
     * checks the original and the highlighted value.
     * if the highlighted value is an excerpt, so a horizontal ellipsises
     * will be wrapped around the highlighted value.
     *
     * @param string $originalValue
     * @param string $highlightedValue
     *
     * @return string
     */
    protected function handleHellip(
        $originalValue,
        $highlightedValue,
        array $options = [],
    ) {
        // cleanup the source and the highlightd
        $cleanOriginalValue = tx_mksearch_util_Misc::html2plain(
            $originalValue,
            ['removedoublespaces' => true]
        );
        $cleanHighlighted = tx_mksearch_util_Misc::html2plain(
            $highlightedValue,
            ['removedoublespaces' => true]
        );

        // check only, if the values not the same!
        if ($cleanOriginalValue !== $cleanHighlighted) {
            // create the WRAP!
            $wrap = ['', ''];
            if (!empty($options['stdWrap.'])) {
                $token = uniqid();
                $wrap = $this->getConfigurations()->getCObj()->stdWrap(
                    $token,
                    $options['stdWrap.']
                );
                $wrap = explode($token, (string) $wrap);
            }

            // add pre, if the first part is not the same!
            if (!Sys25\RnBase\Utility\Strings::isFirstPartOfStr(
                $cleanOriginalValue,
                $cleanHighlighted
            )
            ) {
                $highlightedValue = $wrap[0].$highlightedValue;
            }

            // add post, if the last part is not the same!
            if (!Sys25\RnBase\Utility\Strings::isLastPartOfStr(
                $cleanOriginalValue,
                $cleanHighlighted
            )
            ) {
                $highlightedValue .= $wrap[1];
            }
        }

        return $highlightedValue;
    }

    /**
     * @return array
     */
    public function processFacets(Apache_Solr_Response &$response)
    {
        if (!$response->facet_counts) {
            return [];
        }

        // usually "searchsolr.responseProcessor.facet."
        $confId = $this->getConfId().'facet.';
        $configurations = $this->getConfigurations();

        $builderClass = $configurations->get($confId.'builderClass');
        $builderClass = $builderClass ?: 'tx_mksearch_util_FacetBuilder';

        $facetBuilder = tx_mksearch_util_FacetBuilder::getInstance(
            $builderClass,
            $configurations->get($confId)
        );

        $facets = $facetBuilder->buildFacets($response->facet_counts);

        if ($configurations->getBool($confId.'sorting')) {
            return $facetBuilder->sortFacets($facets);
        }

        return $facets;
    }

    public function processSuggestions(Apache_Solr_Response &$response): array
    {
        $confId = $this->getConfId().'suggestions.';
        // Suggestions
        if ($response->spellcheck && $response->spellcheck->suggestions) {
            $builderClass = $this->getConfigurations()->get($confId.'builderClass');
            $builderClass = $builderClass ?: 'tx_mksearch_util_SuggestionBuilder';
            $builder = tx_mksearch_util_SuggestionBuilder::getInstance($builderClass);

            return $builder->buildSuggestions($response->spellcheck->suggestions);
        }

        return [];
    }

    /**
     * Checks if we got highlightings and wraps them in case in an array.
     */
    protected function getHighlighting(Apache_Solr_Response $response): array
    {
        $aHighlights = [];
        // Highlighting für jedes gefundene Dokument
        if (!empty($response->highlighting)) {
            foreach ($response->highlighting as $iHighlightId => $aHighlighting) {
                // jedes Feld mit einem Highlighting
                foreach ($aHighlighting as $sHighlightFieldsName => $aHighlightFields) {
                    // jedes Highlighting
                    foreach ($aHighlightFields as $sHighlightField) {
                        // wir nehmen als key die Dokument ID ($highlightId) zwecks Zuordnung
                        $aHighlights[$iHighlightId][$sHighlightFieldsName] = $sHighlightField;
                    }
                }
            }
        }

        return $aHighlights;
    }
}
