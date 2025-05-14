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
 * tx_mksearch_tests_util_SolrAutocompleteTest.
 *
 * @author          Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_util_SolrAutocompleteTest extends tx_mksearch_tests_Testcase
{
    public function testGetAutocompleteJsByConfigurationArrayAndLink(): void
    {
        $configurationArray = ['elementSelector' => 'testSelector', 'minLength' => 123];
        $link = $this->getMock(Sys25\RnBase\Utility\Link::class, ['makeUrl']);
        $link->expects(self::once())
            ->method('makeUrl')
            ->with(false)
            ->willReturn('myLink');

        $autocompleteJavaScript = tx_mksearch_util_SolrAutocomplete::getAutocompleteJavaScriptByConfigurationArrayAndLink(
            $configurationArray,
            $link
        );

        $expectedJavaScript = '<script type="text/javascript">jQuery(document).ready(function(){'.
            'jQuery(testSelector).autocomplete({'.
                'source: function( request, response ) {'.
                    'jQuery.ajax({'.
                        'url: "myLink&mksearch[term]="+encodeURIComponent(request.term),'.
                        'dataType: "json",'.
                        'success: function( data ) {'.
                            'var suggestions = [];'.
                            'jQuery.each(data.suggestions, function(key, value) {'.
                                'jQuery.each(value, function(key, suggestion) {'.
                                    'suggestions.push(suggestion.record.value);'.
                                '});'.
                            '});'.
                            'response( jQuery.map( suggestions, function( item ) {'.
                                'return {'.
                                    'label: item,'.
                                    'value: item'.
                                '};'.
                            '}));'.
                        '}'.
                    '});'.
                '},'.
                'minLength: 123'.
            '});'.
        '});'.
        'jQuery(".ui-autocomplete.ui-menu.ui-widget.ui-widget-content.ui-corner-all").show();'.
        '</script>';

        self::assertEquals($expectedJavaScript, $autocompleteJavaScript);
    }

    public function testGetAutocompleteJsByConfigurationArrayAndLinkIfDontWrapInScriptTags(): void
    {
        $configurationArray = ['elementSelector' => 'testSelector', 'minLength' => 123];
        $link = $this->getMock(Sys25\RnBase\Utility\Link::class, ['makeUrl']);
        $link->expects(self::once())
            ->method('makeUrl')
            ->with(false)
            ->willReturn('myLink');

        $autocompleteJavaScript = tx_mksearch_util_SolrAutocomplete::getAutocompleteJavaScriptByConfigurationArrayAndLink(
            $configurationArray,
            $link,
            false
        );

        $expectedJavaScript = 'jQuery(document).ready(function(){'.
            'jQuery(testSelector).autocomplete({'.
                'source: function( request, response ) {'.
                    'jQuery.ajax({'.
                        'url: "myLink&mksearch[term]="+encodeURIComponent(request.term),'.
                        'dataType: "json",'.
                        'success: function( data ) {'.
                            'var suggestions = [];'.
                            'jQuery.each(data.suggestions, function(key, value) {'.
                                'jQuery.each(value, function(key, suggestion) {'.
                                    'suggestions.push(suggestion.record.value);'.
                                '});'.
                            '});'.
                            'response( jQuery.map( suggestions, function( item ) {'.
                                'return {'.
                                    'label: item,'.
                                    'value: item'.
                                '};'.
                            '}));'.
                        '}'.
                    '});'.
                '},'.
                'minLength: 123'.
            '});'.
        '});'.
        'jQuery(".ui-autocomplete.ui-menu.ui-widget.ui-widget-content.ui-corner-all").show();';

        self::assertEquals($expectedJavaScript, $autocompleteJavaScript);
    }
}
