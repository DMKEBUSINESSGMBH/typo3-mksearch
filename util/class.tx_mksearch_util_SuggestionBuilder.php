<?php
/**
 *  @author Hannes Bochmann
 *
 *  Copyright notice
 *
 *  (c) 2010 - 2011 DMK E-Business GmbH <dev@dmk-ebusiness.de>
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

/**
 * benötigte Klassen einbinden.
 */

/**
 * Der SuggestionBuilder erstellt aus den Rohdaten der Suggestions passende Objekte für das Rendering.
 *
 * @author Hannes Bochmann <dev@dmk-ebusiness.de>
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_util_SuggestionBuilder
{
    /**
     * @param string $class
     *
     * @return tx_mksearch_util_SuggestionBuilder
     */
    public static function getInstance($class = '')
    {
        static $instance;
        $class = empty($class) ? 'tx_mksearch_util_SuggestionBuilder' : $class;
        if (!$instance[$class]) {
            $instance[$class] = tx_rnbase::makeInstance($class);
        }

        return $instance[$class];
    }

    /**
     * Baut die Daten für die Suggestions zusammen.
     *
     * @param array $aSuggestionData Daten von Solr
     *
     * @return array Ausgabedaten
     */
    public function buildSuggestions($aSuggestionData)
    {
        $aSuggestions = array();
        if (!$aSuggestionData) {
            return $aSuggestions;
        }
        foreach ($aSuggestionData as $sSearchWord => $oSearchWord) {
            if (isset($oSearchWord->suggestion) && is_array($oSearchWord->suggestion)) {
                $uid = 0;
                foreach ($oSearchWord->suggestion as $sSuggestion) {
                    //sorted by search word
                    $aSuggestions[$sSearchWord][] = $this->getSimpleSuggestion(
                        array(
                            'uid' => ++$uid,
                            'value' => $sSuggestion,
                            'searchWord' => $sSearchWord,
                        )
                    );
                }
            }
        }

        return $aSuggestions;
    }

    /**
     * Liefert eine simple Suggestion zurück.
     *
     * @param string $field
     *
     * @return tx_mksearch_model_Suggestion
     */
    protected function getSimpleSuggestion($aSuggestion)
    {
        return tx_rnbase::makeInstance('tx_mksearch_model_Suggestion', $aSuggestion);
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_SuggestionBuilder.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_SuggestionBuilder.php'];
}
