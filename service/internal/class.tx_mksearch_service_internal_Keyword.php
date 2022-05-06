<?php

/**
 * Keyword service class.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_service_internal_Keyword extends tx_mksearch_service_Base
{
    /**
     * Return name of search class.
     *
     * @return string
     */
    public function getSearchClass()
    {
        return 'tx_mksearch_search_Keyword';
    }
}
