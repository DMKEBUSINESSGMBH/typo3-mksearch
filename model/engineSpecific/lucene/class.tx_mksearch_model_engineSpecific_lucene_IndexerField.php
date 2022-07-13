<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 das Medienkombinat
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
 * Model for indexer fields.
 *
 * @deprecated
 */
class tx_mksearch_model_engineSpecific_lucene_IndexerField extends tx_mksearch_model_IndexerFieldBase
{
    /**
     * Storage type.
     *
     * @var string
     *
     * @see self::$_possibleStorageTypes
     */
    private $_storageType;

    /**
     * Charset encoding.
     *
     * @var string
     */
    private $_encoding;

    /**
     * Possible storage types for Zend Lucene:.
     *
     * The storage type defines how a field is indexed / stored. Possible values are (borrowed from Zend_Lucene):
     *          * 'text':       Constructs a String-valued Field that is tokenized and indexed,
     *                          and is stored in the index, for return with hits.  Useful for short text
     *                          fields, like "title" or "subject". Term vector will not be stored for this field.
     *          * 'keyword':    Constructs a String-valued Field that is not tokenized, but is indexed
     *                          and stored.  Useful for non-text fields, e.g. date or url.
     *          * 'unindexed':  Constructs a String-valued Field that is not tokenized nor indexed,
     *                          but is stored in the index, for return with hits.
     *          * 'unstored':   Constructs a String-valued Field that is tokenized and indexed,
     *                          but that is not stored in the index.
     *          * 'binary':     Constructs a Binary String valued Field that is not tokenized nor indexed,
     *                          but is stored in the index, for return with hits.
     *
     * Actually, this attribute is not needed any longer here.
     * In order to make specific storage types possible, no checks take place in the layer
     * of the index field itself (for now...). Rather is this check out-sourced to the engine service
     * which actually is responsible for dealing with the given storage types.
     *
     * @todo: Create a nice interface for storage types which can be dependent from the search engine.
     *        The search engine might become responsible for providing both indexer document and indexer fields
     *        which both have to implement a (@todo) given interface.
     *
     * @var array
     */
    private static $_possibleStorageTypes = ['text', 'keyword', 'unindexed', 'unstored', 'binary'];

    /**
     * Constructor.
     *
     * @param string $value
     * @param string $storageType   One of the values of self::$_possibleStorageTypes
     * @param string $encoding=null
     */
    public function __construct($value, $storageType, $encoding = null)
    {
        // Don't check this anymore. @see self::$_possibleStorageTypes
//         if (!in_array($storageType, self::$_possibleStorageTypes))
//             throw new Exception('tx_mksearch_model_engineSpecific_lucene_IndexerField::__construct(): Invalid storage type "'.$storageType.'"!');
        $this->setValue($value);
        $this->_storageType = $storageType;
        $this->_encoding = $encoding;

        if (33 == $value) {
            \Sys25\RnBase\Utility\Debug::debug(
                $encoding,
                'class.tx_mksearch_model_engineSpecific_lucene_IndexerField.php'
            );
            exit;
        }
    }

    /**
     * Update value.
     *
     * @param string $value
     */
    public function updateValue($value, $boost = 1.0)
    {
        $this->_value = $value;
    }

    /**
     * Return storage type.
     *
     * @return string
     */
    public function getStorageType()
    {
        return $this->_storageType;
    }

    /**
     * Update storage type.
     *
     * @param string $storageType
     */
    public function updateStorageType($storageType)
    {
        // Don't check this anymore. @see self::$_possibleStorageTypes
//         if (!in_array($storageType, self::$_possibleStorageTypes))
//             throw new Exception('tx_mksearch_model_engineSpecific_lucene_IndexerField::updateStorageType(): Invalid storage type "'.$storageType.'"!');
        $this->_storageType = $storageType;
    }

    /**
     * Return encoding.
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->_encoding;
    }

    /**
     * Update encoding.
     *
     * @param string $encoding
     */
    public function updateEncoding($encoding)
    {
        $this->_encoding = $encoding;
    }
}
