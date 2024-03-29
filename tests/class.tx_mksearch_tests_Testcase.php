<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2014 DMK E-BUSINESS GmbH
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
 * Base Testcase.
 *
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
abstract class tx_mksearch_tests_Testcase extends \Sys25\RnBase\Testing\BaseTestCase
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
            list($extKey, $contentType) = $extKey->getContentType();
        }

        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'tx_mksearch_model_IndexerDocumentBase',
            $extKey,
            $contentType
        );
    }

    /**
     * @param array                         $record
     * @param tx_mksearch_interface_Indexer $indexer
     * @param string                        $tableName
     * @param array                         $options
     *
     * @return tx_mksearch_model_IndexerDocumentBase|PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPreparedIndexDocMockByRecord(
        array $record,
        tx_mksearch_interface_Indexer $indexer,
        $tableName,
        $options = null
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
        $fields
    ) {
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
     * @param string                                $fieldName
     * @param string                                $expectedValue
     */
    public static function assertIndexDocHasField(
        $indexDoc,
        $fieldName,
        $expectedValue
    ) {
        $message = __METHOD__.'("Line '.__LINE__.'"): ';
        self::assertInstanceOf(
            'tx_mksearch_interface_IndexerDocument',
            $indexDoc,
            $message.'$indexDoc has to be an instance of "tx_mksearch_interface_IndexerDocument" but "'
                .(is_object($indexDoc) ? get_class($indexDoc) : gettype($indexDoc)).'" given.'
        );
        $indexData = $indexDoc->getData();
        self::assertTrue(
            is_array($indexData),
            $message.'The data of $indexDoc has to be an array but "'
                .(is_object($indexData) ? get_class($indexData) : gettype($indexData)).'" given.'
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
                .(is_object($field) ? get_class($field) : gettype($field)).'" given.'
        );
        self::assertSame(
            $expectedValue,
            $field->getValue(),
            $message.'"'.$fieldName.'" contains the wrong value. "'.print_r($expectedValue, true).'" as expected but "'.print_r($field->getValue(), true).'" given.'
        );
    }
}
