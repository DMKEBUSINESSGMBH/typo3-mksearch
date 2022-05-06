<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_keywords',
        'label' => 'keyword',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY keyword',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:mksearch/Resources/Public/Icons/tx_mksearch_keywords.gif',
    ],
    'feInterface' => [
        'fe_admin_fieldList' => 'hidden, keyword, link',
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden,searchword,link',
    ],
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => '0',
            ],
        ],
        'keyword' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_keywords_keyword',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'required',
            ],
        ],
        'link' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_keywords_link',
            'config' => [
                'type' => 'input',
                'size' => '15',
                'max' => '255',
                'checkbox' => '',
                'renderType' => 'inputLink',
                'eval' => 'trim',
                'wizards' => \Sys25\RnBase\Backend\Utility\TcaTool::getWizards(
                    '',
                    ['link' => true]
                ),
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'hidden, keyword, link'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
