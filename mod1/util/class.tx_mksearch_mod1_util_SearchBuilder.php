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
     * @param string $searchword
     */
    public static function buildFreeText(array &$fields, $searchword, array $cols = [])
    {
        $result = false;
        if (0 !== strlen(trim($searchword))) {
            $joined['value'] = trim($searchword);
            $joined['cols'] = $cols;
            $joined['operator'] = OP_LIKE;
            $fields[SEARCH_FIELD_JOINED][] = $joined;
            $result = true;
        }

        return $result;
    }
}
