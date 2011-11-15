<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Lars Heber <lars.heber@das-medienkombinat.de>
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
 * Interface for indexer fields
 */
interface tx_mksearch_interface_IndexerField {
	/**
	 * Constructor
	 * 
	 * @param mixed		$value					Either a scalar or an array value. Possibly not supported by every implementation!
	 * @param mixed		$storageOptionsOrType	Array (@see self::$_storageOptions) OR short cut string (@see self::$_storageType) 
	 * @param string	$encoding
	 */
	public function __construct($value, $storageOptionsOrType, $encoding=null);
	
	/**
	 * Return value
	 *
	 * @return string
	 */
	public function getValue();
	
	/**
	 * Return the field's boost
	 * 
	 * Returned value has / should always have the cardinality like self::$_value.
	 *
	 * @return mixed
	 */
	public function getBoost();

	/**
	 * Update the field's value
	 *
	 * @param mixed $value
	 * @param mixed $boost	@see self::$_boost
	 */
	public function updateValue($value, $boost=1.0);
	
	/**
	 * Return storage options
	 *
	 * @return array
	 */
	public function getStorageOptions();
	
	/**
	 * Return storage type (shortcut)
	 *
	 * @return string
	 */
	public function getStorageType();
	
	/**
	 * Update storage options
	 *
	 * @param array $storageOptions
	 */
	public function updateStorageOptions($storageOptions);
	
	/**
	 * Return requested storage option or null, if option does not exist
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getStorageOption($key);
		
	/**
	 * Update storage option
	 * 
	 * @param string $key
	 * @param mixed $storageOption
	 */
	public function updateStorageOption($key, $storageOption);
	
	/**
	 * Update storage type (shortcut)
	 *
	 * @param mixed		$value
	 * @see self::$_storageType
	 */
	public function updateStorageType($value); 
	
	/**
	 * Return encoding
	 *
	 * @return string
	 */
	public function getEncoding();
	
	/**
	 * Update encoding
	 *
	 * @param string $encoding
	 */
	public function updateEncoding($encoding);
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/interface/class.tx_mksearch_interface_IndexerField.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/interface/class.tx_mksearch_interface_IndexerField.php']);
}