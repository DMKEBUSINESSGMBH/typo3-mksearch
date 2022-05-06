<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 das Medienkombinat
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

/**
 * Model for solr specific indexer fields.
 */
class tx_mksearch_model_engineSpecific_solr_IndexerField extends tx_mksearch_model_IndexerFieldBase
{
    /**
     * Return the field's value
     * FIXEME Wenn hier nacharbeiten notwendig sind, dann gehören die in die Engine-Implementierung bei der Übergabe der
     * Daten in den Indexer.
     *
     * @return mixed
     */
    /*
    public function getValue() {
        return parent::getValue();
        // Zur Info: Datumsangaben immer als String im ISO-Format angeben!
        $dt = $this->getDataType();
        switch ($dt) {
            case 'date':
            case 'datetime':
            case 'time':
                $dateTime = parent::getValue();

                if (!$dateTime instanceof DateTime)
                    throw new Exception('tx_mksearch_model_engineSpecific_solr_IndexerField->getValue(): Data type \'date\' / \'datetime\' / \'time\' given, but value is no DateTime instance!');

                // Normalize datetime
                $dateTime->setTimeZone(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('DateTimeZone', 'UTC'));

                switch ($dt) {
                    case 'date':
                        $dateTime->setTime(0,0,0);
                        break;
                    case 'time':
                        $dateTime->setDate(1,1,1);
                        break;
                }
                // Return datetime string in format required by Solr
                return $dateTime->format('Y-m-d\TH:i:s\Z');
                break;
            default:
                return parent::getValue();
        }
    }
    */

    /**
     * Update the field's value.
     *
     * Solr-specific: Set storage option "multiValued" if $value is an array.
     * // Note that the self::$_storageOption['boost'] may also be ... (???)
     *
     * @param mixed $value
     * @param mixed $boost
     */
    public function updateValue($value, $boost = 1.0)
    {
        parent::updateValue($value, $boost);
        parent::updateStorageOption('multiValued', is_array($value));
    }

    /**
     * Return values with their associated boost, respecting multiple values.
     *
     * @return unknown
     */
    public function getValuesWithBoost()
    {
        $val = $this->getValue();
        $boost = $this->getBoost();
        if (!$this->getStorageOption('multiValued')) {
            return ['value' => $val, 'boost' => $boost];
        }
        // else
        $res = [];
//         $boostSize = count($boost);
//         for ($i=0; $i<count($val); $i++) {
//                                                        // Flat fallback to first boost value
//            $res[] = array('value' => $val[$i], 'boost' => $boost[(empty($boost[$i])?0:$i)]);
//         }
        // so gibt es weniger Probleme bei Arrays wie array(0 => wert, 3 => wert, 5 => wert)
        // in einer for Schleife wären die 2 letzten Values leer da bei array[1] oder array[2]
        // kein Wert vorhanden ist, sondern erst wieder bei array[3]
        foreach ($val as $key => $value) {
            // Flat fallback to first boost value
            $res[] = ['value' => $val[$key], 'boost' => $boost[(empty($boost[$key]) ? 0 : $i)]];
        }

        return $res;
    }

    public function __toString()
    {
        return parent::__toString();
    }
}
