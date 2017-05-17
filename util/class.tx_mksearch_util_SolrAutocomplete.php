<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 das Medienkombinat <dev@dmk-ebusiness.de>
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


tx_rnbase::load('tx_rnbase_configurations');
tx_rnbase::load('tx_mksearch_util_ServiceRegistry');

/**
 * @author Hannes Bochmann <dev@dmk-ebusiness.de>
 * @package TYPO3
 * @subpackage tx_mksearch
 */
class tx_mksearch_util_SolrAutocomplete
{

    /**
     * @var string
     */
    protected static $autocompleteConfId = 'autocomplete.';

    /**
     * @param tx_rnbase_configurations $configurations
     * @param string $confId
     *
     * example TS config:
     * myConfId {
     *  usedIndex = 1
     *  autocomplete {
     *      actionLink {
     *          useKeepVars = 1
     *          useKeepVars.add = ::type=540
     *          absurl = 1
     *          noHash = 1
     *      }
     *  }
     * }
     *
     * @return tx_rnbase_util_Link
     */
    public static function getAutocompleteActionLinkByConfigurationsAndConfId(
        tx_rnbase_configurations $configurations,
        $confId
    ) {
        $linkParameters = array('ajax' => 1);
        $usedIndex = $configurations->get($confId.'usedIndex');
        if ($usedIndex === 0 || $usedIndex > 0) {
            $linkParameters['usedIndex'] = intval($usedIndex);
        }

        $link = $configurations->createLink();
        $link->initByTS(
            $configurations,
            $confId . self::$autocompleteConfId . 'actionLink.',
            $linkParameters
        );

        return $link;
    }

    /**
     * @deprecated use getAutocompleteJavaScriptByConfigurationArrayAndLink
     */
    public static function getAutocompleteJsByConfigurationsConfIdAndLink(
        $configArray,
        tx_rnbase_util_Link $link,
        $wrapInScriptTags = true
    ) {
        return self::getAutocompleteJavaScriptByConfigurationArrayAndLink($configArray, $link, $wrapInScriptTags);
    }

    /**
     * @param array $configArray example:
     * array (
     *  minLength = 2
     *  elementSelector = "#mksearch_term"
     * )
     *
     * @param tx_rnbase_util_Link $link
     * @param bool $wrapInScriptTags
     *
     * @return string
     */
    public static function getAutocompleteJavaScriptByConfigurationArrayAndLink(
        $configArray,
        tx_rnbase_util_Link $link,
        $wrapInScriptTags = true
    ) {
        $javaScript = 'jQuery(document).ready(function(){' .
			'jQuery('.$configArray['elementSelector'].').autocomplete({' .
				'source: function( request, response ) {' .
					'jQuery.ajax({' .
						'url: "' . $link->makeUrl(false) . '&mksearch[term]="+encodeURIComponent(request.term),' .
						'dataType: "json",' .
						'success: function( data ) {' .
							'var suggestions = [];' .
							'jQuery.each(data.suggestions, function(key, value) {' .
								'jQuery.each(value, function(key, suggestion) {' .
									'suggestions.push(suggestion.record.value);' .
								'});' .
							'});' .
							'response( jQuery.map( suggestions, function( item ) {' .
								'return {' .
									'label: item,' .
									'value: item' .
								'};' .
							'}));' .
						'}' .
					'});' .
				'},' .
				'minLength: ' . $configArray['minLength'] .
			'});' .
		'});' .
		'jQuery(".ui-autocomplete.ui-menu.ui-widget.ui-widget-content.ui-corner-all").show();';

        if ($wrapInScriptTags) {
            $javaScript = '<script type="text/javascript">' . $javaScript . '</script>';
        }

        return $javaScript;
    }
}
