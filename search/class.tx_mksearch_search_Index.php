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

/**
 * Class to search indices from database.
 */
class tx_mksearch_search_Index extends tx_rnbase_util_SearchBase
{
    /**
     * Return table mappings.
     *
     * MUST be public as we need these data from external!
     */
    public function getTableMappings()
    {
        $tableMapping = [];
        $tableMapping['INDX'] = self::getBaseTable();
        $tableMapping['CMP'] = 'tx_mksearch_configcomposites';
        $tableMapping['INDXCMPMM'] = 'tx_mksearch_indices_configcomposites_mm';

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
        return 'tx_mksearch_indices';
    }

    /**
     * return name of base table
     * MUST be public as we need these data from external!
     *
     * @see util/tx_rnbase_util_SearchBase#getBaseTable()
     */
    public function getBaseTableAlias()
    {
        return 'INDX';
    }

    public function getWrapperClass()
    {
        return 'tx_mksearch_model_internal_Index';
    }

    protected function getJoins($tableAliases)
    {
        $join = '';
        $tableMapping = $this->getTableMappings();

        // Additional table "composites" or its MM table?
        if (isset($tableAliases['INDXCMPMM']) or isset($tableAliases['CMP'])) {
            $join .=
                ' JOIN '.$tableMapping['INDXCMPMM'].
                    ' ON '.$tableMapping['INDX'].'.uid = '.$tableMapping['INDXCMPMM'].'.uid_local';
        }

        // Additional table "composites"?
        if (isset($tableAliases['CMP'])) {
            $join .= ' JOIN '.$tableMapping['CMP'].' ON '.$tableMapping['INDXCMPMM'].'.uid_foreign = '.$tableMapping['CMP'].'.uid';
        }

        return $join;
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/search/class.tx_mksearch_search_Index.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/search/class.tx_mksearch_search_Index.php'];
}
