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
class tx_mksearch_tests_util_TikaTest extends tx_mksearch_tests_Testcase
{
    protected function setUp()
    {
        self::markTestIncomplete('Uncaught require(typo3-mksearch/.Build/Web/typo3conf/LocalConfiguration.php)');

        if (!tx_mksearch_util_Tika::getInstance()->isAvailable()) {
            $this->markTestSkipped('Tika is not available!');
        }
        parent::setUp();
    }

    /**
     * @group integration
     */
    public function test_getContent()
    {
        $content = tx_mksearch_util_Tika::getInstance()->extractContent(
            'EXT:mksearch/tests/fixtures/html/wizard_form.html'
        );
        self::assertTrue(strlen($content) > 100);
    }

    /**
     * @group integration
     */
    public function test_getContentFromFileWithUmlautsInName()
    {
        $content = tx_mksearch_util_Tika::getInstance()->extractContent(
            'EXT:mksearch/tests/fixtures/txt/ä.txt'
        );
        self::assertEquals(
            'test text',
            trim($content),
            'content falsch extrahiert'
        );
    }

    /**
     * @group integration
     */
    public function test_getLanguage()
    {
        $lang = tx_mksearch_util_Tika::getInstance()->extractLanguage(
            'EXT:mksearch/tests/fixtures/html/wizard_form.html'
        );
        self::assertEquals('en', $lang);
    }

    /**
     * @group integration
     */
    public function test_getMeta()
    {
        $meta = tx_mksearch_util_Tika::getInstance()->extractMetaData(
            'EXT:mksearch/tests/fixtures/html/wizard_form.html'
        );
        self::assertTrue(is_array($meta));
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/util/class.tx_mksearch_tests_util_MiscTest.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/util/class.tx_mksearch_tests_util_MiscTest.php'];
}
