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

/**
 * Interface for search engine services for the "mksearch" extension.
 *
 * @author  Lars Heber <dev@dmk-ebusiness.de>
 * @package     TYPO3
 * @subpackage  tx_mksearch
 */
interface tx_mksearch_interface_SearchEngine
{

    /**
     * Search indexed data
     *
     * @param array     $fields
     * @param array     $options
     * @return array[tx_mksearch_model_SearchResult]    search results
     */
    public function search(array $fields = array(), array $options = array());

    /**
     * Get a document from index
     * @param string $uid
     * @param string $extKey
     * @param string $contentType
     * @return tx_mksearch_model_SearchHit
     */
    public function getByContentUid($uid, $extKey, $contentType);


    /**
     * Return name of the index currently opened
     *
     * @return string
     */
    public function getOpenIndexName();

    /**
     * Open an index
     *
     * @param tx_mksearch_model_internal_Index  $index Instance of the index to open
     * @param bool      $forceCreation  Force creation of index if it doesn't exist
     * @return void
     */
    public function openIndex(tx_mksearch_model_internal_Index $index, $forceCreation = false);

    /**
     * Check if the specified index exists
     *
     * @param string    $name   Name of index
     * @return bool
     */
    public function indexExists($name);

    /**
     * Commit index
     *
     * @return bool success
     */
    public function commitIndex();

    /**
     * Close index
     *
     * @return void
     */
    public function closeIndex();

    /**
     * Delete an entire index
     *
     * @param optional string $name Name of index to delete, if not the open index is meant to be deleted
     * @return void
     */
    public function deleteIndex($name = null);

    /**
     * Optimize index
     *
     * @return void
     */
    public function optimizeIndex();

    /**
     * Replace an index with another.
     *
     * The index to be replaced will be deleted.
     * This actually means that the old's index's directory will be deleted recursively!
     *
     * @param string    $which  Name of index to be replaced i. e. deleted
     * @param string    $by     Name of index which replaces the index named $which
     * @return void
     */
    public function replaceIndex($which, $by);

    /**
     * Put a new record into index
     *
     * @param tx_mksearch_interface_IndexerDocument $doc    "Document" to index
     * @return bool     $success
     */
    public function indexNew(tx_mksearch_interface_IndexerDocument $doc);

    /**
     * Update or create an index record
     *
     * @param tx_mksearch_interface_IndexerDocument $doc    "Document" to index
     * @return bool     $success
     */
    public function indexUpdate(tx_mksearch_interface_IndexerDocument $doc);

    /**
     * Delete index document specified by content uid
     *
     * @param int       $uid            Unique identifier of data record - unique within the scope of $extKey and $content_type
     * @param string    $extKey         Key of extension the data record belongs to
     * @param string    $contentType    Name of semantic content type
     * @return bool success
     */
    public function indexDeleteByContentUid($uid, $extKey, $contentType);

    /**
     * Delete index document specified by index id
     *
     * @param string $id
     * @return void
     */
    public function indexDeleteByIndexId($id);

    /**
     * Delete documents specified by raw query
     * @param string $query
     */
    public function indexDeleteByQuery($query, $options = array());

    /**
     * Return an indexer document instance for the given content type
     *
     * @param string    $extKey         Extension key of records to be indexed
     * @param string    $contentType    Content type of records to be indexed
     * @return tx_mksearch_interface_IndexerDocument
     */
    public function makeIndexDocInstance($extKey, $contentType);

    /**
     * Returns a index status object.
     *
     * @return tx_mksearch_util_Status
     */
    public function getStatus();
    /**
     * Set index model with credential data
     *
     * @param tx_mksearch_model_internal_Index  $index Instance of the index to open
     * @return void
     */
    public function setIndexModel(tx_mksearch_model_internal_Index $index);

    /**
     * This function is called for each index after the indexing
     * is done.
     *
     * @param tx_mksearch_model_internal_Index $oIndex
     * @return void
     */
    public function postProcessIndexing(tx_mksearch_model_internal_Index $oIndex);
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/interface/class.tx_mksearch_interface_SearchEngine.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/interface/class.tx_mksearch_interface_SearchEngine.php']);
}
