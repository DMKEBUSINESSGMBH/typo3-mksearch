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

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_rnbase_util_SimpleMarker');

/**
 * Renders a search result
 */
class tx_mksearch_marker_Search extends tx_rnbase_util_SimpleMarker {

	/**
	 * @param string $template HTML template
	 * @param tx_mksearch_model_SearchHit $item
	 * @param tx_rnbase_util_FormatUtil $formatter Formatter to use
	 * @param string $confId Path in TS config of the item, e. g. 'search.hit.'
	 * @param string $marker name of marker, z.B. CLUB
	 *        Von diesem String hängen die entsprechenden weiteren Marker ab: ###CLUB_NAME###, ###COACH_ADDRESS_WEBSITE###
	 * @return String das geparste Template
	 */
	public function parseTemplate($template, &$item, &$formatter, $confId, $marker = 'SEARCHRESULT') {
		if(!is_object($item)) return '';

		$this->prepareHit($template, $item, $formatter, $confId, $marker);

		// Render extra info if needed...
		if($this->containsMarker($template, $marker.'_EXTRAINFO')) {
			$template = $this->addInfo($template, $item, $formatter, $confId.'extrainfo.', $marker.'_EXTRAINFO');
		}

		// Fill MarkerArray
		$unused = $this->findUnusedCols($item->record, $template, $marker);
		$markerArray = $formatter->getItemMarkerArrayWrapped($item->record, $confId, $unused, $marker.'_', $item->getColumnNames());

		// subparts erzeugen
		$subpartArray = $wrappedSubpartArray = array();
		$this->prepareSubparts($wrappedSubpartArray, $subpartArray, $template, $item, $formatter, $confId, $marker);

		$out = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray, $subpartArray, $wrappedSubpartArray);
		return $out;
	}

	/**
	 * @param string $template HTML template
	 * @param tx_mksearch_model_SearchHit $item
	 * @param tx_rnbase_util_FormatUtil $formatter Formatter to use
	 * @param string $confId Path in TS config of the item, e. g. 'search.hit.'
	 * @param string $marker name of marker
	 * @return void
	 */
	protected function prepareHit($template, &$item, &$formatter, $confId, $marker) {
		$configurations = $formatter->getConfigurations();
		$glue = $configurations->get($confId.'multiValuedGlue');
		if ($configurations->getBool($confId.'multiValuedGlue.noTrim')) {
			$splitter = $configurations->get($confId.'multiValuedGlue.noTrim.splitChar');
			$splitter = $splitter ? $splitter : '|';
			$glue = explode($splitter, $glue);
			$glue = $glue[1];
		}

		//wenn wir ein array haben, holen wir uns dazu eine
		//kommaseparierte Liste um damit einfach im FE arbeiten zu können
		foreach ($item->record as &$mValue){
		    if(is_array($mValue)) $mValue = implode($glue, $mValue);
		}
	}


	/**
	 * Diese Methode ersetzt im HTML-Template der Marker ###..._EXTRAINFO###. Es wird dafür nach einem konfigurierten
	 * Marker für den Typ des Logeintrages gesucht. Diesem wird dann ein passendes HTML-Template übergeben.
	 *
	 * @param string $template
	 * @param tx_t3users_models_log $item
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param string $confId
	 * @param string $markerPrefix
	 */
	protected function addInfo($template, &$item, $formatter, $confId, $markerPrefix) {

		$typeConfId = $confId . $item->record['extKey'].'.'.$item->record['contentType'].'.';
		$markerClass = $formatter->getConfigurations()->get($typeConfId.'markerClass');
		if($markerClass) {
			// Jetzt das Template laden
			$file = $formatter->getConfigurations()->get($typeConfId.'template');
			$templateCode = $formatter->getConfigurations()->getCObj()->fileResource($file);
			if($templateCode) {
				$subpartName = $formatter->getConfigurations()->get($typeConfId.'subpartName');
				if(!$subpartName) {
					$subpartName = strtoupper($item->record['extKey'].'_'.$item->record['contentType']);
				}
				$typeTemplate = tx_rnbase_util_Templates::getSubpart($templateCode,'###'.$subpartName.'###');
				if($typeTemplate) {
					$marker = tx_rnbase::makeInstance($markerClass);
					$extraInfo = $marker->parseTemplate($typeTemplate, $item, $formatter, $typeConfId.'hit.');
				}
				else {
					$extraInfo = '<!-- NO SUBPART '.$subpartName.' FOUND -->';
//					return; // return? dann kommt die info im fe ja nie an!
				}
			}
			else {
				$extraInfo = '<!-- NO INFO-TEMPLATE FOUND: '.$typeConfId.'template'.' -->';
//				return; // return? dann kommt die info im fe ja nie an!
			}
		} else {
			$extraInfo = '<!-- NO MARKER-CLASS FOUND: '.$typeConfId.'markerClass'.' -->';
		}

		$markerArray = array('###'.$markerPrefix.'###' => $extraInfo);
		$template = tx_rnbase_util_Templates::substituteMarkerArrayCached($template,$markerArray);

		return $template;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/marker/class.tx_mksearch_marker_Search.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/marker/class.tx_mksearch_marker_Search.php']);
}