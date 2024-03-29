<?php

/**
 * Der FacetBuilder erstellt aus den Rohdaten der Facets passende Objekte für das Rendering.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_util_SolrResponseProcessor
{
    /**
     * Konfigurations Objekt.
     *
     * @var \Sys25\RnBase\Configuration\Processor
     */
    private $configurations;
    private $confId = 'responseProcessor.';

    /**
     * Enter description here ...
     *
     * @param array                    $response
     * @param array                    $options
     * @param \Sys25\RnBase\Configuration\Processor $configurations
     * @param string                   $confId
     *
     * @return bool
     */
    public static function processSolrResult(array &$result, $options, &$configurations, $confId)
    {
        static $instance = null;

        if (!array_key_exists('response', $result)
        || !($result['response'] instanceof Apache_Solr_Response)
        ) {
            return false;
        }

        if (!$instance) {
            $processorClass = $configurations->get($confId.'class');
            $processorClass = $processorClass ? $processorClass : get_called_class();
            $instance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($processorClass, $configurations, $confId);
        }

        $response = &$result['response'];
        $result = $instance->processSolrResponse($response, $options, $result);

        return true;
    }

    public function __construct(&$configurations, $confId)
    {
        $this->configurations = $configurations;
        $this->confId = $confId;
    }

    /**
     * @return \Sys25\RnBase\Configuration\Processor
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
     *
     * @param Apache_Solr_Response $response
     * @param array $options
     * @param array $result
     */
    public function processSolrResponse(Apache_Solr_Response &$response, $options, $result = [])
    {
        $result['items'] = $this->processHits($response, $options, empty($result['items']) ? [] : $result['items']);
        $result['facets'] = $this->processFacets($response);
        $result['suggestions'] = $this->processSuggestions($response);

        return $result;
    }

    /**
     * @TODO: sollte es hierfür nicht auch eine klasse wie tx_mksearch_util_HitBuilder geben?
     *
     * @param Apache_Solr_Response $response
     *
     * @return array
     */
    public function processHits(Apache_Solr_Response &$response, array $options, array $hits = [])
    {
        $confId = $this->getConfId().'hit.';

        // highlighting einfügen
        $highlights = $this->getHighlighting($response);

        // hier wird nur highlighting gesetzt
        // wenn keins existiert brauchen wir nichts machen
        if (empty($highlights)) {
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
                    $overrideWithHl = $overrideWithHl ? $overrideWithHl : (isset($options['overrideWithHl']) && $options['overrideWithHl']);
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
     * @param array  $options
     *
     * @return string
     */
    protected function handleHellip(
        $originalValue,
        $highlightedValue,
        array $options = []
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
                $wrap = explode($token, $wrap);
            }

            // add pre, if the first part is not the same!
            if (!\Sys25\RnBase\Utility\Strings::isFirstPartOfStr(
                $cleanOriginalValue,
                $cleanHighlighted
            )
            ) {
                $highlightedValue = $wrap[0].$highlightedValue;
            }
            // add post, if the last part is not the same!
            if (!\Sys25\RnBase\Utility\Strings::isLastPartOfStr(
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
     * @param Apache_Solr_Response $response
     *
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
        $builderClass = $builderClass ? $builderClass : 'tx_mksearch_util_FacetBuilder';

        $facetBuilder = tx_mksearch_util_FacetBuilder::getInstance(
            $builderClass,
            $configurations->get($confId)
        );

        $facets = $facetBuilder->buildFacets($response->facet_counts);

        if ($configurations->getBool($confId.'sorting')) {
            $facets = $facetBuilder->sortFacets($facets);
        }

        return $facets;
    }

    /**
     * @param Apache_Solr_Response $response
     *
     * @return array
     */
    public function processSuggestions(Apache_Solr_Response &$response)
    {
        $confId = $this->getConfId().'suggestions.';
        // Suggestions
        if ($response->spellcheck && $response->spellcheck->suggestions) {
            $builderClass = $this->getConfigurations()->get($confId.'builderClass');
            $builderClass = $builderClass ? $builderClass : 'tx_mksearch_util_SuggestionBuilder';
            $builder = tx_mksearch_util_SuggestionBuilder::getInstance($builderClass);
            $suggestions = $builder->buildSuggestions($response->spellcheck->suggestions);
        } else {
            $suggestions = [];
        }

        return $suggestions;
    }

    /**
     * Checks if we got highlightings and wraps them in case in an array.
     *
     * @param Apache_Solr_Response $response
     *
     * @return array
     */
    protected function getHighlighting(Apache_Solr_Response $response)
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
