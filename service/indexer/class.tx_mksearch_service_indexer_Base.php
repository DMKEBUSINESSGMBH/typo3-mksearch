<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Lars Heber <dev@dmk-ebusiness.de>
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

tx_rnbase::load('tx_mksearch_interface_Indexer');
tx_rnbase::load('tx_mksearch_interface_DataProvider');
tx_rnbase::load('Tx_Rnbase_Service_Base');

/**
 * Base indexer class. It uses DataProviders to lookup data to be indexed.
 *
 * @deprecated entfällt komplett
 */
abstract class tx_mksearch_service_indexer_Base extends Tx_Rnbase_Service_Base implements tx_mksearch_interface_Indexer
{
    /**
     * Options used for indexing.
     *
     * @var array
     */
    protected $options;

    /**
     * List of records to be deleted.
     *
     * @var array
     */
    private $deleteList = array();

    /**
     * Prepare indexer.
     *
     * This method prepares things for indexing,
     * i. e. evaluate options, prepare db query etc.
     * It must be called between instatiating the class
     * and calling nextItem() for the first time.
     *
     * @param array $options Indexer options
     * @param array $data    Tablename <-> uids matrix of records to be indexed (array('tab1' => array(2,5,6), 'tab2' => array(4,5,8))
     */
    public function prepare(array $options = array(), array $data = array())
    {
        if (is_null($this->options)) {
            // First run
            $this->deleteList = $data;
            $this->options = $options;
        }
        $dp = $this->getDataProvider();

        return $dp->prepareData($options, $data);
    }

    /**
     * Return next item which is to be indexed.
     *
     * @param tx_mksearch_interface_IndexerDocument $indexDoc Indexer document to be "filled", instantiated based on self::getContentType()
     *
     * @return tx_mksearch_interface_IndexerDocument|null
     */
    public function nextItem(tx_mksearch_interface_IndexerDocument $indexDoc)
    {
        // Iterate until valid data is found or all records are consumed ;-)
        do {
            $record = $this->getDataProvider()->getNextItem();
        } while ($record and !($indexDocResult = $this->prepareData($record, $this->options, $indexDoc)));

        if (isset($indexDocResult)) {
            return $indexDocResult;
        }

        return null;
    }

    /**
     * Quasi-destructor.
     *
     * Clean up things, e.g. free db resources,
     * and return a list of uids of records which are
     * to be deleted from the index.
     *
     * Note: Use of an ordinary __destruct() function is not productive here
     * as a guaranteed invoking of the destructor is not trivial to implement.
     * Additionally, as an indexer is mostly used as a service which may be
     * re-used over and over again
     * (@see tx_rnbase::makeInstanceService() -> persistence of service),
     * take care to restore the instance to a clean, initial state!
     *
     * @return array Matrix of records to be deleted
     */
    public function cleanup()
    {
        return $this->getDataProvider()->cleanupData();
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

    /**
     * Returns the DataProvider used to lookup data.
     *
     * @return tx_mksearch_interface_DataProvider
     */
    abstract protected function getDataProvider();

    /**
     * Get sql data for an optional follow-up data base query.
     *
     * By re-implementing this method one (or even more) follow-up db queries
     * can be initiated which is e.g. useful if additional records are discovered
     * on processing the previous db query.
     * This method is called whenever self::nextItem() cannot find any (more) records.
     * It is called repeatedly as long as its return value is not null.
     *
     * @param array $options from service configuration
     *
     * @see self::getSqlData()
     */
    protected function getFollowUpSqlData(array $options)
    {
        return null;
    }

    /**
     * Transform database record into tx_mksearch_model_IndexerDocument.
     *
     * @param array                                 $rawData
     * @param array                                 $options  from service configuration
     * @param tx_mksearch_interface_IndexerDocument $indexDoc Model to be filled
     *
     * @return tx_mksearch_interface_IndexerDocument or null, if record is not to be indexed
     */
    abstract protected function prepareData(array $rawData, array $options, tx_mksearch_interface_IndexerDocument $indexDoc);
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/service/indexer/class.tx_mksearch_service_indexer_Base.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/service/indexer/class.tx_mksearch_service_indexer_Base.php'];
}
