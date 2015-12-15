<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_model
 *  @author Hannes Bochmann
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
 * benötigte Klassen einbinden
 */
require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_rnbase_model_base');

/**
 * Model für eine Facette
 * @package tx_mksearch
 * @subpackage tx_mksearch_model
 */
class tx_mksearch_model_Facet extends tx_rnbase_model_base  {
	const TYPE_FIELD = 'type_field';
	const TYPE_PIVOT = 'type_pivot';
	const TYPE_QUERY = 'type_query';
	const TYPE_RANGE = 'type_range';
	const TYPE_DATE = 'type_date';

	private $childs = array();

	/**
	 * Gibt ein Facet Model zurück
	 * @param string $field
	 * @param string $id
	 * @param mixed $label Das Label kann ein String, oder ein Array sein
	 * @param int $count
	 * @param boolean $head
	 * @return void
	 */
	public function __construct($field, $id, $label, $count, $head=false) {
		$this->record['field'] = $field;
		$this->record['id'] = $this->record['uid'] = $this->uid = $id;
		if(is_array($label)) {
			// Bei den gruppierten Facets gibt es nicht nur ein Label, sondern mehrere Datenfelder
			$this->record = array_merge($this->record, $label);
		}
		else
			$this->record['label'] = $label;
		$this->record['count'] = $count;
		$this->record['head'] = $head;
		$this->record['type'] = self::TYPE_FIELD; // Als default ein Field-Facet verwenden
	}
	/**
	 * Gibt die Art der Fassette zurück.
	 * @return string
	 */
	public function getFacetType() {
		return $this->record['type'];
	}
	/**
	 * Setzt die Art der Fassette
	 * @param string $type
	 */
	public function setFacetType($type) {
		$this->record['type'] = $type;
	}

	/**
	 * adds one ore more child facets
	 *
	 * @param mixed <multitype:tx_mksearch_model_Facet, tx_mksearch_model_Facet> $child
	 * @return tx_mksearch_model_Facet
	 */
	public function addChild($child) {
		if ($child instanceof tx_mksearch_model_Facet) {
			$this->childs[] = $child;
		} elseif(is_array($child)) {
			foreach ($child as $sub) {
				$this->addChild($sub);
			}
		}

		return $this;
	}
	/**
	 * returns all childs a child facet
	 *
	 * @param array <multitype:tx_mksearch_model_Facet, tx_mksearch_model_Facet> $child
	 * @return tx_mksearch_model_Facet
	 */
	public function setChilds(array $childs) {
		$this->childs = array();
		$this->addChild($childs);

		return $this;
	}
	/**
	 * returns all childs a child facet
	 *
	 * @return multitype:tx_mksearch_model_Facet $child
	 */
	public function getChilds() {
		return $this->childs;
	}
	/**
	 * there are childs?
	 *
	 * @return boolean
	 */
	public function hasChilds() {
		return !empty($this->childs);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/model/class.tx_mksearch_model_Facet.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/model/class.tx_mksearch_model_Facet.php']);
}
