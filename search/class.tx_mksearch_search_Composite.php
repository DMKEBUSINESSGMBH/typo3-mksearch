<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 das Medienkombinat
 *  All rights reserved
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 ***************************************************************/

tx_rnbase::load('tx_rnbase_util_SearchBase');

/**
 * Class to search index configuration composites from database.
 */
class tx_mksearch_search_Composite extends tx_rnbase_util_SearchBase
{
    /**
     * Return table mappings.
     *
     * MUST be public as we need these data from external!
     */
    public function getTableMappings()
    {
        $tableMapping = array();
        $tableMapping['CMP'] = self::getBaseTable();
        $tableMapping['INDX'] = 'tx_mksearch_indices';
        $tableMapping['INDXCMPMM'] = 'tx_mksearch_indices_configcomposites_mm';
        $tableMapping['CFG'] = 'tx_mksearch_indexerconfigs';
        $tableMapping['CMPCFGMM'] = 'tx_mksearch_configcomposites_indexerconfigs_mm';

        return $tableMapping;
    }

    /**
     * return name of base table
     * MUST be public as we need these data from external!
     *
     * @see tx_rnbase_util_SearchBase::getBaseTable()
     */
    public function getBaseTable()
    {
        return 'tx_mksearch_configcomposites';
    }

    /**
     * return name of base table
     * MUST be public as we need these data from external!
     *
     * @see util/tx_rnbase_util_SearchBase#getBaseTable()
     */
    public function getBaseTableAlias()
    {
        return 'CMP';
    }

    public function getWrapperClass()
    {
        return 'tx_mksearch_model_internal_Composite';
    }

    protected function getJoins($tableAliases)
    {
        $join = '';
        $tableMapping = $this->getTableMappings();

        // Additional table "indices" or its MM table?
        if (isset($tableAliases['INDXCMPMM']) or isset($tableAliases['INDX'])) {
            $join .=
                ' JOIN '.$tableMapping['INDXCMPMM'].
                    ' ON '.$tableMapping['CMP'].'.uid = '.$tableMapping['INDXCMPMM'].'.uid_foreign';
        }

        // Additional table "indice"?
        if (isset($tableAliases['INDX'])) {
            $join .= ' JOIN '.$tableMapping['INDX'].' ON '.$tableMapping['INDXCMPMM'].'.uid_local = '.$tableMapping['INDX'].'.uid';
        }

        // Additional table "configs" or its MM table?
        if (isset($tableAliases['CMPCFGMM']) or isset($tableAliases['CFG'])) {
            $join .=
                ' JOIN '.$tableMapping['CMPCFGMM'].
                    ' ON '.$tableMapping['CMP'].'.uid = '.$tableMapping['CMPCFGMM'].'.uid_local';
        }

        // Additional table "configs"?
        if (isset($tableAliases['CFG'])) {
            $join .= ' JOIN '.$tableMapping['CFG'].' ON '.$tableMapping['CMPCFGMM'].'.uid_foreign = '.$tableMapping['INDX'].'.uid';
        }

        return $join;
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/search/class.tx_mksearch_search_Composite.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/search/class.tx_mksearch_search_Composite.php'];
}
