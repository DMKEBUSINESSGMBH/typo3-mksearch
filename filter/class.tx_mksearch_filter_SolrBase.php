<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 das Medienkombinat
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
tx_rnbase::load('tx_rnbase_filter_BaseFilter');
tx_rnbase::load('tx_rnbase_util_ListBuilderInfo');

class tx_mksearch_filter_SolrBase extends tx_rnbase_filter_BaseFilter {
	
	private $sort = false;
	private $sortOrder = 'asc';
	
	/**
	 * Liefert die ConfId für den Filter
	 * Die ID ist hier jeweils dismax oder default.
	 * Wird im flexform angegeben!
	 *
	 * @return string
	 */
	protected function getConfId($extended = true) {
		//$this->confId ist private, deswegen müssen wir deren methode aufrufen.
		$confId = parent::getConfId();
		if($extended) {
			$extended = $this->getConfigurations()->get($confId.'filter.confid');
			$extended = 'filter.'.($extended ? $extended : 'default').'.';
		} else $extended = '';
		return parent::getConfId().$extended;
	}
	
	/**
	 * Initialize filter
	 *
	 * @param array $fields
	 * @param array $options
	 */
	public function init(&$fields, &$options) {
		$confId = $this->getConfId();
		$fields = $this->getConfigurations()->get($confId.'fields.');
		tx_rnbase_util_SearchBase::setConfigOptions($options, $this->getConfigurations(),$confId.'options.');
		
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
		// Es muss ein Submit-Parameter im request liegen, damit der Filter greift
		if(!($parameters->offsetExists('submit') || $configurations->get($confId.'force'))) {
			return false;
		}
		
		// request handler setzen
		if($requestHandler = $configurations->get($this->getConfId(false).'requestHandler')){
			$options['qt'] = $requestHandler;
		}
		
		// suchstring beachten
		$this->handleTerm($fields, $parameters, $configurations, $confId);
		
		// Operatoren berücksichtigen
		$this->handleOperators($fields, $options, $parameters, $configurations, $confId);
		
		// sortierung beachten
		$this->handleSorting($options, $parameters);
		
		// fq (filter query) beachten
		$this->handleFq($options, $parameters, $configurations, $confId);
		
		return true;
	}

	/**
	 * Fügt den Suchstring zu dem Filter hinzu.
	 *
	 * @param 	array 						$fields
	 * @param 	array 						$options
	 * @param 	tx_rnbase_IParameters 		$parameters
	 * @param 	tx_rnbase_configurations 	$configurations
	 * @param 	string 						$confId
	 */
	protected function handleTerm(&$fields, &$parameters, &$configurations, $confId) {
		if($termTemplate = $fields['term']) {
			$templateMarker = tx_rnbase::makeInstance('tx_mksearch_marker_General');
			// Welche Extension-Parameter werden verwendet?
			$extQualifiers = $configurations->getKeyNames($confId.'params.');
			foreach($extQualifiers As $qualifier) {
				$params = $parameters->getAll($qualifier);
				$termTemplate = $templateMarker->parseTemplate($termTemplate, $params, $configurations->getFormatter(), $confId.'params.'.$qualifier.'.', 'PARAM_'.strtoupper($qualifier));
				//wenn keine params gesetzt sind, kommt der marker ungeparsed raus.
				//also ersetzen wir diesen prinzipiell am ende durch ein leeren string.
				$termTemplate = preg_replace('/(###PARAM_'.strtoupper($qualifier).'_)\w.*###/', '', $termTemplate);
			}
			$fields['term'] = $termTemplate;
		}
		// wenn der DisMaxRequestHandler genutzt muss der term leer sein, sonst wird nach *:* gesucht!
		elseif(!$configurations->get($confId.'useDisMax')) {
			$fields['term'] = '*:*';
		}
	}

	/**
	 * Handelt operatoren wie AND OR
	 *
	 * Die hauptaufgabe macht bereits fie userfunc in handleTerm.
	 * @see tx_mksearch_util_SearchBuilder::searchSolrOptions
	 *
	 * @param 	array 						$fields
	 * @param 	array 						$options
	 * @param 	tx_rnbase_IParameters 		$parameters
	 * @param 	tx_rnbase_configurations 	$configurations
	 * @param 	string 						$confId
	 */
	private function handleOperators(&$fields, &$options, &$parameters, &$configurations, $confId) {
		// wenn der DisMaxRequestHandler genutzt wird, müssen wir ggf. den term und die options ändern.
		if($configurations->get($confId.'useDisMax')) {
			tx_rnbase::load('tx_mksearch_util_SearchBuilder');
			tx_mksearch_util_SearchBuilder::handleMinShouldMatch($fields, $options, $parameters, $configurations, $confId);
			tx_mksearch_util_SearchBuilder::handleDismaxFuzzySearch($fields, $options, $parameters, $configurations, $confId);
		}
	}
	
	/**
	 * Fügt eine Filter Query (Einschränkung) zu dem Filter hinzu.
	 *
	 * @param 	array 					$options
	 * @param 	tx_rnbase_IParameters 	$parameters
	 * @param 	tx_rnbase_configurations 	$configurations
	 * @param 	string 						$confId
	 */
	protected function handleFq(&$options, &$parameters, &$configurations, $confId){
		if($sFq = trim($parameters->get('fq'))) {
			$sFqField = $configurations->get($confId.'fqField');
			$sFq = $sFqField ? $sFqField.':'.$sFq : $sFq;
			$options['fq'] = $sFq;
		}
	}

	/**
	 * Fügt die Sortierung zu dem Filter hinzu.
	 *
	 * @param 	array 					$options
	 * @param 	tx_rnbase_IParameters 	$parameters
	 */
	private function handleSorting(&$options, &$parameters){
		if($sort = trim($parameters->get('sort'))) {
			//@TODO: das klappt nur bei einfacher sortierung!
			list($sort, $sortOrder) = explode(' ', $sort);
			// wenn order nicht mit gesetzt wurde, aus den parametern holen
			$sortOrder = $sortOrder ? $sortOrder : $parameters->get('sortorder');
			// sicherstellen, das immer desc oder asc gesetzt ist
			$sortOrder = ($sortOrder == 'asc') ? 'asc' : 'desc';
			// wird beim parsetemplate benötigt
			$this->sort = $sort;
			$this->sortOrder = $sortOrder;
			// sort setzen
			$options['sort'] = $sort.' '.$sortOrder;
		}
	}

	/**
	 *
	 * @param string $template HTML template
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param string $confId
	 * @param string $marker
	 * @return string
	 */
	function parseTemplate($template, &$formatter, $confId, $marker = 'FILTER') {
				
		$markArray = $subpartArray  = $wrappedSubpartArray = array();
		
		// Formular einfügen
		$this->parseSearchForm($template, $markArray, $formatter, $confId, $marker);
		// Sortierung einfügen
		$this->parseSortFields($template, $markArray, $wrappedSubpartArray, $formatter, $confId, $marker);
		
		$template = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markArray, $subpartArray, $wrappedSubpartArray);
		return $template;
	}

	/**
	 * Treat search form
	 *
	 * @param string $template HTML template
	 * @param array $markArray
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param string $confId
	 * @param string $marker
	 * @return string
	 */
	function parseSortFields($template, &$markArray, &$wrappedSubpartArray, &$formatter, $confId, $marker = 'FILTER') {
		$marker = 'SORT';
		$confId = $this->getConfId().'sort.';
		$configurations = $formatter->getConfigurations();
		
		// die felder für die sortierung stehen kommasepariert im ts
		$sortFields = $configurations->get($confId.'fields');
		$sortFields = $sortFields ? t3lib_div::trimExplode(',', $sortFields, true) : array();
		
		if(count($sortFields)) {
			tx_rnbase::load('tx_rnbase_util_BaseMarker');
		  	$token = md5(microtime());
		  	$markOrders = array();
			foreach($sortFields as $field) {
				$isField = ($field == $this->sort);
				// sortOrder ausgeben
				$markOrders[$field.'_order'] = $isField ? $this->sortOrder : '';
				
				$fieldMarker = $marker.'_'.strtoupper($field).'_LINK';
				$makeLink = tx_rnbase_util_BaseMarker::containsMarker($template, $fieldMarker);
				$makeUrl = tx_rnbase_util_BaseMarker::containsMarker($template, $fieldMarker.'URL');
				// link generieren
				if($makeLink || $makeUrl) {
					// sortierungslinks ausgeben
					$params = array(
							'sort' => $field,
							'sortorder' => $isField && $this->sortOrder == 'asc' ? 'desc' : 'asc',
						);
					$link = $configurations->createLink();
					$link->label($token);
					$link->initByTS($configurations, $confId.'link.', $params);
					if($makeLink)
						$wrappedSubpartArray['###'.$fieldMarker.'###'] = explode($token, $link->makeTag());
					if($makeUrl)
						$markArray['###'.$fieldMarker.'URL###'] = $link->makeUrl(false);
				}
			}
			// die sortOrders parsen
			$markOrders = $formatter->getItemMarkerArrayWrapped($markOrders, $confId, 0,$marker.'_', array_keys($markOrders));
			$markArray = array_merge($markArray, $markOrders);
		}
	}
	
	/**
	 * Treat search form
	 *
	 * @param string $template HTML template
	 * @param array $markArray
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param string $confId
	 * @param string $marker
	 * @return string
	 */
	function parseSearchForm($template, &$markArray, &$formatter, $confId, $marker = 'FILTER') {
		$markerName = 'SEARCH_FORM';
		if(!tx_rnbase_util_BaseMarker::containsMarker($template, $markerName))
			return $template;

		$confId = $this->getConfId();
			
		$configurations = $formatter->getConfigurations();
		tx_rnbase::load('tx_rnbase_util_Templates');
		$formTemplate = $configurations->get($confId.'template.file');
		$subpart = $configurations->get($confId.'template.subpart');
		$formTemplate = tx_rnbase_util_Templates::getSubpartFromFile($formTemplate, $subpart);

		if($formTemplate) {
			$link = $configurations->createLink();
			$link->initByTS($configurations,$confId.'template.links.action.', array());
			// Prepare some form data
			$paramArray = $this->getParameters()->getArrayCopy();
			$formData = $this->getParameters()->get('submit') ? $paramArray : $this->getFormData();
			$formData['action'] = $link->makeUrl(false);
			$formData['searchterm'] = htmlspecialchars( $this->getParameters()->get('term') );
			
			$combinations = array('none', 'free', 'or', 'and', 'exact');
			$currentCombination = $this->getParameters()->get('combination');
			$currentCombination = $currentCombination ? $currentCombination : 'none';
			foreach($combinations as $combination)
				// wenn anders benötigt, via ts ändern werden
				$formData['combination_'.$combination] = ($combination == $currentCombination) ? ' checked="checked"' : '';
			
			$options = array('fuzzy');
			$currentOptions = $this->getParameters()->get('options');
			foreach($options as $option)
				// wenn anders benötigt, via ts ändern werden
				$formData['option_'.$option] = empty($currentOptions[$option]) ? '' : ' checked="checked"' ;
				
			$templateMarker = tx_rnbase::makeInstance('tx_mksearch_marker_General');
			$formTemplate = $templateMarker->parseTemplate($formTemplate, $formData, $formatter, $confId.'form.', 'FORM');
		}
		
		$markArray['###'.$markerName.'###'] = $formTemplate;
		return $template;
	}

	private function getFormData() {
		return array();
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/filter/class.tx_mksearch_filter_SolrBase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/filter/class.tx_mksearch_filter_SolrBase.php']);
}