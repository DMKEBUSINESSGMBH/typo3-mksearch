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

require_once tx_rnbase_util_Extensions::extPath('mksearch', 'lib/Apache/Solr/Document.php');
tx_rnbase::load('tx_mksearch_tests_DbTestcase');
tx_rnbase::load('tx_mksearch_tests_Util');

/**
 * Wir müssen in diesem Fall mit der DB testen da wir die pages
 * Tabelle benötigen. in diesen tests haben die elemente mehrere
 * referenzen. die grundlegenden Funktionalitäten werden in
 * tx_mksearch_tests_indexer_TtContent_testcase und
 * tx_mksearch_tests_indexer_TtContent_DB_testcase geprüft
 *
 * @package tx_mksearch
 * @subpackage tx_mksearch_tests
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_indexer_TtContentTv_DB_testcase extends tx_mksearch_tests_DbTestcase
{

    /**
     * @var boolean
     */
    protected $unloadTemplavoila = false;

    /**
     * @var boolean
     */
    protected $gridelementsWasLoaded = false;

    /**
     * Constructs a test case with the given name.
     *
     * @param string $name the name of a testcase
     * @param array $data ?
     * @param string $dataName ?
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->importDataSets[] = tx_mksearch_tests_Util::getFixturePath('db/pages_tv.xml');
        $this->importDataSets[] = tx_mksearch_tests_Util::getFixturePath('db/tt_content_tv.xml');
        $this->importDataSets[] = tx_mksearch_tests_Util::getFixturePath('db/sys_refindex.xml');
    }

    /**
     * setUp() = init DB etc.
     */
    protected function setUp()
    {
        if (!tx_rnbase_util_Extensions::isLoaded('templavoila')) {
            $this->markTestSkipped('templavoila ist nicht Installiert.');
        }

        // gridelements steht im Konflikt mit templavoila
        if ($this->gridelementsWasLoaded = tx_rnbase_util_Extensions::isLoaded('gridelements')) {
            tx_mksearch_tests_Util::unloadExtensionForTypo362OrHigher('gridelements');
        }

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     * @see tx_mksearch_tests_DbTestcase::tearDown()
     */
    protected function tearDown()
    {
        if ($this->gridelementsWasLoaded) {
            $extensionManagementUtility = new TYPO3\CMS\Core\Utility\ExtensionManagementUtility();
            $extensionManagementUtility->loadExtension('gridelements');
        }

        parent::tearDown();
    }

    /**
     * @group integration
     */
    public function testPrepareSearchSetsCorrectPidOfReference()
    {
        $options = $this->getDefaultConfig();

        //should not be deleted as everything is correct with the page it is on
        $indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_TtContent');
        list($extKey, $cType) = $indexer->getContentType();
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        //even though the page of one reference is excluded we have still one valid one
        $options['exclude.']['pageTrees.'] = array(3);
        //the element it self resides on a hidden page but we have references that are okay
        $record = array('uid' => 1, 'pid' => 2, 'CType' => 'list', 'bodytext' => 'Test 1');
        $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertFalse($indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);
        $aData = $indexDoc->getData();
        self::assertEquals(1, $aData['pid']->getValue(), 'the new pid has not been set for '.$record['uid']);

        //should not be deleted as the element it is referenced on is on a valid page
        $indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_TtContent');
        list($extKey, $cType) = $indexer->getContentType();
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $record = array('uid' => 5, 'pid' => 0, 'CType' => 'list', 'bodytext' => 'Test 1');
        $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertFalse($indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);
        $aData = $indexDoc->getData();
        self::assertEquals(1, $aData['pid']->getValue(), 'the new pid has not been set for '.$record['uid']);

        //should be deleted as the element it is referenced on is on a valid page
        $indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_TtContent');
        list($extKey, $cType) = $indexer->getContentType();
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $record = array('uid' => 5, 'pid' => 0, 'CType' => 'list', 'bodytext' => 'Test 1');
        $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertFalse($indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);
    }

    /**
     * @group integration
     */
    public function testPrepareSearchCheckDeleted()
    {
        $options = $this->getDefaultConfig();

        //should be deleted as there is no reference
        $indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_TtContent');
        list($extKey, $cType) = $indexer->getContentType();
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $record = array('uid' => 99, 'pid' => 0, 'CType' => 'list', 'bodytext' => 'Test 1');
        $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertTrue($indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);

        //should be deleted as the page it is referenced on is hidden
        $options = $this->getDefaultConfig();
        $indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_TtContent');
        list($extKey, $cType) = $indexer->getContentType();
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        //the pid doesn't matter as it's taken from the reference to this element
        $record = array('uid' => 2, 'pid' => 0, 'CType' => 'list', 'bodytext' => 'Test 1');
        $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertTrue($indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);

        $options = $this->getDefaultConfig();
        //should be deleted as the element it is referenced on is on a none existent page
        $indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_TtContent');
        list($extKey, $cType) = $indexer->getContentType();
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $record = array('uid' => 98, 'pid' => 99, 'CType' => 'list', 'bodytext' => 'Test 1');
        $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertTrue($indexDoc->getDeleted(), 'Wrong deleted state for uid '.$record['uid']);
    }

    /**
     * @group integration
     */
    public function testPrepareSearchSetsCorrectisIndexableDependendOnPid()
    {
        $options = $this->getDefaultConfig();

        //should return null as the page the element is referenced on is excluded for indexing
        $indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_TtContent');
        list($extKey, $cType) = $indexer->getContentType();
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $record = array('uid' => 3, 'pid' => 0, 'CType' => 'list', 'bodytext' => 'Test 1');
        $options['exclude.']['pageTrees.'] = array(4);//als array
        $indexDoc = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNull($indexDoc, 'Index Doc not null for uid '.$record['uid']);

        //should return null as the page the element is referenced on is excluded for indexing
        $indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_TtContent');
        list($extKey, $cType) = $indexer->getContentType();
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $record = array('uid' => 6, 'pid' => 0, 'CType' => 'list', 'bodytext' => 'Test 1');
        $options['exclude.']['pageTrees.'] = array(4);//als array
        $indexDoc = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
        self::assertNull($indexDoc, 'Wrong deleted state for uid '.$record['uid']);
    }

    /**
     * @return array
     */
    protected function getDefaultConfig()
    {
        $options = array();
        $options['includeCTypes.'] = array('list');
        $options['CType.']['_default_.']['indexedFields.'] = array('bodytext', 'imagecaption' , 'altText', 'titleText');

        return $options;
    }
}
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContent_testcase.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContent_testcase.php']);
}
