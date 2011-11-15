<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 das Medienkombinat GmbH
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
tx_rnbase::load('tx_mksearch_model_SolrHit');

require_once(t3lib_extMgm::extPath('mksearch') . 'lib/Apache/Solr/Document.php');

class tx_mksearch_tests_model_SolrHit_testcase extends tx_phpunit_testcase {

	function test_getSolrId() {
		$doc = new Apache_Solr_Document();
		$doc->id = 'myid';
		$hit = tx_rnbase::makeInstance('tx_mksearch_model_SolrHit', $doc);

//		$sDate = gmstrftime("%d.%m.%Y", $tstamp1);
//		t3lib_div::debug($sDate, 'tx_rnbase_tests_dates_testcase :: test_dateConv'); // TODO: remove me
		$this->assertEquals('myid', $hit->getSolrId());
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/class.tx_mksearch_tests_model_SolrHit_testcase.php']) {
  include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/class.tx_mksearch_tests_model_SolrHit_testcase.php']);
}

?>