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
class tx_mksearch_tests_util_SuggestionBuilderTest extends tx_mksearch_tests_Testcase
{
    public function testBuildSuggestionsWithEmptyFacetData()
    {
        $suggestionData = [];
        $suggestionData = tx_mksearch_util_SuggestionBuilder::getInstance()->buildSuggestions($suggestionData);
        self::assertTrue(is_array($suggestionData), 'es wurde kein array zurück gegeben!');
        self::assertTrue(empty($suggestionData), 'es wurde kein leeres array zurück gegeben!');
    }

    public function testBuildSuggestions()
    {
        $suggestionData = new stdClass();
        $suggestionData->searchWord = new stdClass();
        $suggestionData->searchWord->numFound = 2;
        $suggestionData->searchWord->startOffset = 0;
        $suggestionData->searchWord->endOffset = 3;
        $suggestionData->searchWord->suggestion = [
            0 => 'searchWordFoundOnce',
            1 => 'searchWordFoundTwice',
        ];
        $suggestionData->collation = 'test collation should be ignored.';

        $suggestionData = tx_mksearch_util_SuggestionBuilder::getInstance()->buildSuggestions($suggestionData);

        self::assertTrue(is_array($suggestionData), 'es wurde kein array zurück gegeben!');
        self::assertEquals(1, count($suggestionData), 'Das array hat nicht die richtige Größe!');
        self::assertEquals(1, $suggestionData['searchWord'][0]->getUid(), 'Datensatz 1 - getUid() hat den falschen Wert!');
        self::assertEquals('searchWordFoundOnce', $suggestionData['searchWord'][0]->getProperty('value'), 'Datensatz 1 - Feld:value hat den falschen Wert!');
        self::assertEquals('searchWord', $suggestionData['searchWord'][0]->getProperty('searchWord'), 'Datensatz 1 - Feld:searchWord hat den falschen Wert!');
        self::assertEquals(2, $suggestionData['searchWord'][1]->getUid(), 'Datensatz 2 - getUid() hat den falschen Wert!');
        self::assertEquals('searchWordFoundTwice', $suggestionData['searchWord'][1]->getProperty('value'), 'Datensatz 2 - Feld:value hat den falschen Wert!');
        self::assertEquals('searchWord', $suggestionData['searchWord'][1]->getProperty('searchWord'), 'Datensatz 2 - Feld:searchWord hat den falschen Wert!');
    }
}
