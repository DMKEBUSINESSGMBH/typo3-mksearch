<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 DMK E-Business GmbH
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
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_util_UserFuncTest extends tx_mksearch_tests_Testcase
{
    /**
     * @dataProvider providerSearchOptions
     */
    public function testSearchSolrOptionsWithOptionsFromTs($term, $combination, $options, $result)
    {
        $options['combination'] = $combination;
        $res = tx_mksearch_util_UserFunc::searchSolrOptions($term, $options);
        self::assertEquals($result, $res);
    }

    /**
     * @dataProvider providerSearchOptions
     */
    public function testSearchLuceneOptionsWithOptionsFromTs($term, $combination, $options, $result)
    {
        $options['combination'] = $combination;
        $res = tx_mksearch_util_UserFunc::searchLuceneOptions($term, $options);
        self::assertEquals($result, $res);
    }

    /**
     * @return array
     */
    public function providerSearchOptions()
    {
        $return = [];
        foreach ([
                         // array($term, $combination, $options, $result),
                __LINE__ => ['Hallo  Welt', MKSEARCH_OP_AND, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 0], '(+"Hallo" +"Welt")'],
                __LINE__ => [0, null, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 0, 'sanitize' => 1, 'wildcard' => 1], '("*0*")'],
                __LINE__ => ['', null, ['quote' => 1, 'dismax' => 0, 'fuzzy' => 0, 'sanitize' => 1, 'wildcard' => 1], ''],
            ] as $key => $row) {
            $key = 'Line:'.$key.' Term:'.$row[0].' OP:'.$row[1].' Quote:'.$row[2]['quote'].' DisMax:'.$row[2]['dismax'].' Fuzzy:'.$row[2]['fuzzy'].' Result:'.$row[3];
            $return[$key] = $row;
        }

        return $return;
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/util/class.tx_mksearch_tests_util_SearchBuilderTest.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/util/class.tx_mksearch_tests_util_SearchBuilderTest.php'];
}
