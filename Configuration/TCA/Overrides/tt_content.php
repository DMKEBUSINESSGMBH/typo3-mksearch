<?php

if (!defined('TYPO3_MODE')) {
    exit('Access denied.');
}

// Extend table tt_news
tx_rnbase_util_Extensions::addTCAcolumns(
    'tt_content',
    [
        'tx_mksearch_is_indexable' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tt_content.tx_mksearch_is_indexable',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:mksearch/locallang_db.xml:tt_content.tx_mksearch_is_indexable.use_indexer_config',
                        tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION,
                    ],
                    [
                        'LLL:EXT:mksearch/locallang_db.xml:tt_content.tx_mksearch_is_indexable.yes',
                        tx_mksearch_indexer_ttcontent_Normal::IS_INDEXABLE,
                    ],
                    [
                        'LLL:EXT:mksearch/locallang_db.xml:tt_content.tx_mksearch_is_indexable.no',
                        tx_mksearch_indexer_ttcontent_Normal::IS_NOT_INDEXABLE,
                    ],
                ],
                'default' => tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION,
            ],
        ],
    ],
    false
);

tx_rnbase_util_Extensions::addToAllTCAtypes('tt_content', 'tx_mksearch_is_indexable');
