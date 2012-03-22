<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_marker
 *  @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2011 Hannes Bochmann <hannes.bochmann@das-medienkombinat.de>
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
tx_rnbase::load('tx_mksearch_marker_SearchResultSimple');

/**
 * Marker class for base facets
 *
 * @author Hannes Bochmann
 * @package tx_mksearch
 * @subpackage tx_mksearch_marker
 */
class tx_mksearch_marker_Facet extends tx_mksearch_marker_SearchResultSimple {

	/**
	 * Links vorbereiten
	 *
	 * @param tx_a4base_models_organisation $item
	 * @param string $marker
	 * @param array $markerArray
	 * @param array $wrappedSubpartArray
	 * @param string $confId
	 * @param tx_rnbase_util_FormatUtil $formatter
	 */
	public function prepareLinks(&$item, $marker, &$markerArray, &$subpartArray, &$wrappedSubpartArray, $confId, &$formatter, $template) {
		//schränkt nach dem facettierten feld und dessen aktullen wert ein
		//z.B. wird nach contentType facettiert. Dann sieht der Link bei tt_content
		//so aus: mksearch[fq]=contentType:tt_content
		
		$sFq = $item->record['id'];
		
		//ACHTUNG: im Live Einsatz sollte das Feld nicht im Link stehen sondern nur der Wert.
		//Das Feld sollte dann erst im Filter hinzugefügt werden. In der TS Config sollte
		//dazu facet.links.show.excludeFieldName auf 1 stehen!!!
		if(!$formatter->getConfigurations()->get($confId.'links.show.excludeFieldName'))
					$sFq = $item->record['field'].':'.$sFq;
		$this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, 'show', $marker, array('fq' => $sFq), $template);
		
		// folgende links snind nur sinvoll, wenn die suchparameter gecached werden!
		// add fq link, um einen filter zur suche hinzuzufügen
		// remove fq link, um einen filter zur suche hinzuzufügen
		// der parameter wird im format "mksearch[addfw]=title:home" übergeben.
		// der filter muss sich dann darum kümmern, die parameter (field:value) auseinanderzu nehmen
		// und in die query einzubauen
		$this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, 'add', $marker, array('NK_addfq' => $item->record['field'].':'.$item->record['id']), $template);
		$this->initLink($markerArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, 'remove', $marker, array('NK_remfq' => $item->record['field']), $template);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/marker/class.tx_mksearch_marker_CorePage.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/marker/class.tx_mksearch_marker_CorePage.php']);
}