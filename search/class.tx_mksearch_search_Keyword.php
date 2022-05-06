<?php

/**
 * Class to search keywords from database.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_search_Keyword extends \Sys25\RnBase\Search\SearchBase
{
    /**
     * Return table mappings.
     *
     * MUST be public as we need these data from external!
     */
    public function getTableMappings()
    {
        $tableMapping = [];
        $tableMapping['KEYWORD'] = 'tx_mksearch_keywords';

        return $tableMapping;
    }

    /**
     * return name of base table
     * MUST be public as we need these data from external!
     *
     * @see util/\Sys25\RnBase\Search\SearchBase#getBaseTable()
     */
    public function getBaseTable()
    {
        return 'tx_mksearch_keywords';
    }

    /**
     * return name of base table
     * MUST be public as we need these data from external!
     *
     * @see util/\Sys25\RnBase\Search\SearchBase#getBaseTable()
     */
    public function getBaseTableAlias()
    {
        return 'KEYWORD';
    }

    /**
     * @return bool
     */
    protected function useAlias()
    {
        return false;
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
