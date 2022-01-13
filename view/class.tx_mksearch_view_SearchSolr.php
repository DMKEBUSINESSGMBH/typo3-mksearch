<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011-2013 das Medienkombinat
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * View class for displaying a list of solr search results.
 */
class tx_mksearch_view_SearchSolr extends \Sys25\RnBase\Frontend\View\Marker\BaseView
{
    /**
     * @var string
     */
    private $confId = '';
    /**
     * @var \Sys25\RnBase\Configuration\Processor
     */
    private $configurations = null;

    protected function createOutput($template, \Sys25\RnBase\Frontend\Request\RequestInterface $request, $formatter)
    {
        $viewData = $request->getViewContext();
        $configurations = $request->getConfigurations();
        $result = &$viewData->offsetGet('result');

        $items = $result ? $result['items'] : [];
        /* @var $listBuilder \Sys25\RnBase\Frontend\Marker\ListBuilder */
        $listBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \Sys25\RnBase\Frontend\Marker\ListBuilder::class,
            $viewData->offsetGet('filter') instanceof \Sys25\RnBase\Frontend\Marker\IListBuilderInfo ? $viewData->offsetGet('filter') : null
        );

        // wurden options für die markerklassen gesetzt?
        $markerParams = $viewData->offsetExists('markerParams') ? $viewData->offsetGet('markerParams') : [];

        $markerClass = $configurations->get($this->confId.'mainmarkerclass');
        $markerClass = $markerClass ? $markerClass : 'tx_mksearch_marker_Search';

        $out = $listBuilder->render(
            $items,
            $viewData,
            $template,
            $markerClass,
            $this->confId.'hit.',
            'SEARCHRESULT',
            $formatter,
            $markerParams
        );

        //noch die Facetten parsen wenn da
        $out = $this->handleFacets($out, $viewData, $configurations, $formatter, $listBuilder, $result);
        $out = $this->handleSuggestions($out, $viewData, $configurations, $formatter, $listBuilder, $result);

        return $out;
    }

    /**
     * Ausgabe von Suggestions für alternative Suchbegriffe.
     *
     * @param string                     $template
     * @param array_object               $viewData
     * @param \Sys25\RnBase\Configuration\Processor   $configurations
     * @param \Sys25\RnBase\Frontend\Marker\FormatUtil  $formatter
     * @param \Sys25\RnBase\Frontend\Marker\ListBuilder $listBuilder
     * @param array                      $result
     *
     * @return string
     */
    protected function handleSuggestions($template, $viewData, $configurations, $formatter, $listBuilder, $result)
    {
        $suggestions = $result ? $result['suggestions'] : [];

        if (!empty($suggestions)) {
            $suggestions = reset($suggestions);
        }

        if (\Sys25\RnBase\Frontend\Marker\BaseMarker::containsMarker($template, 'SUGGESTIONS')) {
            $markerClass = $configurations->get($this->confId.'suggestions.markerClass');
            $markerClass = $markerClass ? $markerClass : \Sys25\RnBase\Frontend\Marker\SimpleMarker::class;

            $template = $listBuilder->render(
                $suggestions,
                $viewData,
                $template,
                $markerClass,
                $this->confId.'suggestions.',
                'SUGGESTION',
                $formatter
            );
        }

        return $template;
    }

    /**
     * Kümmert sich um das Parsen der Facetten.
     *
     * @param string                     $template
     * @param array_object               $viewData
     * @param \Sys25\RnBase\Configuration\Processor   $configurations
     * @param \Sys25\RnBase\Frontend\Marker\FormatUtil  $formatter
     * @param \Sys25\RnBase\Frontend\Marker\ListBuilder $listBuilder
     * @param array                      $result
     *
     * @return string
     */
    protected function handleFacets($template, $viewData, $configurations, $formatter, $listBuilder, $result)
    {
        $out = $template;

        // dann Liste parsen
        $facets = $result ? (array) $result['facets'] : [];

        // the old way!
        if (\Sys25\RnBase\Frontend\Marker\BaseMarker::containsMarker($out, 'FACETS')) {
            //erstmal die Markerklasse holen
            $facetMarkerClass = $configurations->get($this->confId.'facet.markerClass');
            $facetMarkerClass = $facetMarkerClass ? $facetMarkerClass : 'tx_mksearch_marker_Facet';

            // früher wurden alle facetten in einer liste nacheinander herausgerendert!
            // dies muss aus kompatibilitätsgründen beibehalten werden.
            $mergedFacets = [];
            foreach ($facets as $group) {
                $mergedFacets = array_merge($mergedFacets, $group->getItems());
            }
            $out = $listBuilder->render(
                $mergedFacets,
                $viewData,
                $out,
                $facetMarkerClass,
                $this->confId.'facet.',
                'FACET',
                $formatter
            );

            // den alten zurücklink
            /* @var $baseMarker \Sys25\RnBase\Frontend\Marker\BaseMarker */
            $baseMarker = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Sys25\RnBase\Frontend\Marker\BaseMarker::class);
            $wrappedSubpartArray = $subpartArray = $markerArray = [];
            $baseMarker->initLink(
                $markerArray,
                $subpartArray,
                $wrappedSubpartArray,
                $formatter,
                $this->confId.'facet.',
                'reset',
                'FACET',
                [],
                $out
            );
            $out = \Sys25\RnBase\Frontend\Marker\Templates::substituteMarkerArrayCached(
                $out,
                $markerArray,
                $subpartArray,
                $wrappedSubpartArray
            );
        }

        // wir geben die facetten grupiert aus.
        if (\Sys25\RnBase\Frontend\Marker\BaseMarker::containsMarker($out, 'GROUPEDFACETS')) {
            //erstmal die Markerklasse holen
            $groupedMarkerClass = $configurations->get($this->confId.'groupedfacet.markerClass');
            $groupedMarkerClass = $groupedMarkerClass ? $groupedMarkerClass : 'tx_mksearch_marker_GroupedFacet';

            $out = $listBuilder->render(
                $facets,
                $viewData,
                $out,
                $groupedMarkerClass,
                $this->confId.'groupedfacet.',
                'GROUPEDFACET',
                $formatter
            );
        }

        return $out;
    }

    /**
     * This method is called first.
     *
     * @param \Sys25\RnBase\Configuration\Processor $configurations
     */
    public function _init(&$configurations)
    {
        $this->confId = $this->getController()->getConfId();
        $this->configurations = &$configurations;
    }

    /**
     * Subpart der im HTML-Template geladen werden soll. Dieser wird der Methode
     * createOutput automatisch als $template übergeben.
     *
     * @param ArrayObject $viewData
     *
     * @return string
     */
    public function getMainSubpart(&$viewData)
    {
        // Wir versuchen den Mainpart aus der viewdata zu holen.
        // Das kann der Fall sein, wenn der Mainpart im Filter oder einer eigenen Action gesetzt wurde.
        $mainSubpart = $viewData->offsetGet('mainsubpart');
        // Wir holen uns den Mainpart vom Typoscript.
        $mainSubpart = $mainSubpart ? $mainSubpart : $this->configurations->get($this->confId.'mainsubpart');
        // Fallback, wenn kein Mainpart gesetzt wurde.
        return $mainSubpart ? $mainSubpart : '###SEARCH###';
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/view/class.tx_mksearch_view_SearchSolr.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/view/class.tx_mksearch_view_SearchSolr.php'];
}
