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

tx_rnbase::load('tx_mksearch_tests_DbTestcase');
tx_rnbase::load('tx_mksearch_tests_fixtures_indexer_Dummy');
tx_rnbase::load('tx_mksearch_tests_Util');

/**
 * Wir müssen in diesem Fall mit der DB testen da wir definitiv
 * mindestens bis hasDocToBeDeleted() laufen. Dort wird die rootline geprüft
 * und daher benötigen wir für alle Tests, die Funktionalitäten
 * nach diesem Punkt testen eine leere DB um alles in einem erwarteten Zustand
 * zu haben. Sonst werden die tatsächlich vorhandenen pages geprüft und
 * diese können auf jedem system unterschiedlich sein.
 *
 * @author Hannes Bochmann
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_indexer_Base_DB_testcase extends tx_mksearch_tests_DbTestcase
{
    public function testIsIndexableRecordWithInclude()
    {
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();
        $options = array(
                'include.' => array('pages.' => array(2)),
        );

        $aRawData = array('uid' => 1, 'pid' => 1);
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNull($aIndexDoc, 'Die Indizierung wurde doch indiziert! 1');

        $aRawData = array('uid' => 1, 'pid' => 2);
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNotNull($aIndexDoc, 'Die Indizierung wurde doch indiziert! 2');

        //noch mit kommaseparierter liste
        unset($options);
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();
        $options = array(
                'include.' => array('pages' => '2,3'),
        );

        $aRawData = array('uid' => 1, 'pid' => 1);
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNull($aIndexDoc, 'Die Indizierung wurde doch indiziert! 3');

        $aRawData = array('uid' => 1, 'pid' => 2);
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNotNull($aIndexDoc, 'Die Indizierung wurde doch indiziert! 4');

        $aRawData = array('uid' => 1, 'pid' => 3);
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNotNull($aIndexDoc, 'Die Indizierung wurde doch indiziert! 5');
    }

    public function testIsIndexableRecordWithIncludeAndDeleteIfNotIndexableOption()
    {
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();
        $options = array(
                'include.' => array('pages.' => array(2)),
                'deleteIfNotIndexable' => 1,
        );

        $aRawData = array('uid' => 1, 'pid' => 1);
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $oIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertTrue($oIndexDoc->getDeleted(), 'Das Element wurde doch indiziert! pid:1');
        //es sollte auch keine exception durch getPrimaryKey geworfen werden damit das Löschen auch wirklich funktioniert
        self::assertEquals('mksearch:dummy:1', $oIndexDoc->getPrimaryKey(true), 'Das Element hat nicht den richtigen primary key! pid:1');

        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $aRawData = array('uid' => 1, 'pid' => 2);
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $oIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertFalse($oIndexDoc->getDeleted(), 'Das Element wurde doch indiziert! pid:2');
    }

    public function testIsIndexableRecordWithExclude()
    {
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();
        $options = array(
                'exclude.' => array('pages.' => array(2)),
        );

        $aRawData = array('uid' => 1, 'pid' => 1);
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNotNull($aIndexDoc, 'Das Element wurde nicht indiziert! 1');

        $aRawData = array('uid' => 1, 'pid' => 2);
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNull($aIndexDoc, 'Das Element wurde doch indiziert! 2');

        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();
        $options = array(
                'exclude.' => array('pages' => '2,3'),
        );

        $aRawData = array('uid' => 1, 'pid' => 1);
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNotNull($aIndexDoc, 'Das Element wurde nicht indiziert! 3');

        $aRawData = array('uid' => 1, 'pid' => 2);
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNull($aIndexDoc, 'Das Element wurde doch indiziert! 4');

        $aRawData = array('uid' => 1, 'pid' => 3);
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNull($aIndexDoc, 'Das Element wurde doch indiziert! 5');
    }

    public function testIndexModelByMapping()
    {
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $options = array();

        $aRawData = array('uid' => 1, 'test_field_1' => '<a href="http://www.test.de">test value 1</a>', 'test_field_2' => 'test value 2');

        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options)->getData();

        self::assertEquals('test value 1', $aIndexDoc['test_field_1_s']->getValue(), 'Es wurde nicht das erste Feld richtig gesetzt!');
        self::assertEquals('test value 2', $aIndexDoc['test_field_2_s']->getValue(), 'Es wurde nicht das zweite Feld richtig gesetzt!');
        //with keep html
        self::assertEquals('<a href="http://www.test.de">test value 1</a>', $aIndexDoc['keepHtml_test_field_1_s']->getValue(), 'Es wurde nicht das erste Feld richtig gesetzt!');
        self::assertEquals('test value 2', $aIndexDoc['keepHtml_test_field_2_s']->getValue(), 'Es wurde nicht das zweite Feld richtig gesetzt!');
    }

    public function testIndexArrayOfModelsByMapping()
    {
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $options = array();

        $aRawData = array('uid' => 1, 'test_field_1' => 'test value 1', 'test_field_2' => 'test value 2', 'multiValue' => true);

        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options)->getData();

        self::assertEquals(array(0 => 'test value 1', 1 => 'test value 1'), $aIndexDoc['multivalue_test_field_1_s']->getValue(), 'Es wurde nicht das erste Feld richtig gesetzt!');
        self::assertEquals(array(0 => 'test value 2', 1 => 'test value 2'), $aIndexDoc['multivalue_test_field_2_s']->getValue(), 'Es wurde nicht das zweite Feld richtig gesetzt!');
    }

    /**
     * Check if setting the doc to deleted works.
     */
    public function testHasDocToBeDeletedConsideringOnlyTheModelItself()
    {
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();
        $options = array();

        //is hidden
        $oIndexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aRawData = array('uid' => 1, 'hidden' => 1);
        $oIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $oIndexDoc, $options);
        self::assertTrue($oIndexDoc->getDeleted(), 'Das Index Doc wurde nicht auf gelöscht gesetzt! 1');

        //is deleted
        $oIndexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aRawData = array('uid' => 1, 'hidden' => 0, 'deleted' => 1);
        $oIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $oIndexDoc, $options);
        self::assertTrue($oIndexDoc->getDeleted(), 'Das Index Doc wurde nicht auf gelöscht gesetzt! 2');

        //everything alright
        $oIndexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aRawData = array('uid' => 1, 'hidden' => 0, 'deleted' => 0);
        $oIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $oIndexDoc, $options);
        self::assertFalse($oIndexDoc->getDeleted(), 'Das Index Doc wurde doch auf gelöscht gesetzt! 3');
    }

    public function testHasDocToBeDeletedConsideringStatesOfTheParentPages()
    {
        $this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/pages.xml'));

        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $options = array();

        list($extKey, $cType) = $indexer->getContentType();

        //parent is hidden
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $record = array('uid' => 10, 'pid' => 7);
        $indexer->prepareSearchData('doesnt_matter', $record, $indexDoc, $options);
        self::assertEquals(true, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);

        //parent of parent is hidden
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $record = array('uid' => 127, 'pid' => 10);
        $indexer->prepareSearchData('doesnt_matter', $record, $indexDoc, $options);
        self::assertEquals(true, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);

        //everything alright with parents
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $record = array('uid' => 125, 'pid' => 3);
        $indexer->prepareSearchData('doesnt_matter', $record, $indexDoc, $options);
        self::assertEquals(false, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);
    }

    public function testStopIndexing()
    {
        $indexer = tx_rnbase::makeInstance('tx_mksearch_tests_fixtures_indexer_Dummy');
        list($extKey, $cType) = $indexer->getContentType();
        $options = array();

        $aRawData = array('uid' => 1, 'stopIndexing' => true);
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNull($aIndexDoc, 'Die Indizierung wurde nicht gestoppt!');

        $aRawData = array('uid' => 1, 'stopIndexing' => false);
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $aIndexDoc = $indexer->prepareSearchData('doesnt_matter', $aRawData, $indexDoc, $options);
        self::assertNotNull($aIndexDoc, 'Die Indizierung wurde gestoppt!');
    }
}
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContent_testcase.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContent_testcase.php'];
}
