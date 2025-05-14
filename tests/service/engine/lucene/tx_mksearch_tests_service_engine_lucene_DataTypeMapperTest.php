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
class tx_mksearch_tests_service_engine_lucene_DataTypeMapperTest extends tx_mksearch_tests_Testcase
{
    /* @var $mapper tx_mksearch_service_engine_lucene_DataTypeMapper */
    private object $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_lucene_DataTypeMapper');
    }

    public function testFieldWithSpecialConfig(): void
    {
        // Zuerst den Default testen
        self::assertEquals('keyword', $this->mapper->getDataType('tstamp'), 'Wrong data type found');

        // Und jetzt per Config überschreiben
        $cfg = [];
        $cfg['fields.']['tstamp.']['type'] = 'unindexed';
        $mapper = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_service_engine_lucene_DataTypeMapper', $cfg);
        self::assertEquals('unindexed', $mapper->getDataType('tstamp'), 'Wrong data type found');
        // Die anderen sollten weiter normal funktionieren
        self::assertEquals('keyword', $mapper->getDataType('uid'), 'Wrong data type found');
    }

    #[PHPUnit\Framework\Attributes\DataProvider('getSolrLikeFieldNames')]
    public function testAutoTypesFromSolr(string $fieldName, string $expectedType): void
    {
        // 'text', 'keyword', 'unindexed', 'unstored', 'binary'
        self::assertEquals($expectedType, $this->mapper->getDataType($fieldName), 'Wrong data type found for fieldname '.$fieldName);
    }

    public static function getSolrLikeFieldNames(): array
    {
        return [
            ['test_s', 'text'],
            ['test_i', 'keyword'],
            ['pid', 'keyword'],
            ['someotherfield', 'text'],
            ['someotherfield_mi', 'text'],
        ];
    }
}
