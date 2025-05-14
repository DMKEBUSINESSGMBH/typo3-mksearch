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

abstract class tx_mksearch_tests_Testcase extends Sys25\RnBase\Testing\BaseTestCase
{
    /**
     * @param string|array $extKey
     * @param string       $contentType
     *
     * @return tx_mksearch_model_IndexerDocumentBase|PHPUnit_Framework_MockObject_MockObject
     */
    protected function getIndexDocMock($extKey, $contentType = null)
    {
        if ($extKey instanceof tx_mksearch_interface_Indexer) {
            [$extKey, $contentType] = $extKey->getContentType();
        }

        return TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            $extKey,
            $contentType
        );
    }

    /**
     * @param string $tableName
     * @param array  $options
     *
     * @return tx_mksearch_model_IndexerDocumentBase|PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPreparedIndexDocMockByRecord(
        array $record,
        tx_mksearch_interface_Indexer $indexer,
        $tableName,
        $options = null,
    ) {
        $indexDoc = $this->getIndexDocMock($indexer);

        if (!is_array($options)) {
            $options = tx_mksearch_util_Misc::parseTsConfig(
                '{'.LF.$indexer->getDefaultTSConfig().LF.'}'
            );
        }

        return $indexer->prepareSearchData(
            $tableName,
            $record,
            $indexDoc,
            $options
        );
    }

    /**
     * Checks a index doc, if there was a correct value.
     *
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param array                                 $fields   Containig field value pairs
     */
    public static function assertIndexDocHasFields(
        $indexDoc,
        $fields,
    ): void {
        foreach ($fields as $field => $value) {
            static::assertIndexDocHasField(
                $indexDoc,
                $field,
                $value
            );
        }
    }

    /**
     * Checks a index doc, if there was a correct value.
     *
     * @param tx_mksearch_interface_IndexerDocument $indexDoc
     * @param string                                $expectedValue
     */
    public static function assertIndexDocHasField(
        $indexDoc,
        string $fieldName,
        $expectedValue,
    ): void {
        $message = __METHOD__.'("Line '.__LINE__.'"): ';
        self::assertInstanceOf(
            'tx_mksearch_interface_IndexerDocument',
            $indexDoc,
            $message.'$indexDoc has to be an instance of "tx_mksearch_interface_IndexerDocument" but "'
                .get_debug_type($indexDoc).'" given.'
        );
        $indexData = $indexDoc->getData();
        self::assertTrue(
            is_array($indexData),
            $message.'The data of $indexDoc has to be an array but "'
                .get_debug_type($indexData).'" given.'
        );
        self::assertArrayHasKey(
            $fieldName,
            $indexData,
            __LINE__.': $indexData does not contain the required field "'.$fieldName.'"'
        );
        $field = $indexData[$fieldName];
        self::assertInstanceOf(
            'tx_mksearch_interface_IndexerField',
            $field,
            $message.'"'.$fieldName.'" has to be an instance of "tx_mksearch_interface_IndexerField" but "'
                .get_debug_type($field).'" given.'
        );
        self::assertSame(
            $expectedValue,
            $field->getValue(),
            $message.'"'.$fieldName.'" contains the wrong value. "'.print_r($expectedValue, true).'" as expected but "'.print_r($field->getValue(), true).'" given.'
        );
    }
}
