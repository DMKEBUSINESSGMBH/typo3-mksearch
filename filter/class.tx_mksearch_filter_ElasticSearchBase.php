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

/**
 *
 * @author Hannes Bochmann <hannes.bochmann@dmk-business.de>
 */
class tx_mksearch_filter_ElasticSearchBase extends tx_rnbase_filter_BaseFilter
{

    /**
     *
     * @var tx_mksearch_util_Filter
     */
    protected $filterUtility;

    /**
     * Initialize filter
     *
     * @param array $fields
     * @param array $options
     */
    public function init(&$fields, &$options)
    {
        $confId = $this->getConfId();
        $fields = $this->getConfigurations()->get($confId . 'fields.');
        tx_rnbase_util_SearchBase::setConfigOptions(
            $options,
            $this->getConfigurations(),
            $confId . 'options.'
        );

        return $this->initFilter(
            $fields,
            $options,
            $this->getParameters(),
            $this->getConfigurations(),
            $confId
        );
    }

    /**
     * Filter for search form
     *
     * @param array $fields
     * @param array $options
     * @param tx_rnbase_parameters $parameters
     * @param tx_rnbase_configurations $configurations
     * @param string $confId
     * @return bool Should subsequent query be executed at all?
     */
    protected function initFilter(
        &$fields,
        &$options,
        &$parameters,
        &$configurations,
        $confId
    ) {
        // Es muss ein Submit-Parameter im request liegen, damit der Filter greift
        if (!($parameters->offsetExists('submit') ||
            $configurations->get($confId . 'forceSearch'))
        ) {
            return false;
        }

        $this->handleTerm($fields, $parameters, $configurations, $confId);
        $this->handleSorting($options);

        return true;
    }

    /**
     * Fügt den Suchstring zu dem Filter hinzu.
     *
     * @param   array                       $fields
     * @param   array                       $options
     * @param   tx_rnbase_IParameters       $parameters
     * @param   tx_rnbase_configurations    $configurations
     * @param   string                      $confId
     */
    protected function handleTerm(&$fields, &$parameters, &$configurations, $confId)
    {
        if ($termTemplate = $fields['term']) {
            $parsedTemplate = $this->getFilterUtility()->parseTermTemplate(
                $termTemplate,
                $parameters,
                $configurations,
                $confId
            );
            $fields['term'] = $parsedTemplate;
        }
    }

    /**
     * Fügt die Sortierung zu dem Filter hinzu.
     *
     * @TODO: das klappt zurzeit nur bei einfacher sortierung!
     *
     * @param   array                   $options
     * @param   tx_rnbase_IParameters   $parameters
     */
    protected function handleSorting(&$options)
    {
        if ($sortString = $this->getFilterUtility()->getSortString(
            $options,
            $this->getParameters()
        )) {
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
    public function parseTemplate($template, &$formatter, $confId, $marker = 'FILTER')
    {
        $markArray = $subpartArray  = $wrappedSubpartArray = array();

        $this->parseSearchForm(
            $template,
            $markArray,
            $subpartArray,
            $wrappedSubpartArray,
            $formatter,
            $confId,
            $marker
        );

        $this->parseSortFields(
            $template,
            $markArray,
            $subpartArray,
            $wrappedSubpartArray,
            $formatter,
            $confId,
            $marker
        );

        return tx_rnbase_util_Templates::substituteMarkerArrayCached(
            $template,
            $markArray,
            $subpartArray,
            $wrappedSubpartArray
        );
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
     *
     * @todo refactoring da die gleiche Methode wie in tx_mksearch_filter_LuceneBase
     */
    public function parseSearchForm(
        $template,
        &$markArray,
        &$subpartArray,
        &$wrappedSubpartArray,
        &$formatter,
        $confId,
        $marker = 'FILTER'
    ) {
        $markerName = 'SEARCH_FORM';
        if (!tx_rnbase_util_BaseMarker::containsMarker($template, $markerName)) {
            return $template;
        }

        $confId = $this->getConfId();

        $configurations = $formatter->getConfigurations();
        tx_rnbase::load('tx_rnbase_util_Templates');
        $formTemplate = $configurations->get($confId . 'template.file');

        $subpart = $configurations->get($confId . 'template.subpart');
        $formTemplate = tx_rnbase_util_Templates::getSubpartFromFile(
            $formTemplate,
            $subpart
        );

        if ($formTemplate) {
            $link = $configurations->createLink();
            $link->initByTS($configurations, $confId.'template.links.action.', array());
            // Prepare some form data
            $paramArray = $this->getParameters()->getArrayCopy();
            $formData = $this->getParameters()->get('submit') ? $paramArray : $this->getFormData();
            $formData['action'] = $link->makeUrl(false);
            $formData['searchterm'] = htmlspecialchars($this->getParameters()->get('term'), ENT_QUOTES);
            tx_rnbase::load('tx_rnbase_util_FormUtil');
            $formData['hiddenfields'] = tx_rnbase_util_FormUtil::getHiddenFieldsForUrlParams($formData['action']);
            $this->prepareFormFields($formData, $this->getParameters());

            $combinations = array('none', 'free', 'or', 'and', 'exact');
            $currentCombination = $this->getParameters()->get('combination');
            $currentCombination = $currentCombination ? $currentCombination : 'none';
            foreach ($combinations as $combination) {
                // wenn anders benötigt, via ts ändern werden
                $formData['combination_'.$combination] = ($combination == $currentCombination) ? ' checked="checked"' : '';
            }

            $options = array('fuzzy');
            $currentOptions = $this->getParameters()->get('options');
            foreach ($options as $option) {
                // wenn anders benötigt, via ts ändern werden
                $formData['option_'.$option] = empty($currentOptions[$option]) ? '' : ' checked="checked"';
            }

            $availableModes = $this->getModeValuesAvailable();
            if ($currentOptions['mode']) {
                foreach ($availableModes as $availableMode) {
                    $formData['mode_' . $availableMode . '_selected'] =
                        $currentOptions['mode'] == $availableMode ? 'checked=checked' : '';
                }
            } else {
                // Default
                $formData['mode_standard_selected'] = 'checked=checked';
            }

            $templateMarker = tx_rnbase::makeInstance('tx_mksearch_marker_General');
            $formTemplate = $templateMarker->parseTemplate($formTemplate, $formData, $formatter, $confId.'form.', 'FORM');
        }

        $markArray['###'.$markerName.'###'] = $formTemplate;

        return $template;
    }

    /**
     * Werte für Formularfelder aufbereiten. Daten aus dem Request übernehmen und wieder füllen.
     * @param array $formData
     * @param tx_rnbase_parameters $parameters
     */
    protected function prepareFormFields(&$formData, $parameters)
    {
        $formData['searchterm'] = htmlspecialchars($parameters->get('term'), ENT_QUOTES);
        $values = array('or', 'and', 'exact');
        $options = $parameters->get('options');
        if ($options['combination']) {
            foreach ($values as $value) {
                $formData['combination_'.$value.'_selected'] = $options['combination'] == $value ? 'checked=checked' : '';
            }
        } else {
            // Default
            $formData['combination_or_selected'] = 'checked=checked';
        }
        $values = $this->getModeValuesAvailable();
        if ($options['mode']) {
            foreach ($values as $value) {
                $formData['mode_'.$value.'_selected'] = $options['mode'] == $value ? 'checked=checked' : '';
            }
        } else {
            // Default
            $formData['mode_standard_selected'] = 'checked=checked';
        }

        $formData = $this->fillFormDataWithRequiredFormFieldsIfNoSet(
            $formData,
            $parameters
        );
    }

    /**
     * Returns all values possible for form field mksearch[options][mode].
     * Makes it possible to easily add more modes in other filters/forms.
     * @return array
     */
    protected function getModeValuesAvailable()
    {
        $availableModes = tx_rnbase_util_Strings::trimExplode(
            ',',
            $this->getConfigurations()->get($this->getConfId() . 'availableModes')
        );

        return (array) $availableModes;
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
        array $formData,
        tx_rnbase_parameters $parameters
    ) {
        $formFields = tx_rnbase_util_Strings::trimExplode(
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
    public function parseSortFields(
        $template,
        &$markArray,
        &$subpartArray,
        &$wrappedSubpartArray,
        &$formatter,
        $confId,
        $marker = 'FILTER'
    ) {
        $this->getFilterUtility()->parseSortFields(
            $template,
            $markArray,
            $subpartArray,
            $wrappedSubpartArray,
            $formatter,
            $this->getConfId(),
            $marker
        );
    }

    /**
     * @return array
     */
    private function getFormData()
    {
        return array();
    }

    /**
     *
     * @return tx_mksearch_util_Filter
     */
    protected function getFilterUtility()
    {
        if (!$this->filterUtility) {
            $this->filterUtility = tx_rnbase::makeInstance('tx_mksearch_util_Filter');
        }

        return $this->filterUtility;
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/filter/class.tx_mksearch_filter_ElasticSearchBase.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/filter/class.tx_mksearch_filter_ElasticSearchBase.php']);
}
