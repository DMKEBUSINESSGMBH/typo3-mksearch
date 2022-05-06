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
 * Generic class for indexer documents.
 */
class tx_mksearch_model_IndexerDocumentBase implements tx_mksearch_interface_IndexerDocument
{
    /**
     * Extension key of indexed data.
     *
     * @var tx_mksearch_interface_IndexerField
     */
    private $extKey = null;

    /**
     * Content type of indexed data.
     *
     * @var tx_mksearch_interface_IndexerField
     */
    private $contentType = null;

    /**
     * UID field.
     *
     * @var tx_mksearch_interface_IndexerField
     */
    private $uid = null;

    /**
     * deleted flag.
     *
     * @var bool
     */
    private $deleted = false;

    /**
     * Indexer field class name.
     *
     * @var string
     */
    private $fieldClass = null;

    /**
     * All content fields (except primary key fields) of the indexer document.
     *
     * @var array[tx_mksearch_interface_IndexerField]
     */
    private $data = [];

    /**
     * @var array
     */
    protected $secommands = [];

    /**
     * Factory for getting a new field object instance.
     *
     * @param mixed  $value                Either a scalar or an array value. Possibly not supported by every implementation!
     * @param mixed  $storageOptionsOrType Array (@see self::$_storageOptions) OR short cut string (@see self::$_storageType)
     * @param string $boost                Boost of that $value
     * @param string $dataType             Data type of $value (@see self::$_dataType)
     * @param string $encoding
     *
     * @return tx_mksearch_interface_IndexerField
     */
    protected function getFieldInstance($value, $storageOptionsOrType, $boost = 1.0, $dataType = null, $encoding = null)
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($this->fieldClass, $value, $storageOptionsOrType, $boost, $dataType, $encoding);
    }

    /***********************************
     * Basic functions
     ***********************************/

    /**
     * Constructor.
     *
     * Instantiate a new indexer document by defining the document's primary key
     * consisting of $extKey, $contentType, and $uid.
     *
     * @param string $extKey      Key of the extension the indexed data belongs to
     * @param string $contentType Name of content type the indexed data represents
     * @param string $fieldClass  Indexer field class name to be instantiated for each indexer field (must implement tx_mksearch_interface_IndexerField!)
     */
    public function __construct($extKey, $contentType, $fieldClass = 'tx_mksearch_model_IndexerFieldBase')
    {
        $this->fieldClass = $fieldClass;
        $this->extKey = $this->getFieldInstance($extKey, 'keyword');

        if (!$this->extKey instanceof tx_mksearch_interface_IndexerField) {
            throw new Exception('tx_mksearch_model_IndexerDocumentBase->__construct(): Given class in $fieldClass must implement tx_mksearch_interface_IndexerField!');
        }

        $this->contentType = $this->getFieldInstance($contentType, 'keyword');

        $this->addField('content_ident_s', $extKey.'.'.$contentType, 'keyword');
    }

    /**
     * Set uid.
     *
     * Setting the uid is not possible on instantiating since
     * instantiating takes place PRIOR to actually collecting
     * records to be indexed!
     *
     * @param int $uid
     */
    public function setUid($uid)
    {
        $this->uid = $this->getFieldInstance($uid, 'keyword', 1.0, 'int');
    }

    /**
     * Add field to indexer document.
     *
     * @param string $key                  Field name
     * @param string $data
     * @param string $storageOptionsOrType don't use id anymore! -> @see tx_mksearch_model_IndexerFieldBase::$_storageOptions and tx_mksearch_model_IndexerFieldBase::$_storageType
     * @param float  $boost
     * @param string $dataType
     * @param string $encoding=null
     */
    public function addField($key, $data, $storageOptionsOrType = 'keyword', $boost = 1.0, $dataType = null, $encoding = null)
    {
        $this->data[$key] = $this->getFieldInstance($data, $storageOptionsOrType, $boost, $dataType, $encoding);
    }

    /**
     * Return primary key fields of index document.
     *
     * @return array[
     *                'extKey' => tx_mksearch_interface_IndexerField,
     *                'contentType'   => tx_mksearch_interface_IndexerField,
     *                'uid'           => tx_mksearch_interface_IndexerField
     *                ]
     */
    public function getPrimaryKey($flat = false)
    {
        if (empty($this->uid)) {
            throw new Exception('tx_mksearch_model_IndexerDocumentBase->getPrimaryKey(): uid not yet set!');
        }

        return !$flat ? ['extKey' => $this->extKey, 'contentType' => $this->contentType, 'uid' => $this->uid] :
             $this->extKey->getValue().':'.$this->contentType->getValue().':'.$this->uid->getValue();
    }

    public function __toString()
    {
        $ret = $this->extKey->getValue().':'.$this->contentType->getValue().':'.(!empty($this->uid) ? $this->uid->getValue() : 'undefined');

        return $ret;
    }

    /**
     * Return data of indexer document.
     *
     * @return array[tx_mksearch_interface_IndexerField]
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * (non-PHPdoc).
     *
     * @see tx_mksearch_interface_IndexerDocument::getSECommands()
     */
    public function getSECommands()
    {
        return $this->secommands;
    }

    /**
     * (non-PHPdoc).
     *
     * @see tx_mksearch_interface_IndexerDocument::addSECommand()
     */
    public function addSECommand($command, $options)
    {
        if (!is_array($this->secommands)) {
            $this->secommands = [];
        }
        $this->secommands[$command] = $options;
    }

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
    public function setTitle($title, $encoding = 'utf-8')
    {
        $this->data['title'] = $this->getFieldInstance($title, 'text', 1.0, 'string', $encoding);
    }

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
    public function setAbstract($abstract, $length = null, $wordCut = true, $encoding = 'utf-8')
    {
        $abstract = tx_mksearch_util_Misc::html2plain($abstract);

        if ($length || ($length = $this->getMaxAbstractLength())) {
            $abstract = mb_substr($abstract, 0, $length, $encoding);
        }
        $this->data['abstract'] = $this->getFieldInstance($abstract, 'unindexed', 1.0, 'string', $encoding);
    }

    /**
     * Return maximum allowed length of abstract text.
     *
     * @todo make configurable somehow - is this class the correct place for this function at all?
     *
     * @return int Max. length of abstract as defined in mksearch extension config parameter abstractMaxLength_[your extkey]_[your content type]
     */
    public function getMaxAbstractLength()
    {
        return 200;
//         return \Sys25\RnBase\Configuration\Processor::getExtensionCfgValue(
//             'mksearch',
//             'abstractMaxLength_' . $this->extKey->getValue() . '_' . $this->contentType->getValue()
//         );
    }

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
    public function setContent($content, $encoding = 'utf-8')
    {
        $this->data['content'] = $this->getFieldInstance($content, 'unstored', 1.0, 'text', $encoding);
    }

    /**
     * Set timestamp.
     *
     * Shortcut for setting a 'tstamp' field as indexed and stored keyword.
     *
     * @param $title
     */
    public function setTimestamp($tstamp)
    {
        $this->data['tstamp'] = $this->getFieldInstance(intval($tstamp), 'keyword', 1.0, 'int');
    }

    /**
     * (non-PHPdoc).
     *
     * @see tx_mksearch_interface_IndexerDocument::setDeleted()
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
    }

    /**
     * (non-PHPdoc).
     *
     * @see tx_mksearch_interface_IndexerDocument::getDeleted()
     */
    public function getDeleted()
    {
        return $this->deleted;
    }
}
