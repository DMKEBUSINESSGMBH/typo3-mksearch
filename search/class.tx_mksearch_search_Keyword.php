<?php

tx_rnbase::load('tx_rnbase_util_SearchBase');

/**
 * Class to search keywords from database.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_search_Keyword extends tx_rnbase_util_SearchBase
{
    /**
     * Return table mappings.
     *
     * MUST be public as we need these data from external!
     */
    public function getTableMappings()
    {
        $tableMapping = array();
        $tableMapping['KEYWORD'] = 'tx_mksearch_keywords';

        return $tableMapping;
    }

    /**
     * return name of base table
     * MUST be public as we need these data from external!
     *
     * @see util/tx_rnbase_util_SearchBase#getBaseTable()
     */
    public function getBaseTable()
    {
        return 'tx_mksearch_keywords';
    }

    /**
     * return name of base table
     * MUST be public as we need these data from external!
     *
     * @see util/tx_rnbase_util_SearchBase#getBaseTable()
     */
    public function getBaseTableAlias()
    {
        return 'KEYWORD';
    }

    public function getWrapperClass()
    {
        return 'tx_mksearch_model_internal_Keyword';
    }

    protected function getJoins($tableAliases)
    {
        $join = '';

        return $join;
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/search/class.tx_mksearch_search_Keyword.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/search/class.tx_mksearch_search_Keyword.php'];
}
