<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of the "mksearch" Extension for TYPO3 CMS.
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GNU Lesser General Public License can be found at
 * www.gnu.org/licenses/lgpl.html
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 * Class to search index configuration composites from database.
 */
class tx_mksearch_search_Composite extends Sys25\RnBase\Search\SearchBase implements tx_mksearch_search_Interface
{
    /**
     * Return table mappings.
     *
     * MUST be public as we need these data from external!
     */
    protected function getTableMappings(): array
    {
        return ['CMP' => self::getBaseTable(), 'INDX' => 'tx_mksearch_indices', 'INDXCMPMM' => 'tx_mksearch_indices_configcomposites_mm', 'CFG' => 'tx_mksearch_indexerconfigs', 'CMPCFGMM' => 'tx_mksearch_configcomposites_indexerconfigs_mm'];
    }

    /**
     * return name of base table
     * MUST be public as we need these data from external!
     *
     * @see Sys25\RnBase\Search\SearchBase::getBaseTable()
     */
    protected function getBaseTable(): string
    {
        return 'tx_mksearch_configcomposites';
    }

    protected function getBaseTableAlias(): string
    {
        return 'CMP';
    }

    /**
     * Is used in tx_mksearch_service_internal_Base::getByPageId()).
     */
    public function getMainTableAlias(): string
    {
        return $this->getBaseTableAlias();
    }

    protected function useAlias(): bool
    {
        return false;
    }

    public function getWrapperClass(): string
    {
        return 'tx_mksearch_model_internal_Composite';
    }

    protected function getJoins($tableAliases): string
    {
        $join = '';
        $tableMapping = $this->getTableMappings();

        // Additional table "indices" or its MM table?
        if (isset($tableAliases['INDXCMPMM']) || isset($tableAliases['INDX'])) {
            $join .=
                ' JOIN '.$tableMapping['INDXCMPMM'].
                    ' ON '.$tableMapping['CMP'].'.uid = '.$tableMapping['INDXCMPMM'].'.uid_foreign';
        }

        // Additional table "indice"?
        if (isset($tableAliases['INDX'])) {
            $join .= ' JOIN '.$tableMapping['INDX'].' ON '.$tableMapping['INDXCMPMM'].'.uid_local = '.$tableMapping['INDX'].'.uid';
        }

        // Additional table "configs" or its MM table?
        if (isset($tableAliases['CMPCFGMM']) || isset($tableAliases['CFG'])) {
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
