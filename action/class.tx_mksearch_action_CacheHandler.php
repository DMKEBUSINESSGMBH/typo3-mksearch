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
    protected function getCacheKey()
    {
        $key = parent::getCacheKey();
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
        $params = [];
        $allowed = \Sys25\RnBase\Utility\Strings::trimExplode(
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
