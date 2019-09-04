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
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_util_SearchBuilderTest extends tx_mksearch_tests_Testcase
{
    /**
     * @dataProvider providerSearchSolrOptions
     */
    public function testSearchSolrOptions($term, $combination, $options, $result)
    {
        $res = tx_mksearch_util_SearchBuilder::searchSolrOptions($term, $combination, $options);
        self::assertEquals($result, $res);
    }

    public function providerSearchSolrOptions()
    {
        $return = array();
        foreach (array(
                         // array($term, $combination, $options, $result),
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_AND, array('quote' => 1, 'dismax' => 0, 'fuzzy' => 0), '(+"Hallo" +"Welt")'),
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_OR, array('quote' => 1, 'dismax' => 0, 'fuzzy' => 0), '("Hallo" "Welt")'),
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_EXACT, array('quote' => 1, 'dismax' => 0, 'fuzzy' => 0), '("Hallo Welt")'),
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_FREE, array('quote' => 1, 'dismax' => 0, 'fuzzy' => 0), 'Hallo Welt'),
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_NONE, array('quote' => 1, 'dismax' => 0, 'fuzzy' => 0), '("Hallo" "Welt")'),
                __LINE__ => array('Hallo  Welt', null, array('quote' => 1, 'dismax' => 0, 'fuzzy' => 0), '("Hallo" "Welt")'),

                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_AND, array('quote' => 0, 'dismax' => 0, 'fuzzy' => 0), '(+Hallo +Welt)'),
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_OR, array('quote' => 0, 'dismax' => 0, 'fuzzy' => 0), '(Hallo Welt)'),
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_EXACT, array('quote' => 0, 'dismax' => 0, 'fuzzy' => 0), '("Hallo Welt")'),
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_FREE, array('quote' => 0, 'dismax' => 0, 'fuzzy' => 0), 'Hallo Welt'),
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_NONE, array('quote' => 0, 'dismax' => 0, 'fuzzy' => 0), '(Hallo Welt)'),
                __LINE__ => array('Hallo  Welt', null, array('quote' => 0, 'dismax' => 0), '(Hallo Welt)'),

                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_AND, array('quote' => 1, 'dismax' => 1, 'fuzzy' => 0), '+"Hallo" +"Welt"'),
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_OR, array('quote' => 1, 'dismax' => 1, 'fuzzy' => 0), '"Hallo" "Welt"'),
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_EXACT, array('quote' => 1, 'dismax' => 1, 'fuzzy' => 0), '"Hallo Welt"'),
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_FREE, array('quote' => 1, 'dismax' => 1, 'fuzzy' => 0), 'Hallo Welt'),
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_NONE, array('quote' => 1, 'dismax' => 1, 'fuzzy' => 0), '"Hallo" "Welt"'),
                __LINE__ => array('Hallo  Welt', null, array('quote' => 1, 'dismax' => 1, 'fuzzy' => 0), '"Hallo" "Welt"'),

                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_AND, array('quote' => 0, 'dismax' => 1, 'fuzzy' => 0), '+Hallo +Welt'),
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_OR, array('quote' => 0, 'dismax' => 1, 'fuzzy' => 0), 'Hallo Welt'),
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_EXACT, array('quote' => 0, 'dismax' => 1, 'fuzzy' => 0), '"Hallo Welt"'),
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_FREE, array('quote' => 0, 'dismax' => 1, 'fuzzy' => 0), 'Hallo Welt'),
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_NONE, array('quote' => 0, 'dismax' => 1, 'fuzzy' => 0), 'Hallo Welt'),
                __LINE__ => array('Hallo  Welt', null, array('quote' => 0, 'dismax' => 1, 'fuzzy' => 0), 'Hallo Welt'),

                //@TODO: doppelte operatoren müssen verhindert werden!
//              __LINE__ => array('+Hallo  -Welt', MKSEARCH_OP_AND, array('quote' => 1, 'dismax' => 0, 'fuzzy' => 0), '(+"Hallo" -"Welt")'),
                __LINE__ => array('+Hallo  -Welt', MKSEARCH_OP_OR, array('quote' => 1, 'dismax' => 0, 'fuzzy' => 0), '(+"Hallo" -"Welt")'),
                __LINE__ => array('+Hallo  -Welt', MKSEARCH_OP_EXACT, array('quote' => 1, 'dismax' => 0, 'fuzzy' => 0), '("+Hallo -Welt")'),
                __LINE__ => array('+Hallo  -Welt', MKSEARCH_OP_FREE, array('quote' => 1, 'dismax' => 0, 'fuzzy' => 0), '+Hallo -Welt'),
                __LINE__ => array('+Hallo  -Welt', MKSEARCH_OP_NONE, array('quote' => 1, 'dismax' => 0, 'fuzzy' => 0), '(+"Hallo" -"Welt")'),
                __LINE__ => array('+Hallo  -Welt', null, array('quote' => 1, 'dismax' => 0, 'fuzzy' => 0), '(+"Hallo" -"Welt")'),

                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_AND, array('quote' => 1, 'dismax' => 0, 'fuzzy' => 1), '(+"Hallo"~0.2 +"Welt"~0.2)'),
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_OR, array('quote' => 1, 'dismax' => 0, 'fuzzy' => 1), '("Hallo"~0.2 "Welt"~0.2)'),
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_EXACT, array('quote' => 1, 'dismax' => 0, 'fuzzy' => 1), '("Hallo Welt"~0.2)'),
                // bei free gibts kein fuzzy!
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_FREE, array('quote' => 1, 'dismax' => 0, 'fuzzy' => 1), 'Hallo Welt'),
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_NONE, array('quote' => 1, 'dismax' => 0, 'fuzzy' => 1), '("Hallo"~0.2 "Welt"~0.2)'),
                __LINE__ => array('Hallo  Welt', null, array('quote' => 1, 'dismax' => 0, 'fuzzy' => 1), '("Hallo"~0.2 "Welt"~0.2)'),

                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_AND, array('quote' => 0, 'dismax' => 0, 'fuzzy' => 1), '(+Hallo~0.2 +Welt~0.2)'),
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_OR, array('quote' => 0, 'dismax' => 0, 'fuzzy' => 1), '(Hallo~0.2 Welt~0.2)'),
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_EXACT, array('quote' => 0, 'dismax' => 0, 'fuzzy' => 1), '("Hallo Welt"~0.2)'),
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_FREE, array('quote' => 0, 'dismax' => 0, 'fuzzy' => 1), 'Hallo Welt'),
                __LINE__ => array('Hallo  Welt', MKSEARCH_OP_NONE, array('quote' => 0, 'dismax' => 0, 'fuzzy' => 1), '(Hallo~0.2 Welt~0.2)'),
                __LINE__ => array('Hallo  Welt', null, array('quote' => 0, 'dismax' => 0, 'fuzzy' => 1), '(Hallo~0.2 Welt~0.2)'),
                __LINE__ => array(' Hallo  Welt texti:something "\' <script>', MKSEARCH_OP_AND, array('quote' => 1, 'dismax' => 0, 'fuzzy' => 0, 'sanitize' => 1), '(+"Hallo" +"Welt" +"textisomething" +"script")'),
                __LINE__ => array('*', MKSEARCH_OP_AND, array('quote' => 1, 'dismax' => 0, 'fuzzy' => 0, 'sanitize' => 1), ''),
                __LINE__ => array('hallo *welt', null, array('quote' => 1, 'dismax' => 0, 'fuzzy' => 0, 'sanitize' => 1, 'wildcard' => 1), '("*hallo*" "*welt*")'),
                __LINE__ => array(0, null, array('quote' => 1, 'dismax' => 0, 'fuzzy' => 0, 'sanitize' => 1, 'wildcard' => 1), '("*0*")'),
                __LINE__ => array('', null, array('quote' => 1, 'dismax' => 0, 'fuzzy' => 0, 'sanitize' => 1, 'wildcard' => 1), ''),
            ) as $key => $row) {
            $key = 'Line:'.$key.' Term:'.$row[0].' OP:'.$row[1].' Quote:'.$row[2]['quote'].' DisMax:'.$row[2]['dismax'].' Fuzzy:'.$row[2]['fuzzy'].' Result:'.$row[3];
            $return[$key] = $row;
        }

        return $return;
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/util/class.tx_mksearch_tests_util_SearchBuilderTest.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/util/class.tx_mksearch_tests_util_SearchBuilderTest.php'];
}
