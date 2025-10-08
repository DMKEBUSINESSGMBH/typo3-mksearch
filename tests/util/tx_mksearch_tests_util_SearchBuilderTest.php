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
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_util_SearchBuilderTest extends tx_mksearch_tests_Testcase
{
    #[PHPUnit\Framework\Attributes\DataProvider('providerSearchSolrOptions')]
    public function testSearchSolrOptions($term, $combination, array $options, $result): void
    {
        $res = tx_mksearch_util_SearchBuilder::searchSolrOptions($term, $combination, $options);
        self::assertEquals($result, $res);
    }

    public static function providerSearchSolrOptions(): array
    {
        $return = [];
        foreach ([
            // array($term, $combination, $options, $result),
            1 => ['Hallo  Welt', MKSEARCH_OP_AND, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 0], '(+"Hallo" +"Welt")'],
            2 => ['Hallo  Welt', MKSEARCH_OP_OR, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 0], '("Hallo" "Welt")'],
            3 => ['Hallo  Welt', MKSEARCH_OP_EXACT, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 0], '("Hallo Welt")'],
            4 => ['Hallo  Welt', MKSEARCH_OP_FREE, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 0], 'Hallo Welt'],
            5 => ['Hallo  Welt', MKSEARCH_OP_NONE, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 0], '("Hallo" "Welt")'],
            6 => ['Hallo  Welt', null, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 0], '("Hallo" "Welt")'],

            7 => ['Hallo  Welt', MKSEARCH_OP_AND, ['quote' => 0, 'dismax' => 0, 'fuzzy' => 0], '(+Hallo +Welt)'],
            8 => ['Hallo  Welt', MKSEARCH_OP_OR, ['quote' => 0, 'dismax' => 0, 'fuzzy' => 0], '(Hallo Welt)'],
            9 => ['Hallo  Welt', MKSEARCH_OP_EXACT, ['quote' => 0, 'dismax' => 0, 'fuzzy' => 0], '("Hallo Welt")'],
            10 => ['Hallo  Welt', MKSEARCH_OP_FREE, ['quote' => 0, 'dismax' => 0, 'fuzzy' => 0], 'Hallo Welt'],
            11 => ['Hallo  Welt', MKSEARCH_OP_NONE, ['quote' => 0, 'dismax' => 0, 'fuzzy' => 0], '(Hallo Welt)'],
            12 => ['Hallo  Welt', null, ['quote' => 0, 'dismax' => 0, 'fuzzy' => 0], '(Hallo Welt)'],

            13 => ['Hallo  Welt', MKSEARCH_OP_AND, ['quote' => 1, 'dismax' => 1, 'fuzzy' => 0], '+"Hallo" +"Welt"'],
            14 => ['Hallo  Welt', MKSEARCH_OP_OR, ['quote' => 1, 'dismax' => 1, 'fuzzy' => 0], '"Hallo" "Welt"'],
            15 => ['Hallo  Welt', MKSEARCH_OP_EXACT, ['quote' => 1, 'dismax' => 1, 'fuzzy' => 0], '"Hallo Welt"'],
            16 => ['Hallo  Welt', MKSEARCH_OP_FREE, ['quote' => 1, 'dismax' => 1, 'fuzzy' => 0], 'Hallo Welt'],
            17 => ['Hallo  Welt', MKSEARCH_OP_NONE, ['quote' => 1, 'dismax' => 1, 'fuzzy' => 0], '"Hallo" "Welt"'],
            18 => ['Hallo  Welt', null, ['quote' => 1, 'dismax' => 1, 'fuzzy' => 0], '"Hallo" "Welt"'],

            19 => ['Hallo  Welt', MKSEARCH_OP_AND, ['quote' => 0, 'dismax' => 1, 'fuzzy' => 0], '+Hallo +Welt'],
            20 => ['Hallo  Welt', MKSEARCH_OP_OR, ['quote' => 0, 'dismax' => 1, 'fuzzy' => 0], 'Hallo Welt'],
            21 => ['Hallo  Welt', MKSEARCH_OP_EXACT, ['quote' => 0, 'dismax' => 1, 'fuzzy' => 0], '"Hallo Welt"'],
            22 => ['Hallo  Welt', MKSEARCH_OP_FREE, ['quote' => 0, 'dismax' => 1, 'fuzzy' => 0], 'Hallo Welt'],
            23 => ['Hallo  Welt', MKSEARCH_OP_NONE, ['quote' => 0, 'dismax' => 1, 'fuzzy' => 0], 'Hallo Welt'],
            24 => ['Hallo  Welt', null, ['quote' => 0, 'dismax' => 1, 'fuzzy' => 0], 'Hallo Welt'],

            // @TODO: doppelte operatoren müssen verhindert werden!
            //              __LINE__ => array('+Hallo  -Welt', MKSEARCH_OP_AND, array('quote' => 1, 'dismax' => 0, 'fuzzy' => 0), '(+"Hallo" -"Welt")'),
            25 => ['+Hallo  -Welt', MKSEARCH_OP_OR, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 0], '(+"Hallo" -"Welt")'],
            26 => ['+Hallo  -Welt', MKSEARCH_OP_EXACT, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 0], '("+Hallo -Welt")'],
            27 => ['+Hallo  -Welt', MKSEARCH_OP_FREE, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 0], '+Hallo -Welt'],
            28 => ['+Hallo  -Welt', MKSEARCH_OP_NONE, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 0], '(+"Hallo" -"Welt")'],
            29 => ['+Hallo  -Welt', null, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 0], '(+"Hallo" -"Welt")'],

            30 => ['Hallo  Welt', MKSEARCH_OP_AND, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 1], '(+"Hallo"~0.2 +"Welt"~0.2)'],
            31 => ['Hallo  Welt', MKSEARCH_OP_OR, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 1], '("Hallo"~0.2 "Welt"~0.2)'],
            32 => ['Hallo  Welt', MKSEARCH_OP_EXACT, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 1], '("Hallo Welt"~0.2)'],
            // bei free gibts kein fuzzy!
            33 => ['Hallo  Welt', MKSEARCH_OP_FREE, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 1], 'Hallo Welt'],
            34 => ['Hallo  Welt', MKSEARCH_OP_NONE, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 1], '("Hallo"~0.2 "Welt"~0.2)'],
            35 => ['Hallo  Welt', null, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 1], '("Hallo"~0.2 "Welt"~0.2)'],

            36 => ['Hallo  Welt', MKSEARCH_OP_AND, ['quote' => 0, 'dismax' => 0, 'fuzzy' => 1], '(+Hallo~0.2 +Welt~0.2)'],
            37 => ['Hallo  Welt', MKSEARCH_OP_OR, ['quote' => 0, 'dismax' => 0, 'fuzzy' => 1], '(Hallo~0.2 Welt~0.2)'],
            38 => ['Hallo  Welt', MKSEARCH_OP_EXACT, ['quote' => 0, 'dismax' => 0, 'fuzzy' => 1], '("Hallo Welt"~0.2)'],
            39 => ['Hallo  Welt', MKSEARCH_OP_FREE, ['quote' => 0, 'dismax' => 0, 'fuzzy' => 1], 'Hallo Welt'],
            40 => ['Hallo  Welt', MKSEARCH_OP_NONE, ['quote' => 0, 'dismax' => 0, 'fuzzy' => 1], '(Hallo~0.2 Welt~0.2)'],
            41 => ['Hallo  Welt', null, ['quote' => 0, 'dismax' => 0, 'fuzzy' => 1], '(Hallo~0.2 Welt~0.2)'],
            42 => [' Hallo  Welt texti:something "\' <script>', MKSEARCH_OP_AND, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 0, 'sanitize' => 1], '(+"Hallo" +"Welt" +"textisomething" +"script")'],
            43 => ['*', MKSEARCH_OP_AND, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 0, 'sanitize' => 1], ''],
            44 => ['hallo *welt', null, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 0, 'sanitize' => 1, 'wildcard' => 1], '("*hallo*" "*welt*")'],
            45 => [0, null, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 0, 'sanitize' => 1, 'wildcard' => 1], '("*0*")'],
            46 => ['', null, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 0, 'sanitize' => 1, 'wildcard' => 1], ''],
        ] as $key => $row) {
            $key = 'Line:'.$key.' Term:'.$row[0].' OP:'.$row[1].' Quote:'.$row[2]['quote'].' DisMax:'.$row[2]['dismax'].' Fuzzy:'.$row[2]['fuzzy'].' Result:'.$row[3];
            $return[$key] = $row;
        }

        return $return;
    }
}
