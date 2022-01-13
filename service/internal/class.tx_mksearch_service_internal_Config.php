<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 das Medienkombinat
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
 * Service for accessing indexer configuration models from database.
 */
class tx_mksearch_service_internal_Config extends tx_mksearch_service_internal_Base
{
    /**
     * Search class of this service.
     *
     * @var string
     */
    protected $searchClass = 'tx_mksearch_search_Config';

    /**
     * Search database for all configurated Indices.
     *
     * @param tx_mksearch_model_internal_Composite $indexerconfig
     *
     * @return array[tx_mksearch_model_internal_Config]
     */
    public function getByComposite(tx_mksearch_model_internal_Composite $composite)
    {
        $fields['CMPCFGMM.uid_local'][OP_EQ_INT] = $composite->getUid();

        return $this->search($fields, []);
    }

    /**
     * Enter description here ...
     *
     * @param tx_mksearch_model_internal_Index $index
     *
     * @return array
     */
    public function getIndexerOptionsByIndex(tx_mksearch_model_internal_Index $index)
    {
        $fields = [
            'CFG.hidden' => [OP_EQ_INT => 0],
            'CMP.hidden' => [OP_EQ_INT => 0],
            'INDX.hidden' => [OP_EQ_INT => 0],
            'INDX.uid' => [OP_EQ_INT => $index->uid],
        ];
        $options = [
            'orderby' => [
                'INDXCMPMM.sorting' => 'ASC',
                'CMPCFGMM.sorting' => 'ASC',
            ],
        ];
        $tmpCfg = $this->search($fields, $options);

        $sTs = $index->getProperty('configuration')."\n";

        //use the uid of the index config as key to be able to
        //get different configs for the same contenttype
        foreach ($tmpCfg as $oModel) {
            $sTs .= $oModel->getProperty('extkey').'.'.$oModel->getProperty('contenttype').'.'.$oModel->getProperty('uid')." {\n".
            $oModel->getProperty('configuration')."\n}\n";
        }

        return tx_mksearch_util_Misc::parseTsConfig($sTs);
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/service/internal/class.tx_mksearch_service_internal_Config.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/service/internal/class.tx_mksearch_service_internal_Config.php'];
}
