<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 das Medienkombinat
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

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');

/**
 *
 * @author Hannes Bochmann <hannes.bochmann@dmk-business.de>
 *
 */
class tx_mksearch_util_Filter {

	/**
	 * Fügt den Suchstring zu dem Filter hinzu.
	 *
	 * dazu folgendes TS Setup
	 *
	 	# Die hier konfigurierten Parameter werden als Marker im Term-String verwendet
		# Es sind verschiedene Extensions möglich
		$confId.params.mksearch {
			term = TEXT
			term {
				field = term
			}
		}
		# So wurden auch alle Parameter von tt_news ausgelesen
		#$confId.params.tt_news {
		#}

		$confId.fields {
			# Die Marker haben das Format ###PARAM_EXTQUALIFIER_PARAMNAME###
			### beim DisMaxRequestHandler darf hier nur ###PARAM_MKSEARCH_TERM### stehen!
			term = contentType:* ###PARAM_MKSEARCH_TERM###
		}
	 *
	 * @param 	string 						$termTemplate
	 * @param 	array 						$options
	 * @param 	tx_rnbase_IParameters 		$parameters
	 * @param 	tx_rnbase_configurations 	$configurations
	 * @param 	string 						$confId
	 *
	 * @return string
	 */
	public function parseTermTemplate($termTemplate, $parameters, $configurations, $confId) {
		$templateMarker = tx_rnbase::makeInstance('tx_mksearch_marker_General');
		// Welche Extension-Parameter werden verwendet?
		$extQualifiers = $configurations->getKeyNames($confId.'params.');
		foreach($extQualifiers As $qualifier) {
			$params = $parameters->getAll($qualifier);
			$termTemplate = $templateMarker->parseTemplate(
				$termTemplate, $params,
				$configurations->getFormatter(),
				$confId.'params.'.$qualifier.'.',
				'PARAM_'.strtoupper($qualifier)
			);
			//wenn keine params gesetzt sind, kommt der marker ungeparsed raus.
			//also ersetzen wir diesen prinzipiell am ende durch ein leeren string.
			$termTemplate = preg_replace(
				'/(###PARAM_'.strtoupper($qualifier).'_)\w.*###/', '', $termTemplate
			);
		}

		return $termTemplate;
	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_Filter.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_Filter.php']);
}