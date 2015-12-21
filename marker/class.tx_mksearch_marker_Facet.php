<?php
/**
 * 	@package tx_mksearch
 *  @subpackage tx_mksearch_marker
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
	 * @param string $template HTML template
	 * @param tx_mksearch_model_Facet $item search hit
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param string $confId path of typoscript configuration
	 * @param string $marker name of marker
	 * @return string readily parsed template
	 */
	public function parseTemplate($template, &$item, &$formatter, $confId, $marker = 'ITEM') {
		$out = parent::parseTemplate($template, $item, $formatter, $confId, $marker);

		if (self::containsMarker($out, $marker . '_CHILDS')) {
			$childs = array();
			if (
				$item instanceof tx_mksearch_model_Facet
				&& $item->hasChilds()
			) {
				$childs = $item->getChilds();
			}

			/* @var $listBuilder tx_rnbase_util_ListBuilder */
			$listBuilder = tx_rnbase::makeInstance('tx_rnbase_util_ListBuilder');
			$out = $listBuilder->render(
				$childs,
				FALSE,
				$out,
				'tx_mksearch_marker_Facet',
				$confId . 'child.',
				$marker . '_CHILD',
				$formatter
			);
		}

		return $out;
	}

	/**
	 * Führt vor dem parsen Änderungen am Model durch.
	 *
	 * @param tx_rnbase_model_base &$item
	 * @param tx_rnbase_configurations &$configurations
	 * @param string &$confId
	 * @return void
	 */
	protected function prepareItem(
			tx_rnbase_model_base &$item,
			tx_rnbase_configurations &$configurations,
			$confId
	) {
		parent::prepareItem($item, $configurations, $confId);

		// wir setzen automatisch den status und den formularnamen!
		// aber nur, wenn wir ein label (facettenwert) haben
		if ($item->getLabel()) {
			$field = $item->getField();
			$value = $item->getId();

			// #4 fixed field name for query facets
			if($item->getFacetType() == tx_mksearch_model_Facet::TYPE_QUERY) {
				$field = tx_mksearch_model_Facet::TYPE_QUERY;
			}

			// check fieldmapping:
			$fieldMap = $configurations->get($confId . 'mapping.field.' . $field);
			$field = empty($fieldMap) ? $field : $fieldMap;

			// den Formularnamen setzen
			if (!isset($item->record['form_name'])) {
				$formName  = $configurations->getQualifier();
				$formName .= '[fq]';
				$formName .= '[' . $field . ']';
				$formName .= '[' . $value . ']';
				$item->record['form_name'] = $formName;
			}

			// wir setzen den wert direkt fertig für den filter zusammen
			if (!isset($item->record['form_value'])) {
				$item->record['form_value'] = $field . ':' . $value;
			}

			// wir setzen den wert direkt fertig für den filter zusammen
			if (!isset($item->record['form_id'])) {
				$formId  = $configurations->getQualifier();
				$formId .= '-' . $field;
				$formId .= '-' . $value;
				$formId = preg_replace('/[^\da-z-]/i', '', strtolower($formId));
				$item->record['form_id'] = $formId;
			}

			// deaktiviert?
			if (!isset($item->record['disabled'])) {
				$item->record['disabled'] = (int) $item->record['count'] > 0 ? 0 : 1;
			}

			// den status setzen
			if (!isset($item->record['active'])) {
				$params = $configurations->getParameters()->get('fq');
				$params = empty($params[$field]) ? array() : $params[$field];
				$item->record['active'] = empty($params[$value]) ? 0 : 1;
			}
		}
	}

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
		$configurations = $formatter->getConfigurations();
		if(!$configurations->get($confId.'links.show.excludeFieldName'))
					$sFq = $item->record['field'].':'.$sFq;

		//jetzt noch den fq parameter wert in den record schreiben
		//damit er im TS zur Verfügung steht
		$item->record[$configurations->get($confId.'links.show.paramField')] = $sFq;

		parent::prepareLinks($item, $marker, $markerArray, $subpartArray, $wrappedSubpartArray, $confId, $formatter, $template);

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

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/marker/class.tx_mksearch_marker_CorePage.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/marker/class.tx_mksearch_marker_CorePage.php']);
}
