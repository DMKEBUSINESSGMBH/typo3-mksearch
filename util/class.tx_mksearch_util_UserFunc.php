<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 das Medienkombinat
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
 ***************************************************************/

/**
 * userFunc Methoden.
 */
class tx_mksearch_util_UserFunc
{
    /**
     * @see     tx_mksearch_util_SearchBuilder::searchSolrOptions
     */
    public static function searchSolrOptions($term = '', $conf = [])
    {
        if (tx_mksearch_util_SearchBuilder::emptyTerm($term)) {
            return '';
        }

        /* @var $parameters tx_rnbase_parameters */
        $parameters = tx_rnbase::makeInstance('tx_rnbase_parameters');
        $combination = $parameters->get('combination', $conf['qualifier']);
        //fallback aus TS
        if (empty($combination)) {
            $combination = $conf['combination'];
        }
        // Bei free kann die volle Dismax-Syntax durch den User verwendet werden
        if (MKSEARCH_OP_FREE == $combination) {
            return $term;
        }

        $options = $parameters->get('options', $conf['qualifier']);

        $options = is_array($options) ? array_merge($conf, $options) : $conf;

        return tx_mksearch_util_SearchBuilder::searchSolrOptions($term, $combination, $options);
    }

    /**
     * vorerst machen wir nichts anders als bei SOLR.
     *
     * @see     tx_mksearch_util_SearchBuilder::searchSolrOptions
     */
    public static function searchLuceneOptions($term = '', $conf = [])
    {
        return self::searchSolrOptions($term, $conf);
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_UserFunc.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_UserFunc.php'];
}
