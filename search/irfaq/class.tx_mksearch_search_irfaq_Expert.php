<?php

/**
 * benötigte Klassen einbinden.
 */

/**
 * @author Hannes Bochmann
 */
class tx_mksearch_search_irfaq_Expert extends \Sys25\RnBase\Search\SearchBase
{
    /**
     * getTableMappings().
     */
    protected function getTableMappings()
    {
        $tableMapping = [];

        // Hook to append other tables
        \Sys25\RnBase\Utility\Misc::callHook(
            'mksearch',
            'search_irfaq_Expert_getTableMapping_hook',
            ['tableMapping' => &$tableMapping],
            $this
        );

        return $tableMapping;
    }

    /**
     * useAlias().
     */
    protected function useAlias()
    {
        return true;
    }

    /**
     * getBaseTableAlias().
     */
    protected function getBaseTableAlias()
    {
        return 'IRFAQ_EXPERT';
    }

    /**
     * getBaseTable().
     */
    protected function getBaseTable()
    {
        return 'tx_irfaq_expert';
    }

    /**
     * getWrapperClass().
     */
    public function getWrapperClass()
    {
        return 'tx_mksearch_model_irfaq_Expert';
    }

    /**
     * Liefert alle JOINS zurück.
     *
     * @param array $tableAliases
     *
     * @return string
     */
    protected function getJoins($tableAliases)
    {
        $join = '';

        // Hook to append other tables
        \Sys25\RnBase\Utility\Misc::callHook(
            'mksearch',
            'search_irfaq_Expert_getJoins_hook',
            ['join' => &$join, 'tableAliases' => $tableAliases],
            $this
        );

        return $join;
    }
}
