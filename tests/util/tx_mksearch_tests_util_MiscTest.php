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
class tx_mksearch_tests_util_MiscTest extends tx_mksearch_tests_Testcase
{
    #[PHPUnit\Framework\Attributes\DataProvider('providerHtml2Plain')]
    public function testHtml2Plain($before, array $options, $after): void
    {
        $res = tx_mksearch_util_Misc::html2plain($before, $options);
        self::assertEquals($res, $after);
    }

    public static function providerHtml2Plain(): array
    {
        $return = [];
        foreach ([
            // array($before, $after),
            1 => [
                'Kapstadt Messetrailer <object width="5" height="5"><param name="movie" value="hier muss eigentlich die url rein ^^" /><param name="allowFullScreen" value="true" /><param name="allowscriptaccess" value="always" /><embed src="hier gehts zum player" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="5" height="5"></embed></object> Eternit Schönes Beschützen',
                [], // emty option
                'Kapstadt Messetrailer   Eternit Schönes Beschützen',
            ],
            2 => [
                'Hallo Welt',
                [], // emty option
                'Hallo Welt',
            ],
            3 => [
                'Hallo <!-- Kommentar --> Welt',
                [], // emty option
                'Hallo   Welt',
            ],
            4 => [
                '<br>Hallo</br> <i>Welt</i>',
                [], // emty option
                'Hallo   Welt',
            ],
            5 => [
                'Umlaute encoded F&ouml;rderm&ouml;glichkeiten',
                [], // emty option
                'Umlaute encoded Fördermöglichkeiten',
            ],
            6 => [
                'Zeile1'.PHP_EOL.'Zeile2',
                [], // emty option
                'Zeile1 Zeile2',
            ],
            7 => [
                'Zeile1'.PHP_EOL.'Zeile2',
                ['lineendings' => true],
                'Zeile1'.PHP_EOL.'Zeile2',
            ],
            8 => [
                'one two  three   one five     zero',
                ['removedoublespaces' => true],
                'one two three one five zero',
            ],
            9 => [
                '<a class="collapsed"
                aria-controls="collapse3379"
                aria-expanded="false"
                data-parent="#accordion3383"
                data-toggle="collapse"
                href="#collapse3379"
                role="button"
            >
                HTML tags with line breaks
            </a>',
                [],
                'HTML tags with line breaks',
            ],
        ] as $key => $row) {
            $key = 'Line:'.$key;
            $return[$key] = $row;
        }

        return $return;
    }

    public function testIsIndexableNoConfig(): void
    {
        $record = ['uid' => 123, 'pid' => '1'];
        $options = [];
        self::assertEquals(true, tx_mksearch_util_Misc::isOnValidPage($record, $options));
    }

    public function testIsIndexableIncludeWrongSinglePid(): void
    {
        $record = ['uid' => 123, 'pid' => '1'];
        $options = [];
        $options['include.']['pages.']['0'] = 2;
        self::assertEquals(false, tx_mksearch_util_Misc::isOnValidPage($record, $options));

        $options = [];
        $options['include.']['pages'] = '2,10,145';
        self::assertEquals(false, tx_mksearch_util_Misc::isOnValidPage($record, $options));
    }

    public function testIsIndexableIncludeRightSinglePid(): void
    {
        $record = ['uid' => 123, 'pid' => '1'];
        $options = [];
        $options['include.']['pages.'] = [2, 10, 1, 1145];
        self::assertEquals(true, tx_mksearch_util_Misc::isOnValidPage($record, $options));

        $options = [];
        $options['include.']['pages'] = '2,10,1,145';
        self::assertEquals(true, tx_mksearch_util_Misc::isOnValidPage($record, $options));
    }

    public function testIsIndexableExcludeWrongSinglePid(): void
    {
        $record = ['uid' => 123, 'pid' => '1'];
        $options = [];
        $options['exclude.']['pages.']['0'] = 2;
        self::assertEquals(true, tx_mksearch_util_Misc::isOnValidPage($record, $options));

        $options = [];
        $options['exclude.']['pages'] = '2,10,145';
        self::assertEquals(true, tx_mksearch_util_Misc::isOnValidPage($record, $options));
    }

    public function testIsIndexableExcludeRightSinglePid(): void
    {
        $record = ['uid' => 123, 'pid' => '11'];
        $options = [];
        $options['exclude.']['pages.'] = [2, 10, 11, 1145];
        self::assertEquals(false, tx_mksearch_util_Misc::isOnValidPage($record, $options));

        $options = [];
        $options['exclude.']['pages'] = '2,10,11,145';
        self::assertEquals(false, tx_mksearch_util_Misc::isOnValidPage($record, $options));
    }

    public function testIsIndexableCheckMismachingConfig(): void
    {
        $record = ['uid' => 123, 'pid' => '11'];

        $options = [];
        $options['include.']['pages'] = '';
        $options['exclude.']['pages'] = '2,10,145';
        self::assertEquals(true, tx_mksearch_util_Misc::isOnValidPage($record, $options));
    }

    #[PHPUnit\Framework\Attributes\DataProvider('termProvider')]
    public function testSanitizeTerm(string $sTerm, string $sExpected): void
    {
        self::assertEquals($sExpected, tx_mksearch_util_Misc::sanitizeTerm($sTerm), 'Der Term wurde nicht korrekt bereinigt!');
    }

    public static function termProvider(): array
    {
        return [
            ['test', 'test'],
            ['\'test\'+-&|!(){}\[]^"~+*?<>:', 'test'],
        ];
    }

    #[PHPUnit\Framework\Attributes\DataProvider('sanitizeFqProvider')]
    public function testSanitizeFq(string $sTerm, string $sExpected): void
    {
        self::assertEquals($sExpected, tx_mksearch_util_Misc::sanitizeFq($sTerm));
    }

    public static function sanitizeFqProvider(): array
    {
        return [
            1 => ['test', 'test'],
            2 => ['core.pages', 'core.pages'],
        ];
    }
}
