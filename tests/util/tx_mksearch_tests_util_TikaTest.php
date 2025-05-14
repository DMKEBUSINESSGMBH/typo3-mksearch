<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of the "mksearch" Extension for TYPO3 CMS.
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GNU Lesser General Public License can be found at
 * www.gnu.org/licenses/lgpl.html
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

/**
 * @author Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_tests_util_TikaTest extends tx_mksearch_tests_Testcase
{
    protected function setUp(): void
    {
        $this->markTestSkipped('Tika jar needs to be provided for tests!');
        parent::setUp();
    }

    public function testGetContent(): void
    {
        $content = tx_mksearch_util_Tika::getInstance()->extractContent(
            'EXT:mksearch/tests/fixtures/html/wizard_form.html'
        );
        self::assertTrue(strlen($content) > 100);
    }

    public function testGetContentFromFileWithUmlautsInName(): void
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

    public function testGetLanguage(): void
    {
        $lang = tx_mksearch_util_Tika::getInstance()->extractLanguage(
            'EXT:mksearch/tests/fixtures/html/wizard_form.html'
        );
        self::assertEquals('en', $lang);
    }

    public function testGetMeta(): void
    {
        $meta = tx_mksearch_util_Tika::getInstance()->extractMetaData(
            'EXT:mksearch/tests/fixtures/html/wizard_form.html'
        );
        self::assertTrue(is_array($meta));
    }
}
