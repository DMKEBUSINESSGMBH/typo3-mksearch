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

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/service/class.tx_mksearch_service_internal_Keyword.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/service/class.tx_mksearch_service_internal_Keyword.php'];
}
