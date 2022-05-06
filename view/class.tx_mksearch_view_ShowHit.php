<?php

/**
 * Detailseite eines beliebigen Datensatzes aus Momentan Lucene oder Solr.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_view_ShowHit extends \Sys25\RnBase\Frontend\View\Marker\ListView
{
    public function createOutput($template, \Sys25\RnBase\Frontend\Request\RequestInterface $request, $formatter)
    {
        $confId = $request->getConfId();
        $item = $request->getViewContext()->offsetGet('item');
        $itemPath = $this->getItemPath($request->getConfigurations(), $confId);
        $markerClass = $this->getMarkerClass($request->getConfigurations(), $confId);

        $marker = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($markerClass);

        $out = $marker->parseTemplate($template, $item, $formatter, $confId.$itemPath.'.', strtoupper($itemPath));

        return $out;
    }

    protected function getItemPath($configurations, $confId)
    {
        $itemPath = $configurations->get($confId.'template.itempath');

        return $itemPath ? $itemPath : 'hit';
    }

    protected function getMarkerClass($configurations, $confId)
    {
        $marker = $configurations->get($confId.'template.markerclass');

        return $marker ? $marker : 'tx_mksearch_marker_Search';
    }

    /**
     * Subpart der im HTML-Template geladen werden soll. Dieser wird der Methode
     * createOutput automatisch als $template übergeben.
     *
     * @return string
     */
    public function getMainSubpart(\Sys25\RnBase\Frontend\View\ContextInterface $viewData)
    {
        return $this->subpart ? $this->subpart : '###SHOWHIT###';
    }
}
