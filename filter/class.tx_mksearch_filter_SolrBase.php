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
tx_rnbase::load('tx_mksearch_util_Misc');
tx_rnbase::load('tx_rnbase_filter_BaseFilter');
tx_rnbase::load('tx_rnbase_util_ListBuilderInfo');

/**
 * Der Filter liest seine Konfiguration passend zum Typ des Solr RequestHandlers. Der Typ
 * ist entweder "default" oder "dismax". Entsprechend baut sich auch die Typoscript-Konfiguration
 * auf:
 * searchsolr.filter.default.
 * searchsolr.filter.dismax.
 *
 * Es gibt Optionen, die für beide Requesttypen identisch sind. Als Beispiel seien die Templates
 * genannt. Um diese Optionen zentral über das Flexform konfigurieren zu können, werden die
 * betroffenen Werte zusätzlich noch über den Pfad
 *
 * searchsolr.filter._overwrite.
 *
 * ermittelt und wenn vorhanden bevorzugt verwendet.
 *
 * @author René Nitzsche
 *
 */
class tx_mksearch_filter_SolrBase extends tx_rnbase_filter_BaseFilter {

	protected $sort = false;
	protected $sortOrder = 'asc';
	protected $confIdExtended = '';
	/**
	 * @return string
	 */
	protected function getConfIdOverwrite() {
		return $this->getConfId(false).'filter._overwrite.';
	}
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
			if (empty($this->confIdExtended)) {
				$this->confIdExtended = $this->getConfigurations()->get($confId.'filter.confid');
				$this->confIdExtended = 'filter.'.($this->confIdExtended ? $this->confIdExtended : 'default').'.';
			}
			$confId .= $this->confIdExtended;
		}
		return $confId;
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
	 * Liefert entweder die Konfiguration aus dem Flexform
	 * oder aus dem Typoscript.
	 *
	 * @param string $confId
	 * @return Ambigous <multitype:, unknown>
	 */
	protected function getConfValue($confId) {
		$configurations = $this->getConfigurations();
		// fallback: ursprünglich musste das configurationsobject als erster parameter übergeben werden.
		if (!is_scalar($confId)) {
			$configurations = $confId;
			$confId = func_get_arg(1);
		}
		// wert aus flexform holen (_override)
		$value = $configurations->get($this->getConfIdOverwrite().$confId);
		if(empty($value)) {
			// wert aus normalem ts holen.
			$value = $configurations->get($this->getConfId().$confId);
		}
		return $value;
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
		if(!($parameters->offsetExists('submit') || $this->getConfValue($configurations, 'force'))) {
			return false;
		}
		// request handler setzen
		if($requestHandler = $configurations->get($this->getConfId(false).'requestHandler')){
			$options['qt'] = $requestHandler;
		}

		// das Limit setzen
		$this->handleLimit($options);

		// suchstring beachten
		$this->handleTerm($fields, $parameters, $configurations, $confId);

		// facetten setzen beachten
		$this->handleFacet($options, $parameters, $configurations, $confId);

		// Operatoren berücksichtigen
		$this->handleOperators($fields, $options, $parameters, $configurations, $confId);

		// sortierung beachten
		$this->handleSorting($options, $parameters);

		// fq (filter query) beachten
		$this->handleFq($options, $parameters, $configurations, $confId);


		// Debug prüfen
		if($configurations->get($this->getConfIdOverwrite().'options.debug'))
			$options['debug'] = 1;
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
	protected function handleLimit(&$options) {
		// wir können das limit im flexform setzen
		$options['limit'] = (int) $this->getConfValue('options.limit');
		// wenn es auf -1 steht, entfernen wir das limit
		if ($options['limit'] < 0) {
			unset($options['limit']);
		}
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
	 *
	 * @param array $options
	 * @param tx_rnbase_IParameters $parameters
	 * @param tx_rnbase_configurations $configurations
	 * @param string $confId
	 * @return void
	 */
	protected function handleFacet(&$options, &$parameters, &$configurations, $confId) {
		$fields = $this->getConfValue('options.facet.fields');
		$fields = t3lib_div::trimExplode(',', $fields, TRUE);

		if (empty($fields)) {
			return;
		}

		// die felder für die facetierung setzen
		$options['facet.field'] = $fields;

		// facetten aktivieren, falls nicht explizit gesetzt.
		if (empty($options['facet']) || is_array($options['facet'])) {
			$options['facet'] = 'true';
		}
		// nur einträge, wleche zu einem ergebnis führen würden.
		if (empty($options['facet.mincount'])) {
			$options['facet.mincount'] = '1';
		}
		// immer nach der anzahl sortieren, die alternative wäre index.
		if (empty($options['facet.sort'])) {
			$options['facet.sort'] = 'count';
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
	protected function handleFq(&$options, &$parameters, &$configurations, $confId) {
		self::addFilterQuery($options, self::getFilterQueryForFeGroups());

		// die erlaubten felder holen
		$allowedFqParams = $configurations->getExploded($confId.'allowedFqParams');
		// wenn keine konfiguriert sind, nehmen wir automatisch die faccet fields
		if (empty($allowedFqParams) && !empty($options['facet.field'])) {
			$allowedFqParams = $options['facet.field'];
		}

		// parameter der filterquery prüfen
		// @see tx_mksearch_marker_Facet::prepareItem
		$fqParams = $parameters->get('fq');
		$fqParams = is_array($fqParams) ? $fqParams : array(trim($fqParams));
		//@todo die if blöcke in eigene funktionen auslagern
		if(!empty($fqParams)) {
			// FQ field, for single queries
			$sFqField = $configurations->get($confId.'fqField');
			foreach ($fqParams as $fqField => $fqValues) {
				$fieldOptions = array();
				$fqValues = is_array($fqValues) ? $fqValues : array(trim($fqValues));
				if (empty($fqValues)) {
					continue;
				}
				foreach ($fqValues as $fqNameValue => $fqValue) {
					$fqValue = trim($fqValue);
					if ($sFqField) {
						$fq = $sFqField . ':"' . tx_mksearch_util_Misc::sanitizeFq($fqValue) . '"';
					} else {
						// field value konstelation prüfen
						$fq = $this->parseFieldAndValue($fqValue, $allowedFqParams);
						if (empty($fq) && !empty($fqField)) {
							$fq  = tx_mksearch_util_Misc::sanitizeFq($fqField);
							$fq .= ':"' . tx_mksearch_util_Misc::sanitizeFq($fqValue) . '"';
						}
					}
					self::addFilterQuery($fieldOptions, $fq);
				}
				if (!empty($fieldOptions['fq'])) {
					$fqOperator = $configurations->get($confId . 'filterQuery.' . $fqField . '.operator');
					$fqOperator = $fqOperator == 'OR' ? 'OR' : 'AND';
					self::addFilterQuery($options, implode(' ' . $fqOperator . ' ', $fieldOptions['fq']));
				}
			}
		}
		if($sAddFq = trim($parameters->get('addfq'))) {
			// field value konstelation prüfen
			$sAddFq = $this->parseFieldAndValue($sAddFq, $allowedFqParams);
			self::addFilterQuery($options, $sAddFq);
		}
		if($sRemoveFq = trim($parameters->get('remfq'))) {
			$aFQ = isset($options['fq']) ? (is_array($options['fq']) ? $options['fq'] : array($options['fq'])) : array();
			// hier stekt nur der feldname drinn
			foreach ($aFQ as $iKey => $sFq) {
				list($sfield) = explode(':', $sFq);
				// wir löschend as feld
				if(in_array($sRemoveFq, $allowedFqParams) && $sRemoveFq == $sfield)
					unset($aFQ[$iKey]);
			}
			$options['fq'] = $aFQ;
		}
	}

	public static function getFilterQueryForFeGroups() {
		//wenigstens ein teil der query muss matchen. bei prüfen auf
		//nicht vorhandensein muss also noch auf ein feld geprüft werden
		//das garantiert existiert und damit ein match generiert.
		$filterQuery = '(-fe_group_mi:[* TO *] AND uid:[* TO *]) OR fe_group_mi:0';

		if(is_array($GLOBALS['TSFE']->fe_user->groupData['uid'])){
			$filterQueriesByFeGroup = array();
			foreach ($GLOBALS['TSFE']->fe_user->groupData['uid'] as $feGroup) {
				$filterQueriesByFeGroup[] = 'fe_group_mi:' . $feGroup.'';
			}

			if(!empty($filterQueriesByFeGroup))
				$filterQuery .= ' OR ' . join(' OR ', $filterQueriesByFeGroup);
		}

		return $filterQuery;
	}

	/**
	 * Fügt einen Parameter zu der FilterQuery hinzu
	 * @TODO: kann die fq nicht immer ein array sein!? dann könnten wir uns das sparen!
	 * @param array $options
	 * @param unknown_type $sFQ
	 */
	public static function addFilterQuery(array &$options, $sFQ) {
		if (empty($sFQ)) return ;
		// vorhandene fq berücksichtigen
		if (isset($options['fq'])) {
			// den neuen wert anhängen
			if (is_array($options['fq'])){
				$options['fq'][] = $sFQ;
			}
			// aus fq ein array machen und den neuen wert anhängen
			else {
				$options['fq'] = array($options['fq'], $sFQ);
			}
		}
		// fq schreiben
		else $options['fq'] = $sFQ;
	}

	/**
	 * Prüft den fq parameter auf richtigkeit.
	 * @param string $sFq
	 * @return array
	 */
	private function parseFieldAndValue($sFq, $allowedFqParams) {
		if (empty($sFq) || empty($allowedFqParams)) return '';

		// wir trennen den string auf!
		// field:value | field:"value"
		// nur kleinbuchstaben und unterstrich für feldnamen erlauben.
		$pattern  = '(?P<field>([a-z_]*))';
		// feld mit doppelpunkt vom wert getrennt.
		$pattern .= ':';
		// eventuelles anführungszeichen am anfang abschneiden.
		$pattern .= '(["]*)';
		// nur buchstaben, zahlen unterstrich, leerzeichen und punkt für wert erlauben.
		$pattern .= '(?P<value>([a-zA-Z0-9_. ]*))';
		tx_rnbase::load('tx_mksearch_util_Misc');
		if (
			// wir splitten den string auf!
			preg_match('/^'.$pattern.'/i', $sFq, $matches)
			// wurde das feld gefunden?
			&& isset($matches['field']) && !empty($matches['field'])
			// wurde der wert gefunden?
			&& isset($matches['value']) && !empty($matches['value'])
			// das feld muss erlaubt sein!
			&& in_array($matches['field'], $allowedFqParams)
			// werte reinigen
			&& ($field = tx_mksearch_util_Misc::sanitizeFq($matches['field']))
			&& ($term = tx_mksearch_util_Misc::sanitizeFq($matches['value']))
		) {
			// fq wieder zusammensetzen
			$sFq = $field . ':"'. $term .'"';
		}
		// kein feld und oder wert gefunden oder feld nicht erlaubt, wir lassen den qs leer!
		else $sFq = '';

		return $sFq;
	}

	/**
	 * Fügt die Sortierung zu dem Filter hinzu.
	 *
	 * @TODO: das klappt zurzeit nur bei einfacher sortierung!
	 *
	 * @param 	array 					$options
	 * @param 	tx_rnbase_IParameters 	$parameters
	 */
	protected function handleSorting(&$options, &$parameters) {
		// die parameter nach einer sortierung fragen
		$sort = trim($parameters->get('sort'));
		// wurden keine parameter gefunden, nutzen wir den default des filters
		$sort = $sort ? $sort : $this->sort;
		if($sort) {
			list($sort, $sortOrder) = explode(' ', $sort);
			// wenn order nicht mit gesetzt wurde, aus den parametern holen
			$sortOrder = $sortOrder ? $sortOrder : $parameters->get('sortorder');
			// den default order nutzen!
			$sortOrder = $sortOrder ? $sortOrder : $this->sortOrder;
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
		$this->parseSearchForm($template, $markArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, $marker);
		// Sortierung einfügen
		$this->parseSortFields($template, $markArray, $subpartArray, $wrappedSubpartArray, $formatter, $confId, $marker);

		$template = tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markArray, $subpartArray, $wrappedSubpartArray);
		return $template;
	}

	/**
	 * Treat search form
	 *
	 * @param string $template HTML template
	 * @param array $markArray
	 * @param array $subpartArray
	 * @param array $wrappedSubpartArray
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param string $confId
	 * @param string $marker
	 * @return string
	 */
	function parseSortFields($template, &$markArray, &$subpartArray, &$wrappedSubpartArray, &$formatter, $confId, $marker = 'FILTER') {
		$marker = 'SORT';
		$confId = $this->getConfId().'sort.';
		$configurations = $formatter->getConfigurations();

		// die felder für die sortierung stehen kommasepariert im ts
		$sortFields = $configurations->get($confId.'fields');
		$sortFields = $sortFields ? t3lib_div::trimExplode(',', $sortFields, true) : array();

		if(!empty($sortFields)) {
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
	 * @param array $subpartArray
	 * @param array $wrappedSubpartArray
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param string $confId
	 * @param string $marker
	 * @return string
	 */
	function parseSearchForm($template, &$markArray, &$subpartArray, &$wrappedSubpartArray, &$formatter, $confId, $marker = 'FILTER') {
		$markerName = 'SEARCH_FORM';
		if(!tx_rnbase_util_BaseMarker::containsMarker($template, $markerName))
			return $template;

		$confId = $this->getConfId();

		$configurations = $formatter->getConfigurations();
		tx_rnbase::load('tx_rnbase_util_Templates');
		$formTemplate = $this->getConfValue($configurations, 'template.file');
		$subpart = $this->getConfValue($configurations, 'template.subpart');
		$formTemplate = tx_rnbase_util_Templates::getSubpartFromFile($formTemplate, $subpart);

		if($formTemplate) {
			$link = $configurations->createLink();
			$link->initByTS($configurations, $confId.'template.links.action.', array());
			// Prepare some form data
			$paramArray = $this->getParameters()->getArrayCopy();
			$formData = $this->getParameters()->get('submit') ? $paramArray : $this->getFormData();
			$formData['action'] = $link->makeUrl(false);
			$formData['searchterm'] = htmlspecialchars( $this->getParameters()->get('term') );
			tx_rnbase::load('tx_rnbase_util_FormUtil');
			$formData['hiddenfields'] = tx_rnbase_util_FormUtil::getHiddenFieldsForUrlParams($formData['action']);

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