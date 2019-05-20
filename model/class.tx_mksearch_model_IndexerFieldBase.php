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

tx_rnbase::load('tx_mksearch_interface_IndexerField');

/**
 * Base model for indexer fields.
 */
class tx_mksearch_model_IndexerFieldBase implements tx_mksearch_interface_IndexerField
{
    /**
     * Field value.
     *
     * @var mixed
     */
    private $_value;

    /**
     * Field boost.
     *
     * If self::$_value is an array, self::$_boost may be a scalar
     * (meaning same boost for all values) or an array of the same size
     * like self::$_value.
     *
     * @var mixed
     */
    private $_boost;

    /**
     * Default storage options.
     *
     * * The following storage option keys shall be considered by any implementation:
     * * stored (bool):					Field is to be stored in the index for return with search hits.
     * * indexed (bool):				Field is to be indexed, so that it may be searched on.
     * * tokenized (bool):				Field should be tokenized as text prior to indexing.
     * * binary (bool):					Field is stored as binary.
     *
     * @var array
     */
    private $defaultStorageOptions = array(
        'stored' => true,
        'indexed' => true,
        'tokenized' => true,
        'binary' => false,
    );

    /**
     * Storage options.
     *
     * @var array
     *
     * @see self::$defaultStorageOptions
     */
    private $_storageOptions = array();

    /**
     * Define a storage type which as a shortcut replaces a fixed set of storage options and data type.
     *
     * Alternatively to giving a complete array of storage options in self::$_storageOptions,
     * a few textual shortcuts can be used instead which shall be mapped to the respective
     * self::$_storageOptions by the concrete implementation.
     * Possible values are (partly borrowed from Zend_Lucene):
     * * 'text':		Constructs a field (default data type, if not defined: "text")
     * 					that is tokenized and indexed, and is stored in the index, for return with hits.
     * 					Useful for medium-sized text fields needed in result list, e.g. abstract.
     * * 'tinytext':	Constructs a field (default data type, if not defined: "string")
     * 					that is tokenized and indexed, and is stored in the index, for return with hits.
     * 					Useful for short text fields, e.g. title or subject.
     * * 'keyword':		Constructs a field (default data type, if not defined: "string")
     * 					that is not tokenized, but is indexed and stored.
     * 					Useful for non-text fields, e.g. date or url.
     * * 'unindexed':	Constructs a field (default data type, if not defined: "string")
     * 					that is not tokenized nor indexed, but is stored in the index, for return with hits.
     * * 'unstored':	Constructs a field (default data type, if not defined: "text")
     * 					that is tokenized and indexed, but that is not stored in the index.
     * 					Useful for the actual textual payload of the data to be indexed.
     * * 'binary':		Constructs a field (default data type, if not defined: "blob")
     * 					that is not tokenized nor indexed, but is stored in the index, for return with hits.
     * * 'uid':			Constructs a field (default data type, if not defined: "int")
     * 					that is not tokenized, but is indexed and stored.
     *
     * @var string
     */
    private $_storageType = null;

    /**
     * Data type of the given field.
     *
     * Defines the basic data type of the payload.
     *
     * If a concrete implementation uses data types at all,
     * the following values shall be respected:
     * * int
     * * single
     * * double
     * * bool
     * * string
     * * text
     * * blob
     * * date		-> DateTime object
     * * datetime	-> DateTime object
     * * time		-> DateTime object
     *
     * As each item of array typed fields have to be of the same scalar type,
     * just give this scalar type - "array" is recognized implicitely from
     * the field value.
     *
     * @var string
     */
    private $_dataType = null;

    /**
     * Charset encoding.
     *
     * @var string
     */
    private $_encoding = null;

    /**
     * Split storage option / storage type.
     *
     * @param mixed $storageOptionsOrType
     */
    private function processStorageOptionsOrType($storageOptionsOrType)
    {
        // Shortcut? Set storage options automagically
        if (!is_array($storageOptionsOrType)) {
            $this->_storageType = $storageOptionsOrType;
            // Reset storage options to default
            $this->updateStorageOptions(array());
            switch ($this->_storageType) {
                // Nothing to do for text & tinytext - default options are just right.
                case 'text':
                case 'tinytext': break;

                case 'keyword':
                case 'uid':
                    $this->updateStorageOption('tokenized', false);
                    break;

                case 'unindexed':
                case 'binary':
                    $this->updateStorageOption('indexed', false);
                    $this->updateStorageOption('tokenized', false);
                    break;

                case 'unstored':
                    $this->updateStorageOption('stored', false);
                    break;

                default:
                    ;
            }
            // If data type is not set explicitely:
            if (!$this->_dataType) {
                switch ($this->_storageType) {
                    case 'text':
                    case 'unstored':
                        $this->updateDataType('text'); break;

                    case 'keyword':
                    case 'tinytext':
                    case 'unindexed':
                        $this->updateDataType('string'); break;

                    case 'binary':
                        $this->updateDataType('blob'); break;

                    case 'uid':
                        $this->updateDataType('int'); break;

                    default:
                        ;
                }
            }
        }
        // No short cut: Just normal storage options
        else {
            $this->updateStorageOptions($storageOptionsOrType);
        }
    }

    /**
     * Constructor
     * $storageOptionsOrType should be turned to deprecated.
     *
     * @param mixed  $value                Either a scalar or an array value. Possibly not supported by every implementation!
     * @param mixed  $storageOptionsOrType Array (@see self::$_storageOptions) OR short cut string (@see self::$_storageType)
     * @param string $boost                Boost of that $value
     * @param string $dataType             Data type of $value (@see self::$_dataType)
     * @param string $encoding
     */
    public function __construct($value, $storageOptionsOrType, $boost = 1.0, $dataType = null, $encoding = null)
    {
        $this->updateDataType($dataType);
        $this->processStorageOptionsOrType($storageOptionsOrType);

        $this->updateEncoding($encoding);
        // Call this one at last to enable deriving classes
        // to finally adjust options dependent on $value
        $this->updateValue($value, $boost);
    }

    /**
     * Stellt sicher, das keine Integer Werte gesetzt werden,
     * da diese Warnings verursachen.
     * (htmlspecialchars in Apache_Solr_Service::_documentToXmlFragment()).
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function fixValue($value)
    {
        if (is_array($value)) {
            foreach ($value as &$v) {
                $v = $this->fixValue($v);
            }
        } elseif (is_numeric($value)) {
            // aus dem int ein string machen
            $value = strval($value);
        }
        //		elseif(is_object($value)) {
        //			// was passiert mit objekten?
        //		}
        return $value;
    }

    /**
     * Return the field's value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->_value;
    }

    public function setValue($value)
    {
        $this->_value = $this->fixValue($value);
    }

    /**
     * Return the field's boost.
     *
     * Returned value has / should always have the cardinality like self::$_value.
     *
     * @return mixed
     */
    public function getBoost()
    {
        $val = $this->getValue();

        // Both value and boost are scalar
        if (!is_array($val) && !is_array($this->_boost)) {
            return $this->_boost;
        }
        // else
        // value is array, but boost is scalar
        if (is_array($val) && !is_array($this->_boost)) {
            $foo = array();
            for ($i = 0; $i < count($val); ++$i) {
                $foo[] = $this->_boost;
            }

            return $foo;
        }
        // else
        // Error: value is scalar, but boost is array? Fallback to first boost value
        if (!is_array($val) && is_array($this->_boost)) {
            return $this->_boost[0];
        }

        // else
        // Both value and boost are arrays
        // @todo: Check if $this->_value and $this->_boost have the same size
        return $this->_boost;
    }

    /**
     * Update the field's value.
     *
     * @param mixed $value
     * @param mixed $boost @see self::$_boost
     */
    public function updateValue($value, $boost = 1.0)
    {
        $this->_value = $this->fixValue($value);
        $this->_boost = $boost;
    }

    /**
     * Return storage options.
     *
     * @return array
     *
     * @see self::$_storageOptions
     */
    public function getStorageOptions()
    {
        return $this->_storageOptions;
    }

    /**
     * Update storage options.
     *
     * Given storage options are merged with self::$defaultStorageOptions.
     * Storage type is not touched, so storage options can be set additionally
     * after having defined a storage type.
     *
     * @param array $storageOptions
     *
     * @see self::$_storageOptions
     */
    public function updateStorageOptions($storageOptions)
    {
        $this->_storageOptions = array_merge($this->defaultStorageOptions, $storageOptions);
    }

    /**
     * Return requested storage option or null, if option does not exist.
     *
     * @param string $key
     *
     * @return mixed
     *
     * @see self::$_storageOptions
     */
    public function getStorageOption($key)
    {
        return array_key_exists($key, $this->_storageOptions) ?
            $this->_storageOptions[$key] :
            null;
    }

    /**
     * Update storage option.
     *
     * @param string $key
     * @param mixed  $storageOption
     *
     * @see self::$_storageOptions
     */
    public function updateStorageOption($key, $storageOption)
    {
        $this->_storageOptions[$key] = $storageOption;
    }

    /**
     * Return storage type (shortcut).
     *
     * @return string
     *
     * @see self::$_storageType
     */
    public function getStorageType()
    {
        return $this->_storageType;
    }

    /**
     * Update storage type (shortcut).
     *
     * Storage options are set automagically, overwriting still existing ones.
     * If not still explicitely defined, data type is also set.
     *
     * @param mixed $type
     *
     * @see self::$_storageType
     */
    public function updateStorageType($type)
    {
        $this->processStorageOptionsOrType($type);
    }

    /**
     * Return data type.
     *
     * @return string
     */
    public function getDataType()
    {
        return $this->_dataType;
    }

    /**
     * Update data type.
     *
     * @param string $dataType
     */
    public function updateDataType($dataType)
    {
        $this->_dataType = $dataType;
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

    public function __toString()
    {
        $mValue = $this->getValue();
        try {
            return (is_array($mValue)) ? implode(',', $mValue) : (is_object($mValue) ? $mValue->__toString() : '"'.$this->getValue().'"');
        } catch (Exception $e) {
            return 'ERROR: '.$e->getMessage();
        }
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/model/class.tx_mksearch_model_IndexerFieldBase.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/model/class.tx_mksearch_model_IndexerFieldBase.php'];
}
