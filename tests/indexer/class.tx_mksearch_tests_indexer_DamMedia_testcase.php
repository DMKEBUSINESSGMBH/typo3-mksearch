<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 DMK E-Business GmbH <dev@dmk-ebusiness.de>
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

tx_rnbase::load('tx_mksearch_tests_Testcase');
tx_rnbase::load('tx_mksearch_indexer_DamMedia');

/**
 * Kindklasse des Indexers, um auf private Methoden zuzugreifen.
 *
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_indexer_DamMediaTest extends tx_mksearch_indexer_DamMedia
{
    // wir wollen isIndexableRecord nicht erst public machen
    public function testIsIndexableRecord($tableName, $sourceRecord, $options)
    {
        return $this->isIndexableRecord($tableName, $sourceRecord, $options);
    }
}

/**
 * Tests für den Dam Media Indexer.
 *
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_indexer_DamMedia_testcase extends tx_mksearch_tests_Testcase
{
    private static $oDamMediaTest = null;

    /**
     * Constructs a test case with the given name.
     *
     * @param string $name
     * @param array  $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        self::$oDamMediaTest = new tx_mksearch_indexer_DamMediaTest();
    }

    /**
     * @dataProvider providerIsIndexableRecord
     */
    public function testIsIndexableRecord($aSourceRecord, $aOptions, $bIndexable)
    {
        self::assertEquals(
            $bIndexable,
            self::$oDamMediaTest->testIsIndexableRecord('tx_dam', $aSourceRecord, array('tx_dam.' => $aOptions))
        );
    }

    public function providerIsIndexableRecord()
    {
        return array(
                'Line: '.__LINE__ => array(
                    array(
                        'file_path' => 'fileadmin/unterordner/',
                        'file_type' => 'html',
                    ), array(
                        'byFileExtension' => 'pdf, html',
                        'byDirectory' => '/^fileadmin\/.*\//',
                    ), true,
                ),
                'Line: '.__LINE__ => array(
                    array(
                        'file_path' => 'fileadmin/unterordner/',
                        'file_type' => 'html',
                    ), array(
                        'byFileExtension' => 'pdf, html',
                        'byDirectory' => '/^fileadmin\/unterordner.*\//',
                    ), true,
                ),
                'Line: '.__LINE__ => array(
                    array(
                        'file_path' => 'fileadmin/unterordner/',
                        'file_type' => 'txt',
                    ), array(
                        'byFileExtension' => 'pdf, html',
                        'byDirectory' => '/^fileadmin\/unterordner.*\//',
                    ), false,
                ),
                'Line: '.__LINE__ => array(
                    array(
                        'file_path' => 'fileadmin/denied/',
                        'file_type' => 'txt',
                    ), array(
                        'byFileExtension' => 'pdf, html',
                        'byDirectory' => '/^fileadmin\/unterordner.*\//',
                    ), false,
                ),
                'Line: '.__LINE__ => array(
                    array(
                        'file_path' => 'fileadmin/allowed/',
                        'file_type' => 'pdf',
                    ), array(
                        'byFileExtension.' => array('pdf', 'txt'),
                        'byDirectory.' => array('fileadmin/unterordner/', 'fileadmin/allowed/'),
                    ), true,
                ),
                'Line: '.__LINE__ => array(
                    array(
                        'file_path' => 'fileadmin/denied/',
                        'file_type' => 'pdf',
                    ), array(
                        'byFileExtension' => 'pdf, html',
                        'byDirectory.' => array('fileadmin/unterordner/', 'fileadmin/allowed/'),
                    ), false,
                ),
                'Line: '.__LINE__ => array(
                    array(
                        'file_path' => 'fileadmin/unterordner/',
                        'file_type' => 'txt',
                    ), array(
                        'byFileExtension' => 'html, xhtml',
                        'byFileExtension.' => array('pdf', 'txt'),
                        'byDirectory' => '/^fileadmin\/.*\//',
                        'byDirectory.' => array('fileadmin/unterordner/', 'fileadmin/allowed/'),
                    ), true,
                ),
                'Line: '.__LINE__ => array(
                    array(
                        'file_path' => 'fileadmin/unterordner/subfolder/',
                        'file_type' => 'txt',
                    ), array(
                        'byFileExtension' => 'html, xhtml',
                        'byFileExtension.' => array('pdf', 'txt'),
                        'byDirectory' => '/^fileadmin\/.*\//',
                        'byDirectory.' => array('fileadmin/unterordner/', 'fileadmin/allowed/'),
                    ), false,
                ),
                'Line: '.__LINE__ => array(
                    array(
                        'file_path' => 'fileadmin/unterordner/subfolder/',
                        'file_type' => 'txt',
                    ), array(
                        'byFileExtension' => 'html, xhtml',
                        'byFileExtension.' => array('pdf', 'txt'),
                        'byDirectory' => '/^fileadmin\/.*\//',
                        'byDirectory.' => array('checkSubFolder' => 1, 'fileadmin/unterordner/', 'fileadmin/allowed/'),
                    ), true,
                ),
                // spezieller eternit fall
                'Line: '.__LINE__ => array(
                    array(
                        'file_path' => 'fileadmin/downloads/tx_eternitdownload/',
                        'file_type' => 'txt',
                    ), array(
                        'byFileExtension' => 'html, xhtml',
                        'byFileExtension.' => array('pdf', 'txt'),
                        'byDirectory' => '/^fileadmin\/.*\//',
                        'byDirectory.' => array('checkSubFolder' => '1', 'fileadmin/downloads/', '10' => 'fileadmin/downloads/tx_eternitdownload/', '10.' => array('disallow' => 1)),
                    ), false,
                ),
            );
    }

    public function testIsIndexableRecordWithoutDeleteIfNotIndexableOption()
    {
        $indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_DamMedia');
        list($extKey, $cType) = $indexer->getContentType();
        $options = array(
            'filter.' => array(
                'tx_dam.' => array('byFileExtension' => 'pdf, html'),
            ),
            'deleteIfNotIndexable' => 0,
        );

        $aRawData = array('uid' => 1, 'file_type' => 'something_else');
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $oIndexDoc = $indexer->prepareSearchData('tx_dam', $aRawData, $indexDoc, $options);
        self::assertNull($oIndexDoc, 'Es wurde nicht null geliefert!');
    }

    public function testIsIndexableRecordWithDeleteIfNotIndexableOption()
    {
        $indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_DamMedia');
        list($extKey, $cType) = $indexer->getContentType();
        $options = array(
            'filter.' => array(
                'tx_dam.' => array('byFileExtension' => 'pdf, html'),
            ),
            'deleteIfNotIndexable' => 1,
        );

        $aRawData = array('uid' => 1, 'file_type' => 'something_else');
        $indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase', $extKey, $cType);
        $oIndexDoc = $indexer->prepareSearchData('tx_dam', $aRawData, $indexDoc, $options);
        self::assertTrue($oIndexDoc->getDeleted(), 'Das Element wurde nich auf gelöscht gesetzt!');
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_DamMedia_testcase.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_DamMedia_testcase.php'];
}
