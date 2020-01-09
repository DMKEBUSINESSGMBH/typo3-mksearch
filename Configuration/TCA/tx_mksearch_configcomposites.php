<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_configcomposites',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY title',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:mksearch/icons/icon_tx_mksearch_configcomposites.gif',
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden,title,description,indices,configs,configuration',
    ],
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_configcomposites.hidden',
            'config' => [
                'type' => 'check',
                'default' => '0',
            ],
        ],
        'title' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_configcomposites.title',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'max' => '100',
                'eval' => 'required,trim',
            ],
        ],
        'description' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_configcomposites.description',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ],
        ],
        'indices' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_configcomposites.indices',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_mksearch_indices',
                'foreign_table_where' => ' AND tx_mksearch_indices.pid=###CURRENT_PID### ORDER BY tx_mksearch_indices.title',
                'MM' => 'tx_mksearch_indices_configcomposites_mm',
                'MM_opposite_field' => 'composites',
                'size' => 20,
                'minitems' => 0,
                'maxitems' => 100,
                'fieldControl' => ['editPopup' => true, 'addRecord' => true],
                'wizards' => Tx_Rnbase_Utility_TcaTool::getWizards(
                    'tx_mksearch_indices',
                    ['add' => true, 'edit' => true, 'list' => true]
                ),
            ],
        ],
        'configs' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_configcomposites.configs',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_mksearch_indexerconfigs',
                'foreign_table_where' => ' AND tx_mksearch_indexerconfigs.pid=###CURRENT_PID### ORDER BY tx_mksearch_indexerconfigs.title',
                'MM' => 'tx_mksearch_configcomposites_indexerconfigs_mm',
                'size' => 20,
                'minitems' => 0,
                'maxitems' => 100,
                'fieldControl' => ['editPopup' => true, 'addRecord' => true],
                'wizards' => Tx_Rnbase_Utility_TcaTool::getWizards(
                    'tx_mksearch_indexerconfigs',
                    ['add' => true, 'edit' => true, 'list' => true]
                ),
            ],
        ],
        'configuration' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_configcomposites.configuration',
            'config' => [
                'type' => 'text',
                'cols' => '200',
                'rows' => '50',
                'default' => '### Insert the default indexer configuration here'.PHP_EOL.
                            '# extkey.contenttype {'.PHP_EOL.
                            '#     default config here.'.PHP_EOL.
                            '# }',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'hidden, title, description, indices, configs, configuration'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
