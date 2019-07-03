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

/**
 * DataProvider class to lookup data from database.
 *
 * @author  Lars Heber <dev@dmk-ebusiness.de>
 */
abstract class tx_mksearch_service_dp_Database implements tx_mksearch_interface_DataProvider
{
    /**
     * Options used for indexing.
     *
     * @var array
     */
    protected $options;

    /**
     * Container for sql resource.
     *
     * @var sql resource
     */
    protected $sqlRes;

    /**
     * Container for storing $GLOBALS['TYPO3_CONF_VARS']['FE']['pageOverlayFields'].
     *
     * @var string
     */
    private $pof;

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
    public function prepareData(array $options = array(), array $data = array())
    {
        // Explicitely include tstamp field to localization.
        // We have to do this on every preparation.
        // @see self::cleanup() PHPDoc
        $this->pof = $GLOBALS['TYPO3_CONF_VARS']['FE']['pageOverlayFields'];
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageOverlayFields'] .= ',tstamp';

        if (is_null($this->options)) {
            // First run
            $this->deleteList = $data;
            $this->options = $options;

            $sql = $this->getSqlData($this->options, $data);
        } else {
            // Try follow-up db query
            $sql = $this->getFollowUpSqlData($this->options);
        }

        // No sql data?
        if (is_null($sql)) {
            $this->sqlRes = null;

            return;
        }
        // else:
        if (!isset($sql['noEnableFields']) or !$sql['noEnableFields']) {
            // Complete where clause with hidden & deleted query
            if (!isset($sql['where']) or empty($sql['where'])) {
                $sql['where'] = '1=1';
            }

            // Limit query to records specified in $uids
            // @todo: change hard coded 'uid'?
            if (count($uids)) {
                $sql['where'] .= ' AND uid in ('.implode(',', $uids).')';
            }

            // Complete where clause with hidden & deleted query
            // Extract first table
            $sql['table'] = trim($sql['table']);
            $pos = strpos($sql['table'], ' ');
            $firstTable = false === $pos ? $sql['table'] : substr($sql['table'], 0, $pos);

            $page = tx_rnbase_util_TYPO3::getSysPage();
            if (isset($sql['skipEnableFields'])) {
                $ignore = array();
                foreach ($sql['skipEnableFields'] as $s) {
                    $ignore[$s] = true;
                }
                $sql['where'] .= $page->enableFields($firstTable, null, $ignore);
            } else {
                $sql['where'] .= $page->enableFields($firstTable);
            }
        }

        if ($options['debug']) {
            // Debug:
            echo "\n".$GLOBALS['TYPO3_DB']->SELECTquery(
                $sql['fields'],
                $sql['table'],
                isset($sql['where']) ? $sql['where'] : '',
                isset($sql['groupBy']) ? $sql['groupBy'] : null,
                isset($sql['orderBy']) ? $sql['orderBy'] : null,
                isset($sql['limit']) ? $sql['limit'] : null
            )."\n";
            die;
        }

        $this->sqlRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            $sql['fields'],
            $sql['table'],
            isset($sql['where']) ? $sql['where'] : '',
            isset($sql['groupBy']) ? $sql['groupBy'] : null,
            isset($sql['orderBy']) ? $sql['orderBy'] : null,
            isset($sql['limit']) ? $sql['limit'] : null
        );
    }

    /**
     * Return next item which is to be indexed.
     *
     * @return array
     */
    public function getNextItem()
    {
        // No valid DB resource?
        if (!is_resource($this->sqlRes)) {
            return null;
        }

        $record = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($this->sqlRes);

        return $record;
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
    public function cleanupData()
    {
        // Free sql query space
        if (is_resource($this->sqlRes)) {
            $GLOBALS['TYPO3_DB']->sql_free_result($this->sqlRes);
        }

        // Restore localization options
        $GLOBALS['TYPO3_CONF_VARS']['FE']['pageOverlayFields'] = $this->pof;

        // Reset things
        $this->options = null;
        $foo = $this->deleteList;
        $this->deleteList = array();

        return $foo;
    }

    /**
     * Get sql data necessary to grab data to be indexed from data base.
     *
     * @param array $options Indexer options
     * @param array $data    Tablename <-> uids matrix of records to be indexed (array('tab1' => array(2,5,6), 'tab2' => array(4,5,8))
     */
    abstract protected function getSqlData(array $options, array $data = array());

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
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/service/dp/class.tx_mksearch_service_dp_Database.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/service/dp/class.tx_mksearch_service_dp_Database.php'];
}