<?php

/**
 * benötigte Klassen einbinden.
 */

/**
 * @author Hannes Bochmann
 */
class tx_mksearch_search_irfaq_Question extends \Sys25\RnBase\Search\SearchBase
{
    /**
     * getTableMappings().
     */
    protected function getTableMappings()
    {
        $tableMapping = [];
        $tableMapping['IRFAQ_QUESTION'] = 'tx_irfaq_q';
        $tableMapping['IRFAQ_QUESTION_CATEGORY_MM'] = 'tx_irfaq_q_cat_mm';

        // Hook to append other tables
        \Sys25\RnBase\Utility\Misc::callHook(
            'mksearch',
            'search_irfaq_Question_getTableMapping_hook',
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
        return 'IRFAQ_QUESTION';
    }

    /**
     * getBaseTable().
     */
    protected function getBaseTable()
    {
        return 'tx_irfaq_q';
    }

    /**
     * getWrapperClass().
     */
    public function getWrapperClass()
    {
        return 'tx_mksearch_model_irfaq_Question';
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

        //rezepte mit typ soundso
        if (isset($tableAliases['IRFAQ_QUESTION_CATEGORY_MM'])) {
            $join .= ' JOIN tx_irfaq_q_cat_mm AS IRFAQ_QUESTION_CATEGORY_MM ON IRFAQ_QUESTION.uid = IRFAQ_QUESTION_CATEGORY_MM.uid_local';
        }

        // Hook to append other tables
        \Sys25\RnBase\Utility\Misc::callHook(
            'mksearch',
            'search_irfaq_Question_getJoins_hook',
            ['join' => &$join, 'tableAliases' => $tableAliases],
            $this
        );

        return $join;
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/search/irfaq/class.tx_mksearch_search_irfaq_Expert.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/search/irfaq/class.tx_mksearch_search_irfaq_Expert.php'];
}
