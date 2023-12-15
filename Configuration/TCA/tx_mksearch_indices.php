<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY title',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:mksearch/Resources/Public/Icons/icon_tx_mksearch_indices.gif',
        'requestUpdate' => 'engine',
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden,title,description,name,composites,configuration',
    ],
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices.hidden',
            'config' => [
                'type' => 'check',
                'default' => '0',
            ],
        ],
        'title' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices.title',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'max' => '100',
                'eval' => 'required,trim',
            ],
        ],
        'description' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices.description',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '3',
            ],
        ],
        'name' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices.name',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'max' => '255',
                'eval' => 'required,trim',
            ],
        ],
        'composites' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices.composites',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_mksearch_configcomposites',
                'foreign_table_where' => ' AND tx_mksearch_configcomposites.pid=###CURRENT_PID### ORDER BY tx_mksearch_configcomposites.title',
                'MM' => 'tx_mksearch_indices_configcomposites_mm',
                'size' => 20,
                'minitems' => 0,
                'maxitems' => 100,
                'fieldControl' => ['editPopup' => true, 'addRecord' => true],
                'wizards' => \Sys25\RnBase\Backend\Utility\TcaTool::getWizards(
                    'tx_mksearch_configcomposites',
                    ['add' => true, 'edit' => true, 'list' => true]
                ),
            ],
        ],
        'engine' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices_engine',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices_engine_zendlucene', 'zend_lucene'],
                    ['LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices_engine_solr', 'solr'],
                    ['LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices_engine_elasticsearch', 'elasticsearch'],
                ],
                'eval' => 'required',
                'default' => 'zend_lucene',
            ],
            'onChange' => 'reload',
        ],
        'solrversion' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices_solrversion',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices_solrversion_35', 35],
                    ['LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices_solrversion_40', 40],
                ],
                'default' => 35,
            ],
            'displayCond' => 'FIELD:engine:=:solr',
        ],
        'configuration' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indexerconfigs.configuration',
            'config' => [
                'type' => 'text',
                'cols' => '200',
                'rows' => '50',
                'default' => '# Insert the default config for this index.'.PHP_EOL.PHP_EOL.'default {'.PHP_EOL.'	# insert default configuration for indexers here.'.PHP_EOL.'	# core.tt_content.lang = 1'.PHP_EOL.'}',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'hidden, title, description, engine, solrversion, configuration, name, composites'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
