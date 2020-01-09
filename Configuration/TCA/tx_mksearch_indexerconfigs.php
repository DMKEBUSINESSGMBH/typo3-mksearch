<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY title',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'requestUpdate' => 'extkey,contenttype',
        'iconfile' => 'EXT:mksearch/icons/icon_tx_mksearch_indexconfigs.gif',
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden,title,description,extkey,contenttype,config,composites',
    ],
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs.hidden',
                'config' => [
                'type' => 'check',
                'default' => '0',
            ],
        ],
        'title' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs.title',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'max' => '100',
                'eval' => 'required,trim',
            ],
        ],
        'description' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs.description',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ],
        ],
        'extkey' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs.extkey',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [['', '']],
                'itemsProcFunc' => 'tx_mksearch_util_TCA->getIndexerExtKeys',
                'size' => '1',
                'maxitems' => '1',
            ],
            'onChange' => 'reload',
        ],
        'contenttype' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs.contenttype',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [['', '']],
                'itemsProcFunc' => 'tx_mksearch_util_TCA->getIndexerContentTypes',
                'size' => '1',
                'maxitems' => '1',
                'eval' => 'required,trim',
            ],
            'onChange' => 'reload',
        ],
        'configuration' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs.configuration',
            'config' => [
                'type' => 'text',
                'cols' => '200',
                'rows' => '50',
                // @see \DMK\Mksearch\Backend\Form\Element\IndexerConfigurationField
                'renderType' => 'indexerConfigurationField',
            ],
        ],
        'composites' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs.composites',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_mksearch_configcomposites',
                'foreign_table_where' => ' AND tx_mksearch_configcomposites.pid=###CURRENT_PID### ORDER BY tx_mksearch_configcomposites.title',
                'MM' => 'tx_mksearch_configcomposites_indexerconfigs_mm',
                'MM_opposite_field' => 'configs',
                'size' => 20,
                'minitems' => 0,
                'maxitems' => 100,
                'fieldControl' => ['editPopup' => true, 'addRecord' => true],
                'wizards' => Tx_Rnbase_Utility_TcaTool::getWizards(
                    'tx_mksearch_configcomposites',
                    ['add' => true, 'edit' => true, 'list' => true]
                ),
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'hidden, title, description, extkey, contenttype, configuration, composites'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
