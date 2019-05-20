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
 * Interface for indexer documents.
 */
interface tx_mksearch_interface_IndexerDocument
{
    /***********************************
     * Basic functions
     ***********************************/

    /**
     * Constructor.
     *
     * Instanciate a new indexer document by defining the document's primary key
     * consisting of $extKey, $contentType, and $uid.
     *
     * @TODO: Feldklasse sollte entfernt werden und einheitlich zur Verfügung stehen.
     *
     * @param string $extKey      Key of the extension the indexed data belongs to
     * @param string $contentType Name of content type the indexed data represents
     * @param string $fieldClass  Indexer field class name to be instantiated for each indexer field (must implement tx_mksearch_interface_IndexerField!)
     */
    public function __construct($extKey, $contentType, $fieldClass = 'tx_mksearch_model_IndexerFieldBase');

    /**
     * Set uid.
     *
     * Setting the uid is not possible on instantiating since
     * instantiating takes place PRIOR to actually collecting
     * records to be indexed! Mal wieder sehr geschickt...
     *
     * @param string $uid
     */
    public function setUid($uid);

    /**
     * Add field to indexer document.
     *
     * @param string       $key                  Field name
     * @param string|array $data
     * @param string       $storageOptionsOrType
     *                                           See tx_mksearch_model_IndexerFieldBase::$_storageOptions and tx_mksearch_model_IndexerFieldBase::$_storageType
     * @param float        $boost
     * @param string       $dataType
     * @param string       $encoding
     */
    public function addField($key, $data, $storageOptionsOrType = 'keyword', $boost = 1.0, $dataType = null, $encoding = null);

    /**
     * Return primary key fields of index document.
     *
     * @param bool $flat if true the key is returned as flat string
     *
     * @return array[
     *                'extKey'        => tx_mksearch_interface_IndexerField,
     *                'contentType'   => tx_mksearch_interface_IndexerField,
     *                'uid'           => tx_mksearch_interface_IndexerField
     *                ]
     */
    public function getPrimaryKey($flat = false);

    /**
     * Return data of indexer document.
     *
     * @return array[tx_mksearch_interface_IndexerField]
     */
    public function getData();

    /**
     * This method provides a way, to send custom commands to search engine. It is up to the search engine,
     * whether or not the command is supported.
     *
     * @return array
     */
    public function getSECommands();

    /**
     * Add a search engine command to document.
     *
     * @param string $command
     * @param array  $options
     */
    public function addSECommand($command, $options);

    /***********************************
     * Additional functions
     ***********************************/

    /**
     * Set title.
     *
     * Shortcut for setting a 'title' field as indexed and stored text.
     *
     * @param string $title
     * @param string $encoding='utf-8'
     */
    public function setTitle($title, $encoding = 'utf-8');

    /**
     * Set abstract.
     *
     * Shortcut for setting an 'abstract' field as UNINDEXED text.
     *
     * The abstract of a document just stores data, which is basically used
     * for display on textual search results page, but is however NOT taken into account
     * in terms of indexing!
     *
     * @param string $abstract
     * @param int    $length           Cut abstract to that length - if 0, no cut takes place
     * @param bool   $wordCut          Cut at last full word
     * @param string $encoding='utf-8'
     */
    public function setAbstract($abstract, $length = null, $wordCut = true, $encoding = 'utf-8');

    /**
     * Return maximum allowed length of abstract text.
     *
     * @todo make configurable somehow - is this class the correct place for this function at all?
     *
     * @return int Max. length of abstract as defined in mksearch extension config parameter abstractMaxLength_[your extkey]_[your content type]
     */
    public function getMaxAbstractLength();

    /**
     * Set content of indexed document.
     *
     * Shortcut for setting a 'content' field as indexed, but UNSTORED text.
     *
     * This is used for indexing large textual data which does not need
     * to be stored for being returned within the search results.
     *
     * @param string $content
     * @param string $encoding='utf-8'
     */
    public function setContent($content, $encoding = 'utf-8');

    /**
     * Set timestamp.
     *
     * Shortcut for setting a 'tstamp' field as indexed and stored keyword.
     *
     * @param $title
     */
    public function setTimestamp($tstamp);

    /**
     * The document should be deleted from index.
     *
     * @param bool $deleted
     */
    public function setDeleted($deleted);

    /**
     * @return bool
     */
    public function getDeleted();
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/interface/class.tx_mksearch_interface_IndexerDocument.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/interface/class.tx_mksearch_interface_IndexerDocument.php'];
}
