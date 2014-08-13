<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2014 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
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

require_once t3lib_extMgm::extPath('rn_base', 'class.tx_rnbase.php');

/**
 * Bei Facetten von Lucene oder Solr ist es nicht Möglich,
 * sich Key Value paare liefern zu lassen.
 * Dies kann allerdings notwendig sein,
 * wenn der Wert für die Filterung "field_uid" eindeutig dem wert
 * für die ausgabe zugeordnetw erden muss "field_title".
 *
 * Wir behelfen uns, indem wir die Werte zusammenführen
 * und in einem Feld indizieren.
 *
 * Das zusammenführen und auseinandernehmen, erledigt diese Klasse für uns!
 *
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_util
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_util_KeyValueFacet {

	/**
	 *
	 * @var tx_mksearch_util_KeyValueFacet|NULL
	 */
	private static $defaultInstance = NULL;

	/**
	 *
	 * @var string
	 */
	private $facetDelimiter = '<[DFS]>';

	/**
	 *
	 * @param string $delimiter
	 */
	public function __construct($delimiter = NULL) {
		if (!empty($delimiter)) {
			$this->facetDelimiter = $delimiter;
		}
	}

	/**
	 * Liefert eine instanz dieser klasse.
	 * Bei defaulteinstellungen bleibt es ein sigelton.
	 *
	 * @param string $delimiter
	 * @return tx_mksearch_util_KeyValueFacet
	 */
	public static function getInstance($delimiter = NULL) {
		$instance = self::$defaultInstance && $delimiter === NULL
			? self::$defaultInstance
			: tx_rnbase::makeInstance(
				'tx_mksearch_util_KeyValueFacet',
				$delimiter
			)
		;
		if ($delimiter === NULL) {
			self::$defaultInstance = $instance;
		}
		return $instance;
	}

	/**
	 *
	 * @param string $value
	 * @param string $sorting
	 * @return string
	 */
	public function buildFacetValue($key, $value, $sorting = NULL) {
		$builded = $key . $this->facetDelimiter . $value;
		if ($sorting !== NULL) {
			$builded .= $this->facetDelimiter . $sorting;
		}
		return $builded;
	}

	/**
	 * Prüft, ob es sich bei dem Wert um einen zusammengebauten handelt
	 *
	 * @param string$value
	 */
	public function checkValue($value) {
		return strpos($value, $this->facetDelimiter) !== FALSE;
	}

	/**
	 *
	 * @param string $value
	 * @return array ($sorting | $value | $sorting[optional] )
	 */
	public function explodeFacetValue($value) {
		$exploded = t3lib_div::trimExplode($this->facetDelimiter, $value);
		return array(
			'key' => array_shift($exploded),
			'value' => array_shift($exploded),
			'sorting' => array_shift($exploded),
		);
	}

	/**
	 *
	 * @param array $values
	 * @return array
	 */
	public function explodeFacetValues(array $values) {
		$extracted = array();
		foreach ($values as $key => $value) {
			$exploded = $this->explodeFacetValue($value);
			$extracted[] = $exploded;
		}
		return $this->sortExplodedFacetValues($extracted);
	}

	/**
	 *
	 * @param string $value
	 * @return string
	 */
	public function extractFacetValue($value) {
		$exploded = $this->explodeFacetValue($value);
		return $exploded['value'];
	}

	/**
	 *
	 * @param array $values
	 * @return array
	 */
	public function extractFacetValues(array $values) {
		$extracted = array();
		$exploded = $this->explodeFacetValues($values);
		foreach ($exploded as $value) {
			$extracted[$value['key']] = $value['value'];
		}
		return $extracted;
	}

	/**
	 *
	 * @TODO: implement sorting on the sorting key
	 *
	 * @param array $exploded
	 * @return array
	 */
	protected function sortExplodedFacetValues(array $exploded) {
		// foreach ($exploded as $values) {
		// 	$values['sorting'];
		// }
		return $exploded;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_KeyValueFacet.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_KeyValueFacet.php']);
}