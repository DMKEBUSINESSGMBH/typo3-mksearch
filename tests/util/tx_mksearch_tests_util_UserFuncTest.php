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
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_util_UserFuncTest extends tx_mksearch_tests_Testcase
{
    #[PHPUnit\Framework\Attributes\DataProvider('providerSearchOptions')]
    public function testSearchSolrOptionsWithOptionsFromTs($term, $combination, array $options, $result): void
    {
        $options['combination'] = $combination;
        $res = tx_mksearch_util_UserFunc::searchSolrOptions($term, $options);
        self::assertEquals($result, $res);
    }

    #[PHPUnit\Framework\Attributes\DataProvider('providerSearchOptions')]
    public function testSearchLuceneOptionsWithOptionsFromTs($term, $combination, array $options, $result): void
    {
        $options['combination'] = $combination;
        $res = tx_mksearch_util_UserFunc::searchLuceneOptions($term, $options);
        self::assertEquals($result, $res);
    }

    public static function providerSearchOptions(): array
    {
        $return = [];
        foreach ([
            1 => ['', null, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 0, 'sanitize' => 1, 'wildcard' => 1], ''],
            2 => [0, null, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 0, 'sanitize' => 1, 'wildcard' => 1], '("*0*")'],
            3 => ['', null, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 0, 'sanitize' => 1, 'wildcard' => 1], ''],
        ] as $key => $row) {
            $key = 'Line:'.$key.' Term:'.$row[0].' OP:'.$row[1].' Quote:'.$row[2]['quote'].' DisMax:'.$row[2]['dismax'].' Fuzzy:'.$row[2]['fuzzy'].' Result:'.$row[3];
            $return[$key] = $row;
        }

        return $return;
    }
}
