<?php

/**
 * Detailseite eines beliebigen Datensatzes aus Momentan Lucene oder Solr.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_action_CacheHandler extends tx_rnbase_action_CacheHandlerDefault
{
    /**
     * Generate a key used to store data to cache.
     *
     * @return string
     */
    protected function generateKey()
    {
        $key = parent::generateKey(null);
        // Parameter cHash anhängen
        $key .= '_'.md5(serialize($this->getAllowedParameters()));

        return $key;
    }

    /**
     * Liefert alle erlaubten parameter,
     * welche zum erzeugen des CacheKeys verwendet werden.
     *
     * @return array
     */
    private function getAllowedParameters()
    {
        $parameters = $this->getConfigurations()->getParameters();
        $params = array();
        $allowed = tx_rnbase_util_Strings::trimExplode(
            ',',
            $this->getConfigValue('params.allowed', ''),
            1
        );
        foreach ($allowed as $p) {
            $params[$p] = $parameters->get($p);
        }

        return $params;
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/action/class.tx_mksearch_action_CacheHandler.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/action/class.tx_mksearch_action_CacheHandler.php'];
}
