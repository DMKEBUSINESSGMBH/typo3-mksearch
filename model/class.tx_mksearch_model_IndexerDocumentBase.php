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

require_once t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php';
tx_rnbase::load('tx_mksearch_interface_IndexerDocument');

/**
 * Generic class for indexer documents
 */
class tx_mksearch_model_IndexerDocumentBase implements tx_mksearch_interface_IndexerDocument {
	
	/**
	 * Extension key of indexed data
	 *
	 * @var tx_mksearch_interface_IndexerField
	 */
	private $extKey=null;
	
	/**
	 * Content type of indexed data
	 *
	 * @var tx_mksearch_interface_IndexerField
	 */
	private $contentType=null;
	
	/**
	 * UID field
	 *
	 * @var tx_mksearch_interface_IndexerField
	 */
	private $uid=null;

	/**
	 * deleted flag
	 *
	 * @var boolean
	 */
	private $deleted=false;

	/**
	 * Indexer field class name
	 *
	 * @var string
	 */
	private $fieldClass = null;
	
	/**
	 * All content fields (except primary key fields) of the indexer document
	 *
	 * @var array[tx_mksearch_interface_IndexerField]
	 */
	private $data = array();
	
	/**
	 * Factory for getting a new field object instance
	 * 
	 * @param mixed		$value					Either a scalar or an array value. Possibly not supported by every implementation!
	 * @param mixed		$storageOptionsOrType	Array (@see self::$_storageOptions) OR short cut string (@see self::$_storageType) 
	 * @param string	$boost					Boost of that $value
	 * @param string	$dataType				Data type of $value (@see self::$_dataType)
	 * @param string	$encoding
	 *
	 * @return tx_mksearch_interface_IndexerField
	 * 
	 */
	protected function getFieldInstance($value, $storageOptionsOrType, $boost=1.0, $dataType=null, $encoding=null) {
		return tx_rnbase::makeInstance($this->fieldClass, $value, $storageOptionsOrType, $boost, $dataType, $encoding);
	}
	
	/***********************************
	 * Basic functions
	 ***********************************/
	
	/**
	 * Constructor
	 * 
	 * Instantiate a new indexer document by defining the document's primary key
	 * consisting of $extKey, $contentType, and $uid.
	 * 
	 * @param string	$extKey			Key of the extension the indexed data belongs to
	 * @param string	$contentType	Name of content type the indexed data represents
	 * @param string	$fieldClass		Indexer field class name to be instantiated for each indexer field (must implement tx_mksearch_interface_IndexerField!)
	 * @return void
	 */
	public function __construct($extKey, $contentType, $fieldClass='tx_mksearch_model_IndexerFieldBase') {
		$this->fieldClass = $fieldClass;
		$this->extKey = $this->getFieldInstance($extKey, 'keyword');
		
		if (!$this->extKey instanceof tx_mksearch_interface_IndexerField)
			throw new Exception('tx_mksearch_model_IndexerDocumentBase->__construct(): Given class in $fieldClass must implement tx_mksearch_interface_IndexerField!');
			
		$this->contentType = $this->getFieldInstance($contentType, 'keyword');
	}

	/**
	 * Set uid
	 * 
	 * Setting the uid is not possible on instantiating since
	 * instantiating takes place PRIOR to actually collecting
	 * records to be indexed!
	 *
	 * @param int $uid
	 */
	public function setUid($uid) {
		$this->uid = $this->getFieldInstance($uid, 'keyword', 1.0, 'int');
	}
	
	/**
	 * Add field to indexer document
	 * 
	 * @param string	$key Field name
	 * @param string	$data
	 * @param string	$storageOptionsOrType -> @see tx_mksearch_model_IndexerFieldBase::$_storageOptions and tx_mksearch_model_IndexerFieldBase::$_storageType
	 * @param float		$boost
	 * @param string	$dataType
	 * @param string	$encoding=null
	 * @return void
	 */
	public function addField($key, $data, $storageOptionsOrType='keyword', $boost=1.0, $dataType=null, $encoding=null) {
		$this->data[$key] = $this->getFieldInstance($data, $storageOptionsOrType, $boost, $dataType, $encoding);
	}
	
	/**
	 * Return primary key fields of index document
	 * 
	 * @return array[
	 * 		'extKey' => tx_mksearch_interface_IndexerField,
	 *		'contentType'	=> tx_mksearch_interface_IndexerField,
	 *		'uid'			=> tx_mksearch_interface_IndexerField
	 * 		]
	 */
	public function getPrimaryKey($flat = false) {
		if (empty($this->uid)) 
			throw new Exception('tx_mksearch_model_IndexerDocumentBase->getPrimaryKey(): uid not yet set!');

		return !$flat ? array('extKey' => $this->extKey,'contentType' => $this->contentType,'uid' => $this->uid,) :
			 $this->extKey->getValue().':' .$this->contentType->getValue().':' .$this->uid->getValue();
	}
	public function __toString() {
		$ret = $this->extKey->getValue().':' .$this->contentType->getValue().':' . (!empty($this->uid)? $this->uid->getValue() : 'undefined');
		return $ret;
	}
	/**
	 * Return data of indexer document
	 * 
	 * @return array[tx_mksearch_interface_IndexerField]
	 */
	public function getData() {
		return $this->data;
	}
	/**
	 * (non-PHPdoc)
	 * @see tx_mksearch_interface_IndexerDocument::getSECommands()
	 */
	public function getSECommands() {
		return $this->secommands;
	}
	/**
	 * (non-PHPdoc)
	 * @see tx_mksearch_interface_IndexerDocument::addSECommand()
	 */
	public function addSECommand($command, $options) {
		if(!is_array($this->secommands))
			$this->secommands = array();
		$this->secommands[$command] = $options;
	}
	/***********************************
	 * Additional functions
	 ***********************************/

	/**
	 * Set title
	 * 
	 * Shortcut for setting a 'title' field as indexed and stored text.
	 * 
	 * @param string	$title
	 * @param string	$encoding='utf-8'
	 * @return void
	 */
	public function setTitle($title, $encoding='utf-8') {
		$this->data['title'] = $this->getFieldInstance($title, 'text', 1.0, 'string', $encoding);
	}

	/**
	 * Set abstract
	 * 
	 * Shortcut for setting an 'abstract' field as UNINDEXED text.
	 * 
	 * The abstract of a document just stores data, which is basically used
	 * for display on textual search results page, but is however NOT taken into account
	 * in terms of indexing!
	 * 
	 * @param string	$abstract
	 * @param int		$length		Cut abstract to that length - if 0, no cut takes place
	 * @param bool		$wordCut	Cut at last full word
	 * @param string	$encoding='utf-8'
	 * @return void
	 */
	public function setAbstract($abstract, $length=null, $wordCut=true, $encoding='utf-8') {
		if ($length) {
			// @TODO implement wordCut
			$abstract = substr($abstract, 0, $length);
		}
		$this->data['abstract'] = $this->getFieldInstance($abstract, 'unindexed', 1.0, 'string', $encoding);
	}
	
	/**
	 * Return maximum allowed length of abstract text
	 *
	 * @todo make configurable somehow - is this class the correct place for this function at all? 
	 * @return int Max. length of abstract as defined in mksearch extension config parameter abstractMaxLength_[your extkey]_[your content type]
	 */
	public function getMaxAbstractLength() {
		return 200;
//		tx_rn_base::load('tx_rnbase_configurations');
//		return tx_rnbase_configurations::getExtensionCfgValue(
//				'mksearch', 
//				'abstractMaxLength_'.$this->extKey->getValue().'_'.$this->contentType->getValue()
//				);
	}
	
	/**
	 * Set content of indexed document
	 * 
	 * Shortcut for setting a 'content' field as indexed, but UNSTORED text.
	 * 
	 * This is used for indexing large textual data which does not need
	 * to be stored for being returned within the search results.
	 * 
	 * @param string	$content
	 * @param string	$encoding='utf-8'
	 * @return void
	 */
	public function setContent($content, $encoding='utf-8') {
		$this->data['content'] = $this->getFieldInstance($content, 'unstored', 1.0, 'text', $encoding);
	}
	
	/**
	 * Set timestamp
	 * 
	 * Shortcut for setting a 'tstamp' field as indexed and stored keyword.
	 * 
	 * @param $title
	 * @return void
	 */
	public function setTimestamp($tstamp) {
		$this->data['tstamp'] = $this->getFieldInstance(intval($tstamp), 'keyword', 1.0, 'int');
	}
	
	/**
	 * Set FE user groups
	 * 
	 * Provide the actually resulting FE user groups which can differ
	 * from the groups explicitely set for this record - e.g. caused by
	 * superordinated records with the flag "Include subpages".
	 * 
	 * Calling this method is mandatory.
	 * The field must NOT be empty to enable
	 * search for anonymous users!
	 * 
	 * @param array|csv $fe_groups
	 * @return void
	 */
	public function setFeGroups($fe_groups=array(0)) {
		if (!is_array($fe_groups)) 
			$fe_groups = t3lib_div::trimExplode(',', $fe_groups);
		$this->data['fe_groups'] = $this->getFieldInstance($fe_groups, 'keyword', 1.0, 'int');
	}

	/**
	 * (non-PHPdoc)
	 * @see tx_mksearch_interface_IndexerDocument::setDeleted()
	 */
	public function setDeleted($deleted) {
		$this->deleted = $deleted;
	}
	/**
	 * (non-PHPdoc)
	 * @see tx_mksearch_interface_IndexerDocument::getDeleted()
	 */
	public function getDeleted() {
		return $this->deleted;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/model/class.tx_mksearch_model_IndexerDocumentBase.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/model/class.tx_mksearch_model_IndexerDocumentBase.php']);
}