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

require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mksearch', 'lib/Apache/Solr/Document.php');

/**
 * Wir müssen in diesem Fall mit der DB testen da wir die pages
 * Tabelle benötigen.
 *
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_indexer_TtContent_DBTest extends tx_mksearch_tests_Testcase
{
    /**
     * Constructs a test case with the given name.
     *
     * @param string $name     the name of a testcase
     * @param array  $data     ?
     * @param string $dataName ?
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->importDataSets[] = tx_mksearch_tests_Util::getFixturePath('db/pages.xml');
    }

    public function testPrepareSearchDataAndCheckDeleted()
    {
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_TtContent');
        $options = $this->getDefaultConfig();

        list($extKey, $cType) = $indexer->getContentType();
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);

        //is deleted
        $record = ['uid' => 123, 'pid' => 1, 'deleted' => 1, 'CType' => 'list'];
        $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertEquals(true, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);

        //is hidden
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $record = ['uid' => 124, 'pid' => 1, 'deleted' => 0, 'hidden' => 1, 'CType' => 'list'];
        $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertEquals(true, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);

        //everything alright
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $record = ['uid' => 125, 'pid' => 1, 'deleted' => 0, 'hidden' => 0, 'header' => 'test', 'CType' => 'list'];
        $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertEquals(false, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);

        //everything alright
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $record = ['uid' => 126, 'pid' => 1, 'deleted' => 0, 'hidden' => 0, 'sectionIndex' => 1, 'header' => 'test', 'CType' => 'list'];
        $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertEquals(false, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);
        //data correct indexed?
        $aData = $indexDoc->getData();
        self::assertEquals('test', $aData['title']->getValue(), 'Wrong value for title for uid '.$record['uid']);
        self::assertEquals('test', $aData['abstract']->getValue(), 'Wrong value for title for uid '.$record['uid']);
        self::assertEquals('test ', $aData['content']->getValue(), 'Wrong value for title for uid '.$record['uid']);

        //no content
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $record = ['uid' => 128, 'pid' => 1, 'deleted' => 0, 'hidden' => 0, 'CType' => 'list'];
        $indexer->prepareSearchData('tt_content', $record, $indexDoc, array_merge($options, ['leaveHeaderEmpty' => true]));
        self::assertEquals(true, $indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);
    }

    /**
     * Prüft ob nur die Elemente indiziert werden, die im
     * angegebenen Seitenbaum liegen.
     */
    public function testPrepareSearchData_CheckIncludePageTrees()
    {
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_TtContent');
        list($extKey, $cType) = $indexer->getContentType();

        $record = ['uid' => 123, 'deleted' => 0, 'hidden' => 0, 'pid' => 3, 'CType' => 'list', 'bodytext' => 'test'];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        //damit es nicht an der Konfig scheitert
        $options = $this->getDefaultConfig();

        //testbeginn
        $options['include.']['pageTrees.'] = [1]; //als array
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNotNull($result, 'Element sollte nicht gelöscht werden, da im richtigen Seitenbaum. 1');

        unset($options['include.']['pageTrees.']); //zurücksetzen
        $options['include.']['pageTrees'] = '4'; //als string
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNull($result, 'Element sollte gelöscht werden, da nicht im richtigen Seitenbaum.');

        unset($options['include.']['pageTrees']); //zurücksetzen
        $options['include.']['pageTrees.'] = [4]; //als array
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNull($result, 'Element sollte gelöscht werden, da nicht im richtigen Seitenbaum.');

        $record2 = ['uid' => 123, 'deleted' => 0, 'hidden' => 0, 'pid' => 5, 'CType' => 'list', 'bodytext' => 'test'];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $options['include.']['pageTrees.'] = [1, 4]; //als array
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options, 1);
        self::assertNotNull($result, 'Element sollte nicht gelöscht werden, da im richtigen Seitenbaum. 2');
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $result = $indexer->prepareSearchData('tt_content', $record2, $indexDoc, $options, 1);
        self::assertNotNull($result, 'Element sollte nicht gelöscht werden, da im richtigen Seitenbaum. 3');

        //nichts explizit inkluded also alles rein
        unset($options['include.']['pageTrees.']); //zurücksetzen
        $record2 = ['uid' => 123, 'deleted' => 0, 'hidden' => 0, 'pid' => 5, 'CType' => 'list', 'bodytext' => 'test'];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options, 1);
        self::assertNotNull($result, 'Element sollte nicht gelöscht werden, da im richtigen Seitenbaum und nichts explizit included. 1');
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $result = $indexer->prepareSearchData('tt_content', $record2, $indexDoc, $options, 1);
        self::assertNotNull($result, 'Element sollte nicht gelöscht werden, da im richtigen Seitenbaum und nichts explizit included. 2');

        //klappt auch alles wenn das Element auf der obersten Ebene liegt?
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_TtContent');
        list($extKey, $cType) = $indexer->getContentType();

        $record = ['uid' => 123, 'deleted' => 0, 'hidden' => 0, 'pid' => 1, 'CType' => 'list', 'bodytext' => 'test'];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        //damit es nicht an der Konfig scheitert
        $options = $this->getDefaultConfig();

        //testbeginn
        $options['include.']['pageTrees.'] = [1]; //als array
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNotNull($result, 'Element sollte nicht gelöscht werden, da im richtigen Seitenbaum. 1');
    }

    /**
     * Prüft ob nicht die Elemente indiziert werden, die im
     * angegebenen Seitenbaum liegen.
     */
    public function testPrepareSearchData_CheckExcludePageTrees()
    {
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_TtContent');
        list($extKey, $cType) = $indexer->getContentType();

        $record = ['uid' => 123, 'deleted' => 0, 'hidden' => 0, 'pid' => 3, 'CType' => 'list', 'bodytext' => 'test'];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        //damit es nicht an der Konfig scheitert
        $options = $this->getDefaultConfig();

        //testbeginn
        $options['exclude.']['pageTrees.'] = [4]; //als array
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNotNull($result, 'Element sollte nicht gelöscht werden, da im richtigen Seitenbaum.');

        unset($options['exclude.']['pageTrees.']); //zurücksetzen
        $options['exclude.']['pageTrees'] = '1'; //als string
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNull($result, 'Element sollte gelöscht werden, da nicht im richtigen Seitenbaum. 1');

        unset($options['exclude.']['pageTrees']); //zurücksetzen
        $options['exclude.']['pageTrees.'] = [1]; //als array
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNull($result, 'Element sollte gelöscht werden, da nicht im richtigen Seitenbaum. 2');

        $record2 = ['uid' => 123, 'deleted' => 0, 'hidden' => 0, 'pid' => 5, 'CType' => 'list', 'bodytext' => 'test'];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $options['exclude.']['pageTrees.'] = [1, 4]; //als array
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options, 1);
        self::assertNull($result, 'Element sollte gelöscht werden, da im richtigen Seitenbaum. 3');
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $result = $indexer->prepareSearchData('tt_content', $record2, $indexDoc, $options, 1);
        self::assertNull($result, 'Element sollte gelöscht werden, da im richtigen Seitenbaum. 4');

        //nichts explizit excluded also alles rein
        unset($options['exclude.']['pageTrees.']); //zurücksetzen
        $record2 = ['uid' => 123, 'deleted' => 0, 'hidden' => 0, 'pid' => 5, 'CType' => 'list', 'bodytext' => 'test'];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options, 1);
        self::assertNotNull($result, 'Element sollte nicht gelöscht werden, da nichts excluded. 1');
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $result = $indexer->prepareSearchData('tt_content', $record2, $indexDoc, $options, 1);
        self::assertNotNull($result, 'Element sollte nicht gelöscht werden, da nichts excluded. 2');

        //klappt esw auch wenn das Element auf der obersten Ebene liegt?
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_TtContent');
        list($extKey, $cType) = $indexer->getContentType();

        $record = ['uid' => 123, 'deleted' => 0, 'hidden' => 0, 'pid' => 1, 'CType' => 'list', 'bodytext' => 'test'];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        //damit es nicht an der Konfig scheitert
        $options = $this->getDefaultConfig();

        unset($options['exclude.']['pageTrees.']); //zurücksetzen
        $options['exclude.']['pageTrees'] = '1'; //als string
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNull($result, 'Element sollte gelöscht werden, da nicht im richtigen Seitenbaum. 1');
    }

    public function testPrepareSearchDataPutsCorrectElementsIntoTheQueueIfTablePagesAndPageHasSubpages()
    {
        $this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/tt_content.xml'));
        //damit es nicht an der Konfig scheitert
        $options = $this->getDefaultConfig();

        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_TtContent');
        list($extKey, $cType) = $indexer->getContentType();

        $record = ['uid' => 1];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);

        $result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);

        //nichtzs direkt indiziert?
        self::assertNull($result, 'Es wurde nicht null zurück gegeben!');

        $aOptions = [
            'enablefieldsoff' => true,
        ];
        $aResult = \Sys25\RnBase\Database\Connection::getInstance()->doSelect('*', 'tx_mksearch_queue', $aOptions);

        self::assertEquals(4, count($aResult), 'Es wurde nicht der richtige Anzahl in die queue gelegt!');

        //elemente auf der eigentlichen seite
        self::assertEquals('tt_content', $aResult[0]['tablename'], 'Es wurde nicht das richtige Element (tablename) in die queue gelegt! Element 1');
        self::assertEquals(1, $aResult[0]['recid'], 'Es wurde nicht das richtige Element (recid) in die queue gelegt! Element 1');
        self::assertEquals('tt_content', $aResult[1]['tablename'], 'Es wurde nicht das richtige Element (tablename) in die queue gelegt! Element 2');
        self::assertEquals(2, $aResult[1]['recid'], 'Es wurde nicht das richtige Element (recid) in die queue gelegt! Element 2');
        //elemente von unterseiten
        self::assertEquals('tt_content', $aResult[2]['tablename'], 'Es wurde nicht das richtige Element (tablename) in die queue gelegt! Element 3');
        self::assertEquals(4, $aResult[2]['recid'], 'Es wurde nicht das richtige Element (recid) in die queue gelegt! Element 3');
        self::assertEquals('tt_content', $aResult[3]['tablename'], 'Es wurde nicht das richtige Element (tablename) in die queue gelegt! Element 4');
        self::assertEquals(5, $aResult[3]['recid'], 'Es wurde nicht das richtige Element (recid) in die queue gelegt! Element 4');
    }

    public function testPrepareSearchDataDoesNothingIfTablePagesButPageNotExistent()
    {
        $this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/tt_content.xml'));
        //damit es nicht an der Konfig scheitert
        $options = $this->getDefaultConfig();

        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_TtContent');
        list($extKey, $cType) = $indexer->getContentType();

        $record = ['uid' => 99];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);

        $result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);

        //nichtzs direkt indiziert?
        self::assertNull($result, 'Es wurde nicht null zurück gegeben!');

        $aOptions = [
            'enablefieldsoff' => true,
        ];
        $aResult = \Sys25\RnBase\Database\Connection::getInstance()->doSelect('*', 'tx_mksearch_queue', $aOptions);

        self::assertEmpty($aResult, 'Es wurde doch etwas in die queue gelegt!');
    }

    public function testPrepareSearchDataDoesNothingIfTablePagesButPageHasNoContent()
    {
        $this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/tt_content.xml'));
        //damit es nicht an der Konfig scheitert
        $options = $this->getDefaultConfig();

        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_TtContent');
        list($extKey, $cType) = $indexer->getContentType();

        $record = ['uid' => 5];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);

        $result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);

        //nichtzs direkt indiziert?
        self::assertNull($result, 'Es wurde nicht null zurück gegeben!');

        $aOptions = [
            'enablefieldsoff' => true,
        ];
        $aResult = \Sys25\RnBase\Database\Connection::getInstance()->doSelect('*', 'tx_mksearch_queue', $aOptions);

        self::assertEmpty($aResult, 'Es wurde doch etwas in die queue gelegt!');
    }

    public function testPrepareSearchDataPutsCorrectElementsIntoTheQueueIfTablePagesAndPageIsHidden()
    {
        $this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/tt_content.xml'));
        //damit es nicht an der Konfig scheitert
        $options = $this->getDefaultConfig();

        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_TtContent');
        list($extKey, $cType) = $indexer->getContentType();

        $record = ['uid' => 7];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);

        $result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);

        //nichtzs direkt indiziert?
        self::assertNull($result, 'Es wurde nicht null zurück gegeben!');

        $aOptions = [
            'enablefieldsoff' => true,
        ];
        $aResult = \Sys25\RnBase\Database\Connection::getInstance()->doSelect('*', 'tx_mksearch_queue', $aOptions);

        self::assertEquals(2, count($aResult), 'Es wurde nicht der richtige Anzahl in die queue gelegt!');
        self::assertEquals('tt_content', $aResult[0]['tablename'], 'Es wurde nicht das richtige Element (tablename) in die queue gelegt! Element 1');
        self::assertEquals(6, $aResult[0]['recid'], 'Es wurde nicht das richtige Element (recid) in die queue gelegt! Element 1');
        self::assertEquals('tt_content', $aResult[1]['tablename'], 'Es wurde nicht das richtige Element (tablename) in die queue gelegt! Element 2');
        self::assertEquals(3, $aResult[1]['recid'], 'Es wurde nicht das richtige Element (recid) in die queue gelegt! Element 2');
    }

    public function testPrepareSearchDataPutsCorrectElementsIntoTheQueueIfTablePagesAndPageHasNoSubpages()
    {
        $this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/tt_content.xml'));
        //damit es nicht an der Konfig scheitert
        $options = $this->getDefaultConfig();

        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_TtContent');
        list($extKey, $cType) = $indexer->getContentType();

        $record = ['uid' => 2];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);

        $result = $indexer->prepareSearchData('pages', $record, $indexDoc, $options);

        //nichtzs direkt indiziert?
        self::assertNull($result, 'Es wurde nicht null zurück gegeben!');

        $aOptions = [
            'enablefieldsoff' => true,
        ];
        $aResult = \Sys25\RnBase\Database\Connection::getInstance()->doSelect('*', 'tx_mksearch_queue', $aOptions);

        self::assertEquals(1, count($aResult), 'Es wurde nicht der richtige Anzahl in die queue gelegt!');
        self::assertEquals('tt_content', $aResult[0]['tablename'], 'Es wurde nicht das richtige Element (tablename) in die queue gelegt! Element 1');
        self::assertEquals(3, $aResult[0]['recid'], 'Es wurde nicht das richtige Element (recid) in die queue gelegt! Element 1');
    }

    public function testPrepareSearchDataSetsDocToDeletedIfPageIsNotValid()
    {
        $this->importDataSet(tx_mksearch_tests_Util::getFixturePath('db/tt_content.xml'));

        //damit es nicht an der Konfig scheitert
        $options = $this->getDefaultConfig();

        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_TtContent');
        list($extKey, $cType) = $indexer->getContentType();

        //seite ist versteckt
        $record = ['uid' => 123, 'pid' => 7, 'deleted' => 0, 'hidden' => 0, 'CType' => 'list', 'bodytext' => 'test'];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertTrue($result->getDeleted(), 'Das Element wurde nicht auf gelöscht gesetzt! Pid: 7');

        //seite ist gelöscht
        $record = ['uid' => 123, 'pid' => 8, 'deleted' => 0, 'hidden' => 0, 'CType' => 'list', 'bodytext' => 'test'];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertTrue($result->getDeleted(), 'Das Element wurde nicht auf gelöscht gesetzt! Pid: 8');

        //alles okay
        $record = ['uid' => 123, 'pid' => 6, 'deleted' => 0, 'hidden' => 0, 'CType' => 'list', 'bodytext' => 'test'];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertFalse($result->getDeleted(), 'Das Element wurde auf gelöscht gesetzt! Pid: 6');

        //seite ist hart gelöscht bzw. nicht vorhanden
        $record = ['uid' => 123, 'pid' => 99, 'deleted' => 0, 'hidden' => 0, 'CType' => 'list', 'bodytext' => 'test'];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertTrue($result->getDeleted(), 'Das Element wurde nicht auf gelöscht gesetzt! Pid: 8');

        //seite ist nicht gelöscht aber der parent ist versteckt
        $record = ['uid' => 123, 'pid' => 10, 'deleted' => 0, 'hidden' => 0, 'CType' => 'list', 'bodytext' => 'test'];
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertTrue($result->getDeleted(), 'Das Element wurde nicht auf gelöscht gesetzt! Pid: 10');
    }

    /**
     * @depends testPrepareSearchDataSetsDocToDeletedIfPageIsNotValid
     */
    public function testPrepareSearchDataSetsDocToIndexableConsideringIsIndexableField()
    {
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_TtContent');
        list($extKey, $cType) = $indexer->getContentType();

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $options = $this->getDefaultConfig();

        // nicht im richtigen Seitenbaum und
        //tx_mksearch_is_indexable = tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION
        $record = [
            'uid' => 123, 'deleted' => 0, 'hidden' => 0,
            'pid' => 3, 'CType' => 'list', 'bodytext' => 'test',
            'tx_mksearch_is_indexable' => tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION,
        ];
        $options['exclude.']['pageTrees'] = '1'; //als string
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNull($result, 'Element sollte gelöscht werden, da nicht im richtigen Seitenbaum. 1');

        //nicht im richtigen Seitenbaum aber
        //tx_mksearch_is_indexable = tx_mksearch_indexer_ttcontent_Normal::IS_INDEXABLE
        $record['tx_mksearch_is_indexable'] = tx_mksearch_indexer_ttcontent_Normal::IS_INDEXABLE;
        $options['exclude.']['pageTrees'] = '1'; //als string
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNotNull($result, 'Element sollte nicht gelöscht werden, da zwar nicht im richtigen Seitenbaum aber tx_mksearch_indexer_ttcontent_Normal::IS_INDEXABLE.');

        //ist zwar im richtigen Seitenbaum aber
        //tx_mksearch_is_indexable = tx_mksearch_indexer_ttcontent_Normal::IS_NOT_INDEXABLE
        $record['tx_mksearch_is_indexable'] = tx_mksearch_indexer_ttcontent_Normal::IS_NOT_INDEXABLE;
        unset($options['exclude.']['pageTrees.']); //zurücksetzen
        $options['exclude.']['pageTrees.'] = [4]; //als array
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNull($result, 'Element ist zwar im richtigen Seitenbaum aber soll nicht indiziert werden.');
    }

    public function testPrepareSearchDataIndexesHeaderLayoutCorrect()
    {
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_TtContent');
        list($extKey, $cType) = $indexer->getContentType();

        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $options = $this->getDefaultConfig();

        //header should be indexed
        $record = ['uid' => 123, 'pid' => 6, 'CType' => 'list', 'header' => 'test', 'header_layout' => 101];
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options)->getData();
        self::assertEquals('test', $result['title']->getValue(), 'Titel nicht indiziert!');

        //header shouldn't be indexed
        $record = ['uid' => 123, 'pid' => 6, 'CType' => 'list', 'header' => 'test', 'header_layout' => 100];
        $result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, array_merge($options, ['leaveHeaderEmpty' => true]))->getData();
        self::assertEquals('', $result['title']->getValue(), 'Titel doch indiziert!');
    }

    public function testPrepareSearchDataIndexesEnableColumnsOfThePageToo()
    {
        $indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_indexer_TtContent');
        $options = $this->getDefaultConfig();

        list($extKey, $cType) = $indexer->getContentType();
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);

        //everything alright
        $indexDoc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $record = [
            'uid' => 125,
            'pid' => 1,
            'deleted' => 0,
            'hidden' => 0,
            'header' => 'test',
            'CType' => 'list',
            'starttime' => 5,
            'endtime' => 6,
            'fe_group' => 7,
        ];

        $indexDoc = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        $indexedData = $indexDoc->getData();

        self::assertEquals('1970-01-01T00:00:05Z', $indexedData['starttime_dt']->getValue());
        self::assertEquals('1970-01-01T00:00:06Z', $indexedData['endtime_dt']->getValue());
        self::assertEquals([0 => 7], $indexedData['fe_group_mi']->getValue());

        self::assertEquals('1970-01-01T00:00:02Z', $indexedData['page_starttime_dt']->getValue());
        self::assertEquals('2030-07-06T09:13:43Z', $indexedData['page_endtime_dt']->getValue());
        self::assertEquals([0 => 4], $indexedData['page_fe_group_mi']->getValue());
    }

    /**
     * @return array
     */
    protected function getDefaultConfig()
    {
        $options = [];
        $options['includeCTypes.'] = ['list'];
        $options['CType.']['_default_.']['indexedFields.'] = ['bodytext', 'imagecaption', 'altText', 'titleText'];

        return $options;
    }
}
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContentTest.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContentTest.php'];
}
