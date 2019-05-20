<?php
/**
 * @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2011 Hannes Bochmann <dev@dmk-ebusiness.de>
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
 * benötigte Klassen einbinden.
 */
tx_rnbase::load('tx_mksearch_indexer_Base');

/**
 * Dummy Indexer for testing the base class as it is abstract.
 */
class tx_mksearch_tests_fixtures_indexer_Dummy extends tx_mksearch_indexer_Base
{
    /**
     * Return content type identification.
     * This identification is part of the indexed data
     * and is used on later searches to identify the search results.
     * You're completely free in the range of values, but take care
     * as you at the same time are responsible for
     * uniqueness (i.e. no overlapping with other content types) and
     * consistency (i.e. recognition) on indexing and searching data.
     *
     * @return array('extKey' => [extension key], 'name' => [key of content type]
     */
    public static function getContentType()
    {
        return array('mksearch', 'dummy');
    }

    /**
     * @param string                                $sTableName
     * @param array                                 $aRawData
     * @param tx_mksearch_interface_IndexerDocument $oIndexDoc
     * @param array                                 $aOptions
     *
     * @return bool
     */
    protected function stopIndexing($sTableName, $aRawData, tx_mksearch_interface_IndexerDocument $oIndexDoc, $aOptions)
    {
        //nothing to handle by default. continue indexing
        return $aRawData['stopIndexing'];
    }

    /**
     * (non-PHPdoc).
     *
     * @see tx_mksearch_interface_Indexer::prepareSearchData()
     */
    public function indexData(tx_rnbase_IModel $oModel, $sTableName, $aRawData, tx_mksearch_interface_IndexerDocument $oIndexDoc, $aOptions)
    {
        $this->indexModelByMapping($oModel, $this->getTestMapping(), $oIndexDoc);
        //with keep html
        $this->indexModelByMapping($oModel, $this->getTestMapping(), $oIndexDoc, 'keepHtml_', array('keepHtml' => 1));

        if ($oModel->record['multiValue']) {
            $aModels = array($oModel, $oModel);
            $this->indexArrayOfModelsByMapping(
                $aModels,
                $this->getTestMapping(),
                $oIndexDoc,
                'multivalue_'
            );
        }

        $aCategories = $this->getTestCategories();
        //includes
        if (!$this->checkInOrExcludeOptions($aCategories, $aOptions)) {
            return null;
        }
        //excludes found
        if (!$this->checkInOrExcludeOptions($aCategories, $aOptions, 1)) {
            return null;
        }

        return $oIndexDoc;
    }

    protected function getTestCategories()
    {
        return array(
            0 => $this->createModel(array('uid' => 1)),
            1 => $this->createModel(array('uid' => 2)),
        );
    }

    /**
     * Returns the mapping of the record fields to the
     * solr doc fields.
     *
     * @return array
     */
    protected function getTestMapping()
    {
        return array(
            'test_field_1' => 'test_field_1_s',
            'test_field_2' => 'test_field_2_s',
        );
    }

    /**
     * Returns the model to be indexed.
     *
     * @param array $aRawData
     *
     * @return tx_rnbase_IModel
     */
    protected function createModel(array $rawData, $tableName = null, $options = array())
    {
        return tx_rnbase::makeInstance('tx_rnbase_model_base', $rawData);
    }

    /**
     * Return the default Typoscript configuration for this indexer.
     *
     * Overwrite this method to return the indexer's TS config.
     * Note that this config is not used for actual indexing
     * but only serves as assistance when actually configuring an indexer!
     * Hence all possible configuration options should be set or
     * at least be mentioned to provide an easy-to-access inline documentation!
     *
     * @return string
     */
    public function getDefaultTSConfig()
    {
        return '';
    }

    public function callGetPidList()
    {
        return $this->_getPidList('');
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/seminars/class.tx_mksearch_indexer_seminars_Seminar.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/indexer/seminars/class.tx_mksearch_indexer_seminars_Seminar.php'];
}
