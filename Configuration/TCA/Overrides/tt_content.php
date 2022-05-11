<?php

if (!defined('TYPO3')) {
    exit('Access denied.');
}

// Extend table tt_news
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
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
                        'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tt_content.tx_mksearch_is_indexable.use_indexer_config',
                        tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION,
                    ],
                    [
                        'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tt_content.tx_mksearch_is_indexable.yes',
                        tx_mksearch_indexer_ttcontent_Normal::IS_INDEXABLE,
                    ],
                    [
                        'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tt_content.tx_mksearch_is_indexable.no',
                        tx_mksearch_indexer_ttcontent_Normal::IS_NOT_INDEXABLE,
                    ],
                ],
                'default' => tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION,
            ],
        ],
    ],
    false
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tt_content', 'tx_mksearch_is_indexable');

// Show tt_content-field pi_flexform
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['tx_mksearch'] = 'pi_flexform';
// Hide some fields
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['tx_mksearch'] = 'select_key';

// add our own header_layout. this one isn't displayed in the FE (like the Hidden type)
// as long as there is no additional configuration how to display this type.
// so this type is just like the 100 type in the FE but this type is indexed instead
// of the standard type (100)
$aTempConfig = $GLOBALS['TCA']['tt_content']['columns']['header_layout']['config']['items'];
$aTempConfig[] = ['LLL:EXT:'.'mksearch'.'/Resources/Private/Language/locallang_db.xlf:plugin.mksearch.tt_content.header_layout', '101'];
$GLOBALS['TCA']['tt_content']['columns']['header_layout']['config']['items'] = $aTempConfig;

// Add flexform and plugin
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('tx_mksearch', 'FILE:EXT:'.'mksearch'.'/flexform_main.xml');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    ['LLL:EXT:'.'mksearch'.'/Resources/Private/Language/locallang_db.xlf:plugin.mksearch.label', 'tx_mksearch'],
    'list_type',
    'mksearch'
);
