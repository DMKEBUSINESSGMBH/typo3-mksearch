<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2014 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
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

    /**
     * @dataProvider providerBuildFacetValue
     *
     * @param string $key
     * @param string $value
     * @param string $expected
     */
    public function testBuildFacetValue($key, $value, $sorting, $expected)
    {
        $builder = $this->getKeyValueFacetInstance();
        $actual = $builder->buildFacetValue($key, $value, $sorting);
        self::assertEquals($expected, $actual);
    }

    /**
     * Dataprovider for testBuildFacetValue.
     */
    public function providerBuildFacetValue()
    {
        return [
            __LINE__ => [
                'key' => 'key', 'value' => 'value', 'sorting' => null,
                'expected' => 'key<[DFS]>value',
            ],
            __LINE__ => [
                'key' => '50', 'value' => 'Test', 'sorting' => '2',
                'expected' => '50<[DFS]>Test<[DFS]>2',
            ],
        ];
    }

    /**
     * @dataProvider providerCheckValue
     *
     * @param string $value
     * @param string $expected
     */
    public function testCheckValue($value, $expected)
    {
        $builder = $this->getKeyValueFacetInstance();
        $actual = $builder->checkValue($value);
        self::assertEquals($expected, $actual);
    }

    /**
     * Dataprovider for testCheckValue.
     */
    public function providerCheckValue()
    {
        return [
            __LINE__ => [
                'value' => 'key<[DFS]>value',
                'expected' => true,
            ],
            __LINE__ => [
                'value' => 'Test',
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider providerExplodeFacetValue
     *
     * @param string $builded
     * @param array  $expected
     */
    public function testExplodeFacetValue($builded, $expected)
    {
        $builder = $this->getKeyValueFacetInstance();
        $actual = $builder->explodeFacetValue($builded);
        self::assertEquals($expected, $actual);
    }

    /**
     * Dataprovider for testExplodeFacetValue.
     */
    public function providerExplodeFacetValue()
    {
        return [
            __LINE__ => [
                'builded' => 'key<[DFS]>value',
                'expected' => ['key' => 'key', 'value' => 'value', 'sorting' => null],
            ],
            __LINE__ => [
                'builded' => '50<[DFS]>Test<[DFS]>2',
                'expected' => ['key' => '50', 'value' => 'Test', 'sorting' => '2'],
            ],
        ];
    }

    /**
     * @dataProvider providerExtractFacetValue
     *
     * @param string $builded
     * @param array  $expected
     */
    public function testExtractFacetValue($builded, $expected)
    {
        $builder = $this->getKeyValueFacetInstance();
        $actual = $builder->extractFacetValue($builded);
        self::assertEquals($expected, $actual);
    }

    /**
     * Dataprovider for testExtractFacetValue.
     */
    public function providerExtractFacetValue()
    {
        return [
            __LINE__ => [
                'builded' => 'key<[DFS]>value',
                'expected' => 'value',
            ],
            __LINE__ => [
                'builded' => '50<[DFS]>Test',
                'expected' => 'Test',
            ],
        ];
    }

    public function testExplodeFacetValues()
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

    public function testExtractFacetValues()
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

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/util/class.tx_mksearch_tests_util_KeyValueFacetTest.php']) {
    include_once $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/mksearch/tests/util/class.tx_mksearch_tests_util_KeyValueFacetTest.php'];
}
