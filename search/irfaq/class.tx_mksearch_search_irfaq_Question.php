<?php
/**
 * @package tx_mksearch
 * @subpackage tx_mksearch_search_irfaq
 *
 *  Copyright notice
 *
 *  (c) 2011 DMK E-Business GmbH <dev@dmk-ebusiness.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 * benötigte Klassen einbinden
 */

tx_rnbase::load('tx_rnbase_util_SearchBase');

/**
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_search_irfaq
 * @author Hannes Bochmann
 */
class tx_mksearch_search_irfaq_Question extends tx_rnbase_util_SearchBase
{

    /**
     * getTableMappings()
     */
    protected function getTableMappings()
    {
        $tableMapping = array();
        $tableMapping['IRFAQ_QUESTION'] = 'tx_irfaq_q';
        $tableMapping['IRFAQ_QUESTION_CATEGORY_MM'] = 'tx_irfaq_q_cat_mm';

        // Hook to append other tables
        tx_rnbase_util_Misc::callHook(
            'mksearch',
            'search_irfaq_Question_getTableMapping_hook',
            array('tableMapping' => &$tableMapping),
            $this
        );

        return $tableMapping;
    }

    /**
     * useAlias()
     */
    protected function useAlias()
    {
        return true;
    }

    /**
     * getBaseTableAlias()
     */
    protected function getBaseTableAlias()
    {
        return 'IRFAQ_QUESTION';
    }

    /**
     * getBaseTable()
     */
    protected function getBaseTable()
    {
        return 'tx_irfaq_q';
    }

    /**
     * getWrapperClass()
     */
    public function getWrapperClass()
    {
        return 'tx_mksearch_model_irfaq_Question';
    }

    /**
     * Liefert alle JOINS zurück
     *
     * @param array $tableAliases
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
        tx_rnbase_util_Misc::callHook(
            'mksearch',
            'search_irfaq_Question_getJoins_hook',
            array('join' => &$join, 'tableAliases' => $tableAliases),
            $this
        );

        return $join;
    }
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/search/irfaq/class.tx_mksearch_search_irfaq_Expert.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/search/irfaq/class.tx_mksearch_search_irfaq_Expert.php']);
}
