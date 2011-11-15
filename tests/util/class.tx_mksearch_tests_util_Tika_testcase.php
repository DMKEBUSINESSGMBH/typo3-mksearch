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
tx_rnbase::load('tx_mksearch_util_Tika');


class tx_mksearch_tests_util_Tika_testcase extends tx_phpunit_testcase {

	public function test_getContent() {
		if(!tx_mksearch_util_Tika::getInstance()->isAvailable())
			$this->markTestSkipped('Tika is not available!');
		
		$content = tx_mksearch_util_Tika::getInstance()->extractContent('EXT:mksearch/doc/wizard_form.html');
		$this->assertTrue(strlen($content) > 100);
	}

	public function test_getLanguage() {
		if(!tx_mksearch_util_Tika::getInstance()->isAvailable())
			$this->markTestSkipped('Tika is not available!');
		
		$lang = tx_mksearch_util_Tika::getInstance()->extractLanguage('EXT:mksearch/doc/wizard_form.html');
		$this->assertEquals('en', $lang);
	}

	public function test_getMeta() {
		if(!tx_mksearch_util_Tika::getInstance()->isAvailable())
			$this->markTestSkipped('Tika is not available!');

		$meta = tx_mksearch_util_Tika::getInstance()->extractMetaData('EXT:mksearch/doc/wizard_form.html');
		$this->assertTrue(is_array($meta));
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/util/class.tx_mksearch_tests_util_Misc_testcase.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/util/class.tx_mksearch_tests_util_Misc_testcase.php']);
}

?>