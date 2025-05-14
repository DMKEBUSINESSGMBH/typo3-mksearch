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
class tx_mksearch_tests_util_KeyValueFacetTest extends tx_mksearch_tests_Testcase
{
    /**
     * @param string $delimiter
     *
     * @return tx_mksearch_util_KeyValueFacet
     */
    protected function getKeyValueFacetInstance($delimiter = null)
    {
        return tx_mksearch_util_KeyValueFacet::getInstance($delimiter);
    }

    #[PHPUnit\Framework\Attributes\DataProvider('providerBuildFacetValue')]
    public function testBuildFacetValue(string $key, string $value, ?string $sorting, string $expected): void
    {
        $builder = $this->getKeyValueFacetInstance();
        $actual = $builder->buildFacetValue($key, $value, $sorting);
        self::assertEquals($expected, $actual);
    }

    public static function providerBuildFacetValue(): array
    {
        return [
            1 => [
                'key' => 'key', 'value' => 'value', 'sorting' => null,
                'expected' => 'key<[DFS]>value',
            ],
            2 => [
                'key' => '50', 'value' => 'Test', 'sorting' => '2',
                'expected' => '50<[DFS]>Test<[DFS]>2',
            ],
        ];
    }

    #[PHPUnit\Framework\Attributes\DataProvider('providerCheckValue')]
    public function testCheckValue(string $value, bool $expected): void
    {
        $builder = $this->getKeyValueFacetInstance();
        $actual = $builder->checkValue($value);
        self::assertEquals($expected, $actual);
    }

    public static function providerCheckValue(): array
    {
        return [
            1 => [
                'value' => 'key<[DFS]>value',
                'expected' => true,
            ],
            2 => [
                'value' => 'Test',
                'expected' => false,
            ],
        ];
    }

    #[PHPUnit\Framework\Attributes\DataProvider('providerExplodeFacetValue')]
    public function testExplodeFacetValue(string $builded, array $expected): void
    {
        $builder = $this->getKeyValueFacetInstance();
        $actual = $builder->explodeFacetValue($builded);
        self::assertEquals($expected, $actual);
    }

    public static function providerExplodeFacetValue(): array
    {
        return [
            1 => [
                'builded' => 'key<[DFS]>value',
                'expected' => ['key' => 'key', 'value' => 'value', 'sorting' => null],
            ],
            2 => [
                'builded' => '50<[DFS]>Test<[DFS]>2',
                'expected' => ['key' => '50', 'value' => 'Test', 'sorting' => '2'],
            ],
        ];
    }

    #[PHPUnit\Framework\Attributes\DataProvider('providerExtractFacetValue')]
    public function testExtractFacetValue(string $builded, string $expected): void
    {
        $builder = $this->getKeyValueFacetInstance();
        $actual = $builder->extractFacetValue($builded);
        self::assertEquals($expected, $actual);
    }

    public static function providerExtractFacetValue(): array
    {
        return [
            1 => [
                'builded' => 'key<[DFS]>value',
                'expected' => 'value',
            ],
            2 => [
                'builded' => '50<[DFS]>Test',
                'expected' => 'Test',
            ],
        ];
    }

    public function testExplodeFacetValues(): void
    {
        $builder = $this->getKeyValueFacetInstance();
        $data = [
            'key<[DFS]>value',
            '50<[DFS]>Test<[DFS]>2',
        ];
        $actual = $builder->explodeFacetValues($data);
        $expected = [
            ['key' => 'key', 'value' => 'value', 'sorting' => null],
            ['key' => '50', 'value' => 'Test', 'sorting' => '2'],
        ];
        self::assertEquals($expected, $actual);
    }

    public function testExtractFacetValues(): void
    {
        $builder = $this->getKeyValueFacetInstance();
        $data = [
            'key<[DFS]>value',
            '50<[DFS]>Test',
        ];
        $actual = $builder->extractFacetValues($data);
        $expected = [
            'key' => 'value',
            '50' => 'Test',
        ];
        self::assertEquals($expected, $actual);
    }
}
