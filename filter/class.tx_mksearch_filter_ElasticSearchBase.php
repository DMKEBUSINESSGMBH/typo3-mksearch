<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of the "mksearch" Extension for TYPO3 CMS.
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GNU Lesser General Public License can be found at
 * www.gnu.org/licenses/lgpl.html
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 * @author Hannes Bochmann
 * @author Michael Wagner
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_filter_ElasticSearchBase extends tx_mksearch_filter_BaseFilter
{
    /**
     * @var tx_mksearch_util_Filter
     */
    protected $filterUtility;

    /**
     * Initialize filter.
     *
     * @param array $fields
     * @param array $options
     */
    public function init(&$fields, &$options): bool
    {
        $confId = $this->getConfId();
        $fields = $this->getConfigurations()->get($confId.'fields.');
        Sys25\RnBase\Search\SearchBase::setConfigOptions(
            $options,
            $this->getConfigurations(),
            $confId.'options.'
        );

        return $this->initFilter(
            $fields,
            $options,
            $this->request
        );
    }

    /**
     * Filter for search form.
     *
     * @param array $fields
     * @param array $options
     *                       \Sys25\RnBase\Frontend\Request\RequestInterface $request
     *
     * @return bool Should subsequent query be executed at all?
     */
    protected function initFilter(
        &$fields,
        &$options,
        Sys25\RnBase\Frontend\Request\RequestInterface $request,
    ): bool {
        $configurations = $request->getConfigurations();
        $parameters = $request->getParameters();
        $confId = $this->getConfId();

        // Es muss ein Submit-Parameter im request liegen, damit der Filter greift
        if (!$parameters->offsetExists('submit') && !$configurations->get($confId.'forceSearch')
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
     * @param Sys25\RnBase\Frontend\Request\ParametersInterface $parameters
     * @param Sys25\RnBase\Configuration\Processor              $configurations
     */
    protected function handleTerm(array &$fields, &$parameters, &$configurations, string $confId)
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
     */
    protected function handleSorting(array &$options)
    {
        if ($sortString = $this->getFilterUtility()->getSortString(
            $options,
            $this->getParameters()
        )) {
            $options['sort'] = $sortString;
        }
    }

    /**
     * @param string                                  $template  HTML template
     * @param Sys25\RnBase\Frontend\Marker\FormatUtil $formatter
     * @param string                                  $confId
     * @param string                                  $marker
     *
     * @return string
     */
    public function parseTemplate($template, &$formatter, $confId, $marker = 'FILTER')
    {
        $markArray = [];
        $subpartArray = [];
        $wrappedSubpartArray = [];
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

        return Sys25\RnBase\Frontend\Marker\Templates::substituteMarkerArrayCached(
            $template,
            $markArray,
            $subpartArray,
            $wrappedSubpartArray
        );
    }

    /**
     * Treat search form.
     *
     * @param string                                  $template            HTML template
     * @param array                                   $subpartArray
     * @param array                                   $wrappedSubpartArray
     * @param Sys25\RnBase\Frontend\Marker\FormatUtil $formatter
     * @param string                                  $confId
     * @param string                                  $marker
     *
     * @return string
     *
     * @todo refactoring da die gleiche Methode wie in tx_mksearch_filter_LuceneBase
     */
    public function parseSearchForm(
        $template,
        array &$markArray,
        &$subpartArray,
        &$wrappedSubpartArray,
        &$formatter,
        $confId,
        $marker = 'FILTER',
    ) {
        $markerName = 'SEARCH_FORM';
        if (!Sys25\RnBase\Frontend\Marker\BaseMarker::containsMarker($template, $markerName)) {
            return $template;
        }

        $confId = $this->getConfId();

        $configurations = $formatter->getConfigurations();
        $formTemplate = $configurations->get($confId.'template.file');

        $subpart = $configurations->get($confId.'template.subpart');
        $formTemplate = Sys25\RnBase\Frontend\Marker\Templates::getSubpartFromFile(
            $formTemplate,
            $subpart
        );

        if ($formTemplate) {
            $link = $configurations->createLink();
            $link->initByTS($configurations, $confId.'template.links.action.', []);
            // Prepare some form data
            $paramArray = $this->getParameters()->getArrayCopy();
            $formData = $this->getParameters()->get('submit') ? $paramArray : $this->getFormData();
            $formData['action'] = $link->makeUrl(false);
            $formData['searchterm'] = htmlspecialchars($this->getParameters()->get('term'), ENT_QUOTES);
            $formData['hiddenfields'] = Sys25\RnBase\Backend\Form\FormUtil::getHiddenFieldsForUrlParams($formData['action']);
            $this->prepareFormFields($formData, $this->getParameters());

            $combinations = ['none', 'free', 'or', 'and', 'exact'];
            $currentCombination = $this->getParameters()->get('combination');
            $currentCombination = $currentCombination ?: 'none';
            foreach ($combinations as $combination) {
                // wenn anders benötigt, via ts ändern werden
                $formData['combination_'.$combination] = ($combination == $currentCombination) ? ' checked="checked"' : '';
            }

            $options = ['fuzzy'];
            $currentOptions = $this->getParameters()->get('options');
            foreach ($options as $option) {
                // wenn anders benötigt, via ts ändern werden
                $formData['option_'.$option] = empty($currentOptions[$option]) ? '' : ' checked="checked"';
            }

            $availableModes = $this->getModeValuesAvailable();
            if ($currentOptions['mode']) {
                foreach ($availableModes as $availableMode) {
                    $formData['mode_'.$availableMode.'_selected'] =
                        $currentOptions['mode'] == $availableMode ? 'checked=checked' : '';
                }
            } else {
                // Default
                $formData['mode_standard_selected'] = 'checked=checked';
            }

            $templateMarker = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_marker_General');
            $formTemplate = $templateMarker->parseTemplate($formTemplate, $formData, $formatter, $confId.'form.', 'FORM');
        }

        $markArray['###'.$markerName.'###'] = $formTemplate;

        return $template;
    }

    /**
     * Werte für Formularfelder aufbereiten. Daten aus dem Request übernehmen und wieder füllen.
     *
     * @param array                                             $formData
     * @param Sys25\RnBase\Frontend\Request\ParametersInterface $parameters
     */
    protected function prepareFormFields(&$formData, $parameters)
    {
        $formData['searchterm'] = htmlspecialchars($parameters->get('term'), ENT_QUOTES);
        $values = ['or', 'and', 'exact'];
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
            $formData
        );
    }

    /**
     * Returns all values possible for form field mksearch[options][mode].
     * Makes it possible to easily add more modes in other filters/forms.
     */
    protected function getModeValuesAvailable(): array
    {
        $availableModes = Sys25\RnBase\Utility\Strings::trimExplode(
            ',',
            $this->getConfigurations()->get($this->getConfId().'availableModes')
        );

        return (array) $availableModes;
    }

    /**
     * ist notwendig weil sonst die Marker, welche die Formulardaten
     * enthalten ungeparsed rauskommen, falls das Formular noch
     * nicht abgeschickt wurde.
     */
    private function fillFormDataWithRequiredFormFieldsIfNoSet(
        array $formData,
    ): array {
        $formFields = Sys25\RnBase\Utility\Strings::trimExplode(
            ',',
            $this->getConfigurations()->get($this->getConfId().'requiredFormFields')
        );

        foreach ($formFields as $formField) {
            if (!array_key_exists($formField, $formData)) {
                $formData[$formField] = '';
            }
        }

        return $formData;
    }

    /**
     * die methode ist nur noch da für abwärtskompatiblität.
     *
     * @param string                                  $template     HTML template
     * @param array                                   $markArray
     * @param array                                   $subpartArray
     * @param Sys25\RnBase\Frontend\Marker\FormatUtil $formatter
     * @param string                                  $confId
     * @param string                                  $marker
     */
    public function parseSortFields(
        $template,
        &$markArray,
        &$subpartArray,
        array &$wrappedSubpartArray,
        &$formatter,
        $confId,
        $marker = 'FILTER',
    ): void {
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

    private function getFormData(): array
    {
        return [];
    }

    /**
     * @return tx_mksearch_util_Filter
     */
    protected function getFilterUtility()
    {
        if (!$this->filterUtility) {
            $this->filterUtility = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_util_Filter');
        }

        return $this->filterUtility;
    }
}
