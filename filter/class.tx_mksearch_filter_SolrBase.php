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


tx_rnbase::load('tx_rnbase_util_SearchBase');
tx_rnbase::load('tx_mksearch_util_Misc');
tx_rnbase::load('tx_rnbase_filter_BaseFilter');
tx_rnbase::load('tx_rnbase_util_ListBuilderInfo');
tx_rnbase::load('tx_mksearch_util_Filter');
tx_rnbase::load('tx_mksearch_service_indexer_core_Config');
tx_rnbase::load('tx_mksearch_model_Facet');


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

	protected $confIdExtended = '';

	/**
	 *
	 * @var tx_mksearch_util_Filter
	 */
	protected $filterUtility;

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
		$this->handleSorting($options);

		// fq (filter query) beachten
		$this->handleFq($options, $parameters, $configurations, $confId);

		// umkreissuche prüfen
		$this->handleSpatial($fields, $options);

		// Debug prüfen
		if ($configurations->get($this->getConfIdOverwrite().'options.debug')) {
			$options['debug'] = 1;
		}

		return true;
	}



	/**
	 * Setzt die Anzahl der Treffer pro Seite
	 *
	 * @param 	array 						$options
	 * @param 	tx_rnbase_IParameters 	$parameters
	 * @param 	tx_rnbase_configurations $configurations
	 * @param 	string 						$confId
	 */
	protected function handleLimit(&$options) {
		$options['limit'] = $this->getFilterUtility()->getPageLimit(
				$this->getParameters(),
				$this->getConfigurations(),
				$this->getConfId(),
				(int) $this->getConfValue('options.limit'));
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
			$termTemplate = $this->getFilterUtility()->parseTermTemplate(
				$termTemplate, $parameters, $configurations, $confId
			);
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
		$fields = tx_rnbase_util_Strings::trimExplode(',', $fields, TRUE);

		if (empty($fields)) {
			return;
		}

		// die felder für die facetierung setzen
		$options['facet.field'] = $fields;

		// facetten aktivieren, falls nicht explizit gesetzt.
		if (empty($options['facet']) || is_array($options['facet'])) {
			$options['facet'] = 'true';
		}

		// nur einträge, welche zu einem ergebnis führen würden.
		if (empty($options['facet.mincount'])) {
			// typoscript auf mincount checken.
			$facetMinCountConfig = $this->getConfValue('options.facet.mincount');
			$options['facet.mincount'] = strlen($facetMinCountConfig)
				? (int) $facetMinCountConfig
				: '1'
			;
		}

		$sortFacetsFromConfiguration = $this->getConfValue('options.facet.sort');
		if ($sortFacetsFromConfiguration && empty($options['facet.sort'])) {
			$options['facet.sort'] = $sortFacetsFromConfiguration;
		}
		// als fallback immer nach der anzahl sortieren, die alternative wäre index.
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
	protected function handleOperators(&$fields, &$options, &$parameters, &$configurations, $confId) {
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

		// respect Root Page
		$this->handleFqForSiteRootPage($options, $configurations, $confId);

		// die erlaubten felder holen
		$allowedFqParams = $configurations->getExploded($confId.'allowedFqParams');
		// wenn keine konfiguriert sind, nehmen wir automatisch die facet fields
		if (empty($allowedFqParams) && !empty($options['facet.field'])) {
			$allowedFqParams = $options['facet.field'];
		}

		// parameter der filterquery prüfen
		// @see tx_mksearch_marker_Facet::prepareItem
		$fqParams = $parameters->get('fq');
		$fqParams = is_array($fqParams) ? $fqParams : ( trim($fqParams) ? array(trim($fqParams)) : array() );
		//@todo die if blöcke in eigene funktionen auslagern
		if(!empty($fqParams)) {
			// FQ field, for single queries
			// Das ist deprecated! Dadurch wäre nur ein festes Facet-Field möglich
			$sFqField = $configurations->get($confId.'fqField');
			foreach ($fqParams as $fqField => $fqValues) {
				$fieldOptions = array();
				$fqValues = is_array($fqValues) ? $fqValues : array(trim($fqValues));
				if (empty($fqValues)) {
					continue;
				}

				foreach ($fqValues as $fqName => $fqValue) {
					$fqValue = trim($fqValue);
					if ($sFqField) {
						// deprecated: sollte nicht mehr vorkommen
						$fq = $sFqField . ':"' . tx_mksearch_util_Misc::sanitizeFq($fqValue) . '"';
					} else {
						// Query-Facet prüfen
						if($fqField === tx_mksearch_model_Facet::TYPE_QUERY) {
							$fq = $this->buildFq4QueryFacet($fqName, $configurations, $confId);
						}
						else {
							// check field facets
							// field value konstellation prüfen
							$fq = $this->parseFieldAndValue($fqValue, $allowedFqParams);
							if (empty($fq) && !empty($fqField) && in_array($fqField, $allowedFqParams)) {
								$fq  = tx_mksearch_util_Misc::sanitizeFq($fqField);
								$fq .= ':"' . tx_mksearch_util_Misc::sanitizeFq($fqValue) . '"';
							}
						}
					}
					self::addFilterQuery($fieldOptions, $fq);
				}
				if (!empty($fieldOptions['fq'])) {
					$fieldQuery = $fieldOptions['fq'];
					if (is_array($fieldQuery)) {
						$fqOperator = $configurations->get($confId . 'filterQuery.' . $fqField . '.operator');
						$fqOperator = $fqOperator == 'OR' ? 'OR' : 'AND';
						$fieldQuery = implode(' ' . $fqOperator . ' ', $fieldQuery);
					}
					$tag = $configurations->get($confId . 'filterQuery.' . $fqField . '.tag');
					if($tag) {
						$tag = '{!tag="'.$tag.'"}';
						$fieldQuery = $tag.$fieldQuery;
					}
					self::addFilterQuery($options, $fieldQuery);
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
			// hier steckt nur der feldname drin
			foreach ($aFQ as $iKey => $sFq) {
				list($sfield) = explode(':', $sFq);
				// wir löschen das feld
				if(in_array($sRemoveFq, $allowedFqParams) && $sRemoveFq == $sfield)
					unset($aFQ[$iKey]);
			}
			$options['fq'] = $aFQ;
		}
	}

	/**
	 * Baut den fq-Parameter für eine Query-Facette zusammen. Die Filter-Anweisung dafür muss im Typoscript
	 * konfiguriert sein. Sie sollte identisch mit der Anweisung in der solrconfig.xml sein.
	 *
	 * @param string $queryFacetAlias
	 * @param tx_rnbase_configurations $configurations
	 * @param string $confId
	 * @return string
	 */
	private function buildFq4QueryFacet($queryFacetAlias, $configurations, $confId) {
		return $configurations->get($confId.'facet.queries.'.$queryFacetAlias);
	}

	public static function getFilterQueryForFeGroups() {
		//wenigstens ein teil der query muss matchen. bei prüfen auf
		//nicht vorhandensein muss also noch auf ein feld geprüft werden
		//das garantiert existiert und damit ein match generiert.
		$filterQuery = '(-fe_group_mi:[* TO *] AND id:[* TO *]) OR fe_group_mi:0';

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
	 * Fügt die SiteRootPage zur Filter Query hinzu
	 *
	 * @param 	array $options
	 * @param 	tx_rnbase_configurations $configurations
	 * @param 	string $confId
	 */
	public function handleFqForSiteRootPage(
		&$options, &$configurations, $confId
	) {
		if ($configurations->getBool($confId . 'respectSiteRootPage')) {
			$siteRootPage = tx_mksearch_service_indexer_core_Config::getSiteRootPage(
				$GLOBALS['TSFE']->id
			);
			if ($options['siteRootPage'] || !is_array($siteRootPage)) {
				$siteRootPage = $options['siteRootPage'];
			}
			if (is_array($siteRootPage) && !empty($siteRootPage)) {
				self::addFilterQuery(
					$options, 'siteRootPage:' . $siteRootPage['uid']
				);
			}
		}
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
	 *
	 * @param string $sFq
	 * @param array $allowedFqParams
	 * @return string
	 */
	private function parseFieldAndValue($sFq, $allowedFqParams) {
		return $this->getFilterUtility()->parseFqFieldAndValue($sFq, $allowedFqParams);
	}

	/**
	 * Liefert die Koordinaten,
	 * anhand der eine Umkreissuche durchgeführt werden soll.
	 *
	 * Dies muss Kommagetrennt ohne Leerzeichen geliefert werden:
	 * "lat,lon" bzw. "50.83,12.93"
	 *
	 * Diese Methode sollte die Kindklasse überschreiben,
	 * um Koordinaten zu liefern.
	 *
	 * @return string or false for no spatial search
	 */
	protected function getSpatialPoint() {
		return FALSE;
	}

	/**
	 * Liefert die Distanz in KM für die Umkreissuche.
	 * Wird 0 geliefert, so wird der default value aus dem TS gezogen.
	 *
	 * @return integer
	 */
	protected function getSpatialDistance() {
		return $this->getParameters()->getInt('distance');
	}

	/**
	 * Prüft Filter for eine Umkreissuche.
	 * Damit dies Funktioniert, muss in den Solr-Dokumenten ein Feld
	 * mit den Koordionaten existieren. Siehe schema.xml dynamicField *_latlon
	 *
	 * Dieses Feld muss konfiguriert werden,
	 * da darin die Umkreissuche stattfindet.
	 *
	 * @param array &$fields
	 * @param array &$options
	 */
	protected function handleSpatial(&$fields, &$options) {
		// Die Koordinaten, anhand der gesucht werden soll.
		$point = $this->getSpatialPoint();
		if (!$point) {
			return;
		}

		$confId = $this->getConfId() . 'spatial.';
		$configurations = $this->getConfigurations();

		// wir prüfen das feld wo die koordinaten im solr liegen
		$coordField = $configurations->get($confId . 'sfield');
		if (empty($coordField)) {
			return;
		}
		$options['sfield'] = $coordField;

		// die distanz bzw. den umkreis für die umkreissuche ermitteln
		$options['d'] = (int) $this->getSpatialDistance();
		if (empty($options['d'])) {
			$options['d'] = $configurations->getInt($confId . 'default.distance');
		}

		$options['pt'] = $point;

		// in die filter query muss nun noch der funktionsaufruf,
		// um die ergebnisse zu filtern.
		// @TODO: methode konfigurierbar machen ({!bbox}, {!geofilt}, ...)
		self::addFilterQuery($options, '{!geofilt}');

		// damit die ergebnisse auch nach umkreis sortiert werden können,
		// muss eine distanzberechnung mit in die query
		// &q={!func}recip(geodist(), 2, 200, 20)
		// dabei müssen wir aufpassen, ob wir uns im dismax befinden oder nicht.
		// wir schreiben die funktion direkt mit in den term,
		// ggf. als neuen parameter.
		$func = '{!func}recip(geodist(), 2, 200, 20)';
		if (empty($fields['term'])) {
			$fields['term'] = $func;
		} else {
			$fields['term'] = array($fields['term'], $func);
		}
	}

	/**
	 * Fügt die Sortierung zu dem Filter hinzu.
	 *
	 * @TODO: das klappt zurzeit nur bei einfacher sortierung!
	 *
	 * @param 	array 					$options
	 * @param 	tx_rnbase_IParameters 	$parameters
	 */
	protected function handleSorting(&$options) {
		if($sortString = $this->getFilterUtility()->getSortString($options, $this->getParameters())) {
			$options['sort'] = $sortString;
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

		$this->parseSearchForm(
			$template, $markArray, $subpartArray, $wrappedSubpartArray,
			$formatter, $confId, $marker
		);

		$this->parseSortFields(
			$template, $markArray, $subpartArray, $wrappedSubpartArray,
			$formatter, $confId, $marker
		);
		// Aufpassen: In $confId steht "searchsolr.hit.filter."
		// in $this->getConfId() steht "searchsolr.filter.default." (bzw. dismax)

		return tx_rnbase_util_Templates::substituteMarkerArrayCached(
			$template, $markArray, $subpartArray, $wrappedSubpartArray
		);
	}

	/**
	 *
	 * @param string $formTemplate
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param string $confId
	 */
	protected function renderSearchForm($formTemplate, tx_rnbase_util_FormatUtil $formatter, $confId, $templateConfId) {
		$configurations = $formatter->getConfigurations();
		if($formTemplate) {
			$link = $configurations->createLink();
			$link->initByTS($configurations, $confId.'template.links.action.', array());
			// Prepare some form data
			$paramArray = $this->getParameters()->getArrayCopy();
			$formData = $this->getParameters()->get('submit') ? $paramArray : $this->getFormData();
			$formData['action'] = $link->makeUrl(false);
			$formData['searchterm'] = htmlspecialchars( $this->getParameters()->get('term'), ENT_QUOTES );
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

			$availableModes = $this->getModeValuesAvailable();
			if($currentOptions['mode']) {
				foreach ($availableModes as $availableMode) {
					$formData['mode_' . $availableMode . '_selected'] =
					$currentOptions['mode'] == $availableMode ? 'checked=checked' : '';
				}
			}
			else {
				// Default
				$formData['mode_standard_selected'] = 'checked=checked';
			}

			$templateMarker = tx_rnbase::makeInstance('tx_mksearch_marker_General');
			$formTemplate = $templateMarker->parseTemplate($formTemplate, $formData, $formatter, $confId.'form.', 'FORM');

			// Formularfelder
			$formTemplate = $this->getFilterUtility()->parseCustomFilters($formTemplate, $formatter->getConfigurations(), $confId);
		}
		return $formTemplate;
	}
	/**
	 * protected
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

		$formTemplate = $this->renderSearchForm($formTemplate, $formatter, $confId, 'template.');
		$markArray['###'.$markerName.'###'] = $formTemplate;
		// Gibt es noch weitere Templates
		$templateKeys = $configurations->getKeyNames($confId.'templates.');
		if($templateKeys) {
			foreach($templateKeys As $templateKey) {
				$templateConfId = 'templates.'.$templateKey.'.';
				$markerName = strtoupper($configurations->get($confId.$templateConfId.'name'));
				if(tx_rnbase_util_BaseMarker::containsMarker($template, $markerName)) {
					$templateFile = $this->getConfValue($configurations, $templateConfId.'file');
					$subpart = $this->getConfValue($configurations, $templateConfId.'subpart');
					$formTemplate = tx_rnbase_util_Templates::getSubpartFromFile($templateFile, $subpart);
					$formTemplate = $this->renderSearchForm($formTemplate, $formatter, $confId, $templateConfId);
					$markArray['###'.$markerName.'###'] = $formTemplate;
				}
			}
		}
		return $template;
	}

	/**
	 * Returns all values possible for form field mksearch[options][mode].
	 * Makes it possible to easily add more modes in other filters/forms.
	 * @return array
	 */
	protected function getModeValuesAvailable() {
		$availableModes = tx_rnbase_util_Strings::trimExplode(',',
			$this->getConfValue($this->getConfigurations(), 'availableModes')
		);
		return (array) $availableModes;
	}

	/**
	 * die methode ist nur noch da für abwärtskompatiblität
	 *
	 * @param string $template HTML template
	 * @param array $markArray
	 * @param array $subpartArray
	 * @param array $wrappedSubpartArray
	 * @param tx_rnbase_util_FormatUtil $formatter
	 * @param string $confId
	 * @param string $marker
	 */
	function parseSortFields($template, &$markArray, &$subpartArray, &$wrappedSubpartArray, &$formatter, $confId, $marker = 'FILTER') {
		$this->getFilterUtility()->parseSortFields(
			$template, $markArray, $subpartArray,
			$wrappedSubpartArray, $formatter, $this->getConfId(), $marker
		);
	}

	/**
	 * @return array
	 */
	protected function getFormData() {
		return array();
	}

	/**
	 *
	 * @return tx_mksearch_util_Filter
	 */
	protected function getFilterUtility() {
		if(!$this->filterUtility) {
			$this->filterUtility = tx_rnbase::makeInstance('tx_mksearch_util_Filter');
		}

		return $this->filterUtility;
	}

}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/filter/class.tx_mksearch_filter_SolrBase.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/filter/class.tx_mksearch_filter_SolrBase.php']);
}
