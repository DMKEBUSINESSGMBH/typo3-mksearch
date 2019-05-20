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

tx_rnbase::load('tx_mksearch_tests_Util');
tx_rnbase::load('tx_rnbase_tests_BaseTestCase');

/**
 * Base Testcase.
 *
 * @author Michael Wagner <michael.wagner@dmk-ebusiness.de>
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
abstract class tx_mksearch_tests_Testcase extends tx_rnbase_tests_BaseTestCase
{
    protected $backups = array();

    /**
     * setUp() = init DB etc.
     */
    protected function setUp()
    {
        tx_mksearch_tests_Util::emptyAddRootlineFields();

        // set up hooks
        tx_mksearch_tests_Util::hooksSetUp();

        // das devlog stört nur bei der Testausführung im BE und ist da auch
        // vollkommen unnötig
        tx_mksearch_tests_Util::disableDevlog();

        // set up tv
        if (tx_rnbase_util_Extensions::isLoaded('templavoila')) {
            $this->backups['templaVoilaConfigBackup'] = $GLOBALS['TYPO3_LOADED_EXT']['templavoila'];
            $GLOBALS['TYPO3_LOADED_EXT']['templavoila'] = null;

            tx_mksearch_tests_Util::unloadExtensionForTypo362OrHigher('templavoila');
        }

        // das TS Parsing frisst in manchen Umgebung mehr als 128MB Speicher. Es gibt aber im Moment keine Zeit
        // die Tests zu refactoren. Also setzen wir das Limit hoch
        ini_set('memory_limit', '1024M');

        // set internal encoding for multibyte strings to utf-8
        $this->backups['mb_internal_encoding'] = mb_internal_encoding();
        mb_internal_encoding('UTF-8');
    }

    /**
     * tearDown() = destroy DB etc.
     */
    protected function tearDown()
    {
        // tear down hooks
        tx_mksearch_tests_Util::hooksTearDown();

        // tear down tv
        if (null !== $this->backups['templaVoilaConfigBackup']) {
            $GLOBALS['TYPO3_LOADED_EXT']['templavoila'] = $this->backups['templaVoilaConfigBackup'];
            $this->backups['templaVoilaConfigBackup'] = null;

            if (tx_rnbase_util_TYPO3::isTYPO62OrHigher()) {
                $extensionManagementUtility = new TYPO3\CMS\Core\Utility\ExtensionManagementUtility();
                $extensionManagementUtility->loadExtension('templavoila');
            }
        }

        tx_mksearch_tests_Util::resetAddRootlineFields();

        // reset internal encoding for multibyte strings
        if (!empty($this->backups['mb_internal_encoding'])) {
            mb_internal_encoding($this->backups['mb_internal_encoding']);
        }
    }

    /**
     * Prepare classes for FE-rendering if it is needed in TYPO3 backend.
     *
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController or tslib_fe
     */
    protected function prepareTSFE(array $options = array('force' => true, 'pid' => 1))
    {
        tx_rnbase_util_Misc::prepareTSFE($options);

        if (!empty($options['pid'])) {
            $GLOBALS['TSFE']->id = $options['pid'];
            if (!is_array($GLOBALS['TSFE']->rootLine)) {
                $GLOBALS['TSFE']->rootLine = array();
            }
            if (!is_array($GLOBALS['TSFE']->rootLine[0])) {
                $GLOBALS['TSFE']->rootLine[0] = array();
            }
            //wenn tq_seo kommt sonst ein error
            $GLOBALS['TSFE']->rootLine[0]['uid'] = $options['pid'];
        }
    }

    /**
     * @param string|array $extKey
     * @param string       $contentType
     *
     * @return tx_mksearch_model_IndexerDocumentBase|PHPUnit_Framework_MockObject_MockObject
     */
    protected function getIndexDocMock($extKey, $contentType = null)
    {
        tx_rnbase::load('tx_mksearch_model_IndexerDocumentBase');
        if ($extKey instanceof tx_mksearch_interface_Indexer) {
            list($extKey, $contentType) = $extKey->getContentType();
        }

        return tx_rnbase::makeInstance(
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
            $message.'"'.$fieldName.'" contains the wrong value. "'.$expectedValue.'" as expected but "'.$field->getValue().'" given.'
        );
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/class.tx_mksearch_tests_Testcase.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/tests/class.tx_mksearch_tests_Testcase.php'];
}
