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

if (!defined('TYPO3')) {
    exit('Access denied.');
}

// Extend table tt_news
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'tt_content',
    [
        'tx_mksearch_is_indexable' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tt_content.tx_mksearch_is_indexable',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tt_content.tx_mksearch_is_indexable.use_indexer_config',
                        'value' => tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION,
                    ],
                    [
                        'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tt_content.tx_mksearch_is_indexable.yes',
                        'value' => tx_mksearch_indexer_ttcontent_Normal::IS_INDEXABLE,
                    ],
                    [
                        'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tt_content.tx_mksearch_is_indexable.no',
                        'value' => tx_mksearch_indexer_ttcontent_Normal::IS_NOT_INDEXABLE,
                    ],
                ],
                'default' => tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION,
            ],
        ],
    ]
);

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'tx_mksearch_is_indexable');

// add our own header_layout. this one isn't displayed in the FE (like the Hidden type)
// as long as there is no additional configuration how to display this type.
// so this type is just like the 100 type in the FE but this type is indexed instead
// of the standard type (100)
$aTempConfig = $GLOBALS['TCA']['tt_content']['columns']['header_layout']['config']['items'];
$aTempConfig[] = ['LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:plugin.mksearch.tt_content.header_layout', '101'];
$GLOBALS['TCA']['tt_content']['columns']['header_layout']['config']['items'] = $aTempConfig;

// Add flexform and plugin
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:mksearch/Configuration/Flexform/Main.xml',
    'tx_mksearch'
);
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    ['LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:plugin.mksearch.label', 'tx_mksearch'],
    TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT,
    'mksearch'
);
// Show tt_content-field pi_flexform
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    '--div--;Configuration,pi_flexform,',
    'tx_mksearch',
    'after:subheader'
);
