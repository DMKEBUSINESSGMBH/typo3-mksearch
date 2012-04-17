<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 das Medienkombinat GmbH
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

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');


require_once(t3lib_extMgm::extPath('mksearch') . 'lib/Apache/Solr/Document.php');

/**
 * @backupStaticAttributes enabled
 * @author Hannes Bochmann
 *
 */
class tx_mksearch_tests_indexer_TtContent_testcase extends tx_phpunit_testcase {

	protected $aTvConfig;
	
	/**
	 * unset templavoila
	 */
	public function setUp() {
		//uninstall templavoila so the tests run without it as they used to
		global $TYPO3_LOADED_EXT;
		$this->aTvConfig = $TYPO3_LOADED_EXT['templavoila'];
		$TYPO3_LOADED_EXT['templavoila'] = null;
		
	}
	
	/**
	 * set templavoila
	 */
	public function tearDown () {
		//re-install templavoila so the tests run without it as they used to
		global $TYPO3_LOADED_EXT;
		$TYPO3_LOADED_EXT['templavoila'] = $this->aTvConfig;
	}
	
	private static function getDefaultOptions(){
		$options = array();
		$options['CType.']['_default_.']['indexedFields.'] = array(
			'bodytext', 'imagecaption' , 'altText', 'titleText'
		);
		return $options;
	}
	
	function test_prepareSearchData_CheckIgnoreContentType() {
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_TtContent');
		list($extKey, $cType) = $indexer->getContentType();
		//content type correct?
		$this->assertEquals('core',$extKey,'wrong ext key');
		$this->assertEquals('tt_content',$cType,'wrong cType');

		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$record = array('uid'=> 123, 'pid' => 1, 'deleted' => 0, 'hidden' => 0, 'sectionIndex' => 1, 'CType'=>'list', 'header' => 'test');
		$options = self::getDefaultOptions();
		$options['ignoreCTypes.'] = array('search','mailform','login');
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
		$this->assertNotNull($result, 'Null returned for uid '.$record['uid']);

		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options = self::getDefaultOptions();
		$options['ignoreCTypes.'] = array('search','mailform','list');
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
		$this->assertNull($result, 'Not Null returned for uid '.$record['uid']);

		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options = self::getDefaultOptions();
		$options['ignoreCTypes'] = 'search,mailform,login';
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
		$this->assertNotNull($result, 'Null returned for uid '.$record['uid']);

		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options = self::getDefaultOptions();
		$options['ignoreCTypes'] = 'search,mailform,list';
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
		$this->assertNull($result, 'Not Null returned for uid '.$record['uid']);
	}
	function test_prepareSearchData_CheckIncludeContentType() {
		$indexer = tx_rnbase::makeInstance('tx_mksearch_indexer_TtContent');
		list($extKey, $cType) = $indexer->getContentType();

		$record = array('uid'=> 123, 'pid' => 1, 'deleted' => 0, 'hidden' => 0, 'sectionIndex' => 1, 'CType'=>'list', 'header' => 'test');
		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options = self::getDefaultOptions();
		$options['includeCTypes.'] = array('search','mailform','login');
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
		$this->assertNull($result, 'Not Null returned for uid '.$record['uid'].' when CType not in includeCTypes');

		$indexDoc = tx_rnbase::makeInstance('tx_mksearch_model_IndexerDocumentBase',$extKey, $cType);
		$options = self::getDefaultOptions();
		$options['includeCTypes.'] = array('search','mailform','list');
		$result = $indexer->prepareSearchData('tt_content', $record, $indexDoc, $options);
		$this->assertNotNull($result, 'Null returned for uid '.$record['uid'].' when CType in includeCTypes');

	}
}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContent_testcase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/indexer/class.tx_mksearch_tests_indexer_TtContent_testcase.php']);
}

?>