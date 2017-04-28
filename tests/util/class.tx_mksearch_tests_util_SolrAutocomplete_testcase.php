<?php
/**
 * @package TYPO3
 * @subpackage mksearch
 * @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2010 Hannes Bochmann <dev@dmk-ebusiness.de>
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
tx_rnbase::load('tx_mksearch_tests_Testcase');
tx_rnbase::load('tx_mksearch_util_SolrAutocomplete');
tx_rnbase::load('tx_rnbase_util_Link');

/**
 * tx_mksearch_tests_util_SolrAutocomplete_testcase
 *
 * @package         TYPO3
 * @subpackage      mksearch
 * @author          Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_util_SolrAutocomplete_testcase extends tx_mksearch_tests_Testcase
{

    /**
     * {@inheritDoc}
     * @see tx_mksearch_tests_Testcase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();

        $property = new ReflectionProperty('\\TYPO3\\CMS\\Core\\Page\\PageRenderer', 'jsInline');
        $property->setAccessible(true);
        $property->setValue(tx_rnbase_util_TYPO3::getPageRenderer(), array());

        $property = new ReflectionProperty('\\TYPO3\\CMS\\Core\\Page\\PageRenderer', 'jsLibs');
        $property->setAccessible(true);
        $property->setValue(tx_rnbase_util_TYPO3::getPageRenderer(), array());
    }

    /**
     * @group unit
     */
    public function testGetAutocompleteJsByConfigurationArrayAndLink()
    {
        $configurationArray = array('elementSelector' => 'testSelector', 'minLength' => 123);
        $link = $this->getMock('tx_rnbase_util_Link', array('makeUrl'));
        $link->expects(self::once())
            ->method('makeUrl')
            ->with(false)
            ->will(self::returnValue('myLink'));

        $autocompleteJavaScript = tx_mksearch_util_SolrAutocomplete::getAutocompleteJavaScriptByConfigurationArrayAndLink(
            $configurationArray,
            $link
        );

        $expectedJavaScript = '<script type="text/javascript">' . LF . 'jQuery(document).ready(function(){
			jQuery(testSelector).autocomplete({
				source: function( request, response ) {
					jQuery.ajax({
						url: "myLink&mksearch[term]="+encodeURIComponent(request.term),
						dataType: "json",
						success: function( data ) {
							var suggestions = [];
							jQuery.each(data.suggestions, function(key, value) {
								jQuery.each(value, function(key, suggestion) {
									suggestions.push(suggestion.record.value);
								});
							});
							response( jQuery.map( suggestions, function( item ) {
								return {
									label: item,
									value: item
								};
							}));
						}
					});
				},
				minLength: 123
			});
		});
		jQuery(".ui-autocomplete.ui-menu.ui-widget.ui-widget-content.ui-corner-all").show();' . LF .
        '</script>';

        self::assertEquals($expectedJavaScript, $autocompleteJavaScript);
    }

    /**
     * @group unit
     */
    public function testGetAutocompleteJsByConfigurationArrayAndLinkIfDontWrapInScriptTags()
    {
        $configurationArray = array('elementSelector' => 'testSelector', 'minLength' => 123);
        $link = $this->getMock('tx_rnbase_util_Link', array('makeUrl'));
        $link->expects(self::once())
            ->method('makeUrl')
            ->with(false)
            ->will(self::returnValue('myLink'));

        $autocompleteJavaScript = tx_mksearch_util_SolrAutocomplete::getAutocompleteJavaScriptByConfigurationArrayAndLink(
            $configurationArray,
            $link,
            false
        );

        $expectedJavaScript = 'jQuery(document).ready(function(){
			jQuery(testSelector).autocomplete({
				source: function( request, response ) {
					jQuery.ajax({
						url: "myLink&mksearch[term]="+encodeURIComponent(request.term),
						dataType: "json",
						success: function( data ) {
							var suggestions = [];
							jQuery.each(data.suggestions, function(key, value) {
								jQuery.each(value, function(key, suggestion) {
									suggestions.push(suggestion.record.value);
								});
							});
							response( jQuery.map( suggestions, function( item ) {
								return {
									label: item,
									value: item
								};
							}));
						}
					});
				},
				minLength: 123
			});
		});
		jQuery(".ui-autocomplete.ui-menu.ui-widget.ui-widget-content.ui-corner-all").show();';

        self::assertEquals($expectedJavaScript, $autocompleteJavaScript);
    }
}
