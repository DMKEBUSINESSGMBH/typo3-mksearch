<?php

/**
 * Detailseite eines beliebigen Datensatzes aus Momentan Lucene oder Solr.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_view_ShowHit extends tx_rnbase_view_Single
{
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
    public function getMainSubpart(&$viewData)
    {
        $subpart = $this->getController()->getConfigurations()->get($this->getController()->getConfId().'template.subpart');

        return $subpart ? $subpart : '###SHOWHIT###';
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/view/class.tx_mksearch_view_Search.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/view/class.tx_mksearch_view_Search.php'];
}
