<?php

/**
 * Hilfsklasse für Suchen im BE.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_util_SearchBuilder
{
    /**
     * Suche nach einem Freitext. Wird ein leerer String
     * übergeben, dann wird nicht gesucht.
     *
     * @param array  $fields
     * @param string $searchword
     * @param array  $cols
     */
    public static function buildFreeText(&$fields, $searchword, array $cols = array())
    {
        $result = false;
        if (strlen(trim($searchword))) {
            $joined['value'] = trim($searchword);
            $joined['cols'] = $cols;
            $joined['operator'] = OP_LIKE;
            $fields[SEARCH_FIELD_JOINED][] = $joined;
            $result = true;
        }

        return $result;
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_mod1_util_SearchBuilder.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_mod1_util_SearchBuilder.php'];
}
