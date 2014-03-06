<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 das Medienkombinat GmbH <kontakt@das-medienkombinat.de>
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

require_once(t3lib_extMgm::extPath('rn_base', 'class.tx_rnbase.php'));
tx_rnbase::load('tx_phpunit_testcase');
tx_rnbase::load('tx_mksearch_indexer_FAL');

/**
 * Kindklasse des Indexers, um auf private Methoden zuzugreifen.
 *
 * @author Michael Wagner <michael.wagner@das-medienkombinat.de>
 */
class tx_mksearch_indexer_FALTest extends tx_mksearch_indexer_FAL {
	// wir wollen isIndexableRecord nicht erst public machen
	public function testIsIndexableRecord($tableName, $sourceRecord, $options) {
		return $this->isIndexableRecord($tableName, $sourceRecord, $options);
	}
}

/**
 * Tests für den Dam Media Indexer
 *
 * @author Michael Wagner <michael.wagner@das-medienkombinat.de>
 */
class tx_mksearch_tests_indexer_FAL_testcase
	extends tx_phpunit_testcase {

	private static $oFALTest = null;

	/**
	 * Constructs a test case with the given name.
	 *
	 * @param string $name
	 * @param array $data
	 * @param string $dataName
	 */
	public function __construct($name = NULL, array $data = array(), $dataName = '') {
		parent::__construct($name, $data, $dataName);
		self::$oFALTest = new tx_mksearch_indexer_FALTest();
	}

	/**
	 * (non-PHPdoc)
	 * @see PHPUnit_Framework_TestCase::setUp()
	 */
	protected function setUp() {
		// eventuelle hooks entfernen
		tx_mksearch_tests_Util::hooksSetUp(
			array(
				'indexerBase_preProcessSearchData',
				'indexerBase_postProcessSearchData',
			)
		);
	}

	/**
	 * tearDown() = destroy DB etc.
	 */
	public function tearDown () {
		// hooks zurücksetzen
		tx_mksearch_tests_Util::hooksTearDown();
	}

	/**
	 * @dataProvider providerIsIndexableRecord
	 */
	public function testIsIndexableRecord($aSourceRecord, $aOptions, $bIndexable) {
		$this->assertEquals(
				$bIndexable,
				self::$oFALTest->testIsIndexableRecord('sys_file', $aSourceRecord, array('sys_file.' => $aOptions))
			);
	}
	public function providerIsIndexableRecord() {
		return array(
				'Line: '. __LINE__ => array(
					array(
						'identifier' => 'unterordner/test.html',
						'extension' => 'html'
					), array(
						'byFileExtension' => 'pdf, html',
						'byDirectory' => '/^fileadmin\/.*\//',
					), true
				),
				'Line: '. __LINE__ => array(
					array(
						'identifier' => 'unterordner/test.html',
						'extension' => 'html'
					), array(
						'byFileExtension' => 'pdf, html',
						'byDirectory' => '/^fileadmin\/unterordner.*\//',
					), true
				),
				'Line: '. __LINE__ => array(
					array(
						'identifier' => 'unterordner/test.txt',
						'extension' => 'txt'
					), array(
						'byFileExtension' => 'pdf, html',
						'byDirectory' => '/^fileadmin\/unterordner.*\//',
					), false
				),
				'Line: '. __LINE__ => array(
					array(
						'identifier' => 'denied/test.txt',
						'extension' => 'txt'
					), array(
						'byFileExtension' => 'pdf, html',
						'byDirectory' => '/^fileadmin\/unterordner.*\//',
					), false
				),
				'Line: '. __LINE__ => array(
					array(
						'identifier' => 'allowed/test.pdf',
						'extension' => 'pdf'
					), array(
						'byFileExtension.' => array('pdf', 'txt'),
						'byDirectory.' => array('fileadmin/unterordner/', 'fileadmin/allowed/'),
					), true
				),
				'Line: '. __LINE__ => array(
					array(
						'identifier' => 'denied/test.pdf',
						'extension' => 'pdf'
					), array(
						'byFileExtension' => 'pdf, html',
						'byDirectory.' => array('fileadmin/unterordner/', 'fileadmin/allowed/'),
					), false
				),
				'Line: '. __LINE__ => array(
					array(
						'identifier' => 'unterordner/test.txt',
						'extension' => 'txt'
					), array(
						'byFileExtension' => 'html, xhtml',
						'byFileExtension.' => array('pdf', 'txt'),
						'byDirectory' => '/^fileadmin\/.*\//',
						'byDirectory.' => array('fileadmin/unterordner/', 'fileadmin/allowed/'),
					), true
				),
				'Line: '. __LINE__ => array(
					array(
						'identifier' => 'unterordner/subfolder/test.txt',
						'extension' => 'txt'
					), array(
						'byFileExtension' => 'html, xhtml',
						'byFileExtension.' => array('pdf', 'txt'),
						'byDirectory' => '/^fileadmin\/.*\//',
						'byDirectory.' => array('fileadmin/unterordner/', 'fileadmin/allowed/'),
					), false
				),
				'Line: '. __LINE__ => array(
					array(
						'identifier' => 'unterordner/subfolder/test.txt',
						'extension' => 'txt'
					), array(
						'byFileExtension' => 'html, xhtml',
						'byFileExtension.' => array('pdf', 'txt'),
						'byDirectory' => '/^fileadmin\/.*\//',
						'byDirectory.' => array('checkSubFolder' => 1,'fileadmin/unterordner/', 'fileadmin/allowed/'),
					), true
				),
				// spezieller eternit fall
				'Line: '. __LINE__ => array(
					array(
						'identifier' => 'downloads/tx_eternitdownload/test.txt',
						'extension' => 'txt'
					), array(
						'byFileExtension' => 'html, xhtml',
						'byFileExtension.' => array('pdf', 'txt'),
						'byDirectory' => '/^fileadmin\/.*\//',
						'byDirectory.' => array('checkSubFolder' => '1', 'fileadmin/downloads/' , '10' => 'fileadmin/downloads/tx_eternitdownload/', '10.' => array('disallow' => 1)),
					), false
				),
			);
	}

	/**
	 *
	 */
	public function testIsIndexableRecordWithoutDeleteIfNotIndexableOption() {
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_FAL');
		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
			'filter.' => array(
				'sys_file.'=>array('byFileExtension' => 'pdf, html'),
			),
			'deleteIfNotIndexable' => 0
		);

		$aRawData = array('uid' => 0, 'extension' => 'something_else');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$oIndexDoc = $indexer->prepareSearchData('sys_file', $aRawData, $indexDoc, $options);
		$this->assertNull($oIndexDoc,'Es wurde nicht null geliefert!');
	}

	/**
	 *
	 */
	public function testIsIndexableRecordWithDeleteIfNotIndexableOption() {
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_FAL');
		list($extKey, $cType) = $indexer->getContentType();
		$options = array(
			'filter.' => array(
				'sys_file.'=>array('byFileExtension' => 'pdf, html'),
			),
			'deleteIfNotIndexable' => 1
		);

		$aRawData = array('uid' => 1, 'extension' => 'something_else');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$oIndexDoc = $indexer->prepareSearchData('sys_file', $aRawData, $indexDoc, $options);
		$this->assertTrue($oIndexDoc->getDeleted(),'Das Element wurde nich auf gelöscht gesetzt!');
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_FAL_testcase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_FAL_testcase.php']);
}

?>