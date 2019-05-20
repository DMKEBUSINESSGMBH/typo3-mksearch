<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

// Extend table tt_news
tx_rnbase::load('tx_mksearch_indexer_ttcontent_Normal');
tx_rnbase::load('tx_rnbase_util_TYPO3');
tx_rnbase_util_Extensions::addTCAcolumns(
    'tt_content',
    array(
        'tx_mksearch_is_indexable' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tt_content.tx_mksearch_is_indexable',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array(
                        'LLL:EXT:mksearch/locallang_db.xml:tt_content.tx_mksearch_is_indexable.use_indexer_config',
                        tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION,
                    ),
                    array(
                        'LLL:EXT:mksearch/locallang_db.xml:tt_content.tx_mksearch_is_indexable.yes',
                        tx_mksearch_indexer_ttcontent_Normal::IS_INDEXABLE,
                    ),
                    array(
                        'LLL:EXT:mksearch/locallang_db.xml:tt_content.tx_mksearch_is_indexable.no',
                        tx_mksearch_indexer_ttcontent_Normal::IS_NOT_INDEXABLE,
                    ),
                ),
                'default' => tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION,
            ),
        ),
    ),
    !tx_rnbase_util_TYPO3::isTYPO62OrHigher()
);

tx_rnbase_util_Extensions::addToAllTCAtypes('tt_content', 'tx_mksearch_is_indexable');
