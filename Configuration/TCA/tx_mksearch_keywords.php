<?php

return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_keywords',
        'label' => 'keyword',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY keyword',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
        'iconfile' => 'EXT:mksearch/icons/tx_mksearch_keywords.gif',
    ),
    'feInterface' => array(
        'fe_admin_fieldList' => 'hidden, keyword, link',
    ),
    'interface' => array(
        'showRecordFieldList' => 'hidden,searchword,link'
    ),
    'columns' => array(
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'keyword' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_keywords_keyword',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'required',
            )
        ),
        'link' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_keywords_link',
            'config' => array(
                'type' => 'input',
                'size' => '15',
                'max' => '255',
                'checkbox' => '',
                'renderType' => 'inputLink',
                'eval' => 'trim',
                'wizards' => Tx_Rnbase_Utility_TcaTool::getWizards(
                    '',
                    array('link' => true)
                )
            )
        ),
    ),
    'types' => array(
        '0' => array('showitem' => 'hidden, keyword, link')
    ),
    'palettes' => array(
        '1' => array('showitem' => '')
    ),
);
