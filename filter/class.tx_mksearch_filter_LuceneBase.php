<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2014 DMK E-BUSINESS GmbH
 *  All rights reserved
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 ***************************************************************/

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');

tx_rnbase::load('tx_rnbase_util_SearchBase');
tx_rnbase::load('tx_rnbase_util_ListBuilderInfo');
tx_rnbase::load('tx_rnbase_filter_BaseFilter');
tx_rnbase::load('tx_mksearch_util_Filter');

/**
 * Dieser Filter verarbeitet Anfragen für Lucene
 * @author rene
 */
class tx_mksearch_filter_LuceneBase extends tx_rnbase_filter_BaseFilter implements ListBuilderInfo {

	static private $formData = array();

	/**
	 * Store info if a search request was submitted - needed for empty list message
	 * @var bool
	 */
	private $isSearch = false;

	/**
	 * Initialize filter
	 *
	 * @param array $fields
	 * @param array $options
	 */
	public function init(&$fields, &$options) {
		$confId = $this->getConfId();
		$fields = $this->getConfigurations()->get($confId.'filter.fields.');
		tx_rnbase_util_SearchBase::setConfigOptions($options, $this->getConfigurations(),$confId.'filter.options.');
		return $this->initFilter($fields, $options, $this->getParameters(), $this->getConfigurations(), $confId);
	}

	/**
	 * Filter for search form
	 *
	 * @param array $fields
	 * @param array $options
	 * @param tx_rnbase_parameters $parameters
	 * @param tx_rnbase_configurations $configurations
	 * @param string $confId
	 * @return bool	Should subsequent query be executed at all?
	 *
	 */
	protected function initFilter(&$fields, &$options, &$parameters, &$configurations, $confId) {
		if(
			$configurations->get($confId . 'formOnly') ||
			!($parameters->offsetExists('submit') ||
			$configurations->get($confId . 'forceSearch'))
		) {
			return false;
		}

		$this->isSearch = true;

		$options = $this->setFeGroupsToOptions($options);

		if($termTemplate = $fields['term']) {
			$options['rawFormat'] = true;
			$this->fixMinimalPrefixLengthInZend();

			$termTemplate = tx_mksearch_util_Filter::parseTermTemplate(
				$termTemplate, $parameters, $configurations, $confId . 'filter.'
			);

			$fields['term'] = trim($termTemplate);
		}

		return true;
	}

	/**
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	protected function setFeGroupsToOptions(array $options) {
		$options['fe_groups'] = $GLOBALS['TSFE']->fe_user->groupData['uid'];

		return $options;
	}

	/**
	 * sonst kommt es in Zend_Search_Lucene_Search_Query_Wildcard::rewrite
	 * zu einer exception bei führenden wildcards.
	 * allgemein sollte das auch im formular validiert werden wenn gewünscht.
	 */
	protected function fixMinimalPrefixLengthInZend() {
		tx_rnbase::makeInstance('tx_mksearch_service_engine_ZendLucene');
		Zend_Search_Lucene_Search_Query_Wildcard::setMinPrefixLength(0);
	}

	/**
	 * Treat search form
	 *
	 * @param string $template HTML template
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param string $confId
	 * @param string $marker
	 * @return string
	 */
	function parseTemplate($template, &$formatter, $confId, $marker = 'FILTER') {
		$configurations = $this->getConfigurations();
		// Aufpassen mit der confId. Der Listbuilder bekommt view.hit. übergeben,
		// Der Filter ist aber in view.filter. konfiguriert. Darum die confId hier umbiegen:
		$confId = $this->getConfId().'filter.';
		$conf = $configurations->get($confId);

		// Form template required?
		if(tx_rnbase_util_BaseMarker::containsMarker($template, $conf['config']['marker'])) {
			// Get template from TS
			$templateCode = $configurations->getCObj()->fileResource($conf['config.']['template']);
			if($templateCode) {
				// Get subpart from TS
				$subpartName = $conf['config.']['subpart'];
				$typeTemplate = $configurations->getCObj()->getSubpart($templateCode, '###'.$subpartName.'###');
				if($typeTemplate) {
					$parameters = $this->getParameters();
					$paramArray = $parameters->getArrayCopy();

					$link = $configurations->createLink();
					$link->initByTS($configurations,$confId.'config.links.action.', array());
					// Prepare some form data
					$formData = $parameters->get('submit') ? $paramArray : self::$formData;
					$formData['action'] = $link->makeUrl(false);
					$formData['searchcount'] = $configurations->getViewData()->offsetGet('searchcount');
					tx_rnbase::load('tx_rnbase_util_FormUtil');
					$formData['hiddenfields'] = tx_rnbase_util_FormUtil::getHiddenFieldsForUrlParams($formData['action']);
					$this->prepareFormFields($formData, $parameters);

					$templateMarker = tx_rnbase::makeInstance('tx_mksearch_marker_General');
					$formTxt = $templateMarker->parseTemplate($typeTemplate, $formData, $formatter, $confId.'form.', 'FORM');
				} else {
					$formTxt = '<!-- NO SUBPART '.$subpartName.' FOUND -->';
				}
			} else {
				$formTxt = '<!-- NO FORM TEMPLATE FOUND: '.$confId.'.template'.' -->';
			}
			// Insert form template into main template
			$template = str_replace('###'.$conf['config.']['marker'].'###', $formTxt, $template);
		}
		return $template;
	}
	/**
	 * Werte für Formularfelder aufbereiten. Daten aus dem Request übernehmen und wieder füllen.
	 * @param array $formData
	 * @param tx_rnbase_parameters $parameters
	 */
	protected function prepareFormFields(&$formData, $parameters) {
		$formData['searchterm'] = $parameters->get('term');
		$values = array('or', 'and', 'exact');
		$options = $parameters->get('options');
		if($options['combination']) {
			foreach ($values as $value) {
				$formData['combination_'.$value.'_selected'] = $options['combination'] == $value ? 'checked=checked' : '';
			}
		}
		else {
			// Default
			$formData['combination_or_selected'] = 'checked=checked';
		}
		$values = array('standard', 'advanced');
		if($options['mode']) {
			foreach ($values as $value) {
				$formData['mode_'.$value.'_selected'] = $options['mode'] == $value ? 'checked=checked' : '';
			}
		}
		else {
			// Default
			$formData['mode_standard_selected'] = 'checked=checked';
		}

		$formData = $this->fillFormDataWithRequiredFormFieldsIfNoSet(
			$formData, $parameters
		);
	}

	/**
	 * ist notwendig weil sonst die Marker, welche die Formulardaten
	 * enthalten ungeparsed rauskommen, falls das Formular noch
	 * nicht abgeschickt wurde
	 *
	 * @param array $formData
	 * @param tx_rnbase_parameters $parameters
	 *
	 * @return array
	 */
	private function fillFormDataWithRequiredFormFieldsIfNoSet(
		array $formData, tx_rnbase_parameters $parameters
	) {
		$formFields = t3lib_div::trimExplode(
			',',
			$this->getConfigurations()->get($this->getConfId() . 'requiredFormFields')
		);

		foreach ($formFields as $formField) {
			if (!array_key_exists($formField, $formData)) {
				$formData[$formField] = '';
			}
		}

		return $formData;
	}

	/**
	 * Get a message string for empty list. This is an language string. The key is
	 * taken from ts-config: [item].listinfo.llkeyEmpty
	 *
	 * @param array_object $viewData
	 * @param tx_rnbase_configurations $configurations
	 * @return string
	 */
	function getEmptyListMessage($confId, &$viewData, &$configurations) {
		if ($this->isSearch) {
			$emptyMsg = $configurations->getLL($configurations->get($confId.'listinfo.llkeyEmpty'));
			if ($configurations->get($confId.'listinfo.form')) {
				//Puts a marker for the form if needed. The Form must be after the plugin.
				$GLOBALS[$configurations->get($confId.'listinfo.formclass')]['enabled']=1;
			}
			return $emptyMsg;
		}
		return '';
	}

	function setMarkerArrays(&$markerArray, &$subpartArray, &$wrappedSubpartArray) {}

	function getListMarkerInfo() {return null;}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/filter/class.tx_mksearch_filter_LuceneBase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/filter/class.tx_mksearch_filter_LuceneBase.php']);
}