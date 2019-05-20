<?php

return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_configcomposites',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY title',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
        'iconfile' => 'EXT:mksearch/icons/icon_tx_mksearch_configcomposites.gif',
    ),
    'interface' => array(
        'showRecordFieldList' => 'hidden,title,description,indices,configs,configuration',
    ),
    'columns' => array(
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_configcomposites.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0',
            ),
        ),
        'title' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_configcomposites.title',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'max' => '100',
                'eval' => 'required,trim',
            ),
        ),
        'description' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_configcomposites.description',
            'config' => array(
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ),
        ),
        'indices' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_configcomposites.indices',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_mksearch_indices',
                'foreign_table_where' => ' AND tx_mksearch_indices.pid=###CURRENT_PID### ORDER BY tx_mksearch_indices.title',
                'MM' => 'tx_mksearch_indices_configcomposites_mm',
                'MM_opposite_field' => 'composites',
                'size' => 20,
                'minitems' => 0,
                'maxitems' => 100,
                'fieldControl' => array('editPopup' => true, 'addRecord' => true),
                'wizards' => Tx_Rnbase_Utility_TcaTool::getWizards(
                    'tx_mksearch_indices',
                    array('add' => true, 'edit' => true, 'list' => true)
                ),
            ),
        ),
        'configs' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_configcomposites.configs',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_mksearch_indexerconfigs',
                'foreign_table_where' => ' AND tx_mksearch_indexerconfigs.pid=###CURRENT_PID### ORDER BY tx_mksearch_indexerconfigs.title',
                'MM' => 'tx_mksearch_configcomposites_indexerconfigs_mm',
                'size' => 20,
                'minitems' => 0,
                'maxitems' => 100,
                'fieldControl' => array('editPopup' => true, 'addRecord' => true),
                'wizards' => Tx_Rnbase_Utility_TcaTool::getWizards(
                    'tx_mksearch_indexerconfigs',
                    array('add' => true, 'edit' => true, 'list' => true)
                ),
            ),
        ),
        'configuration' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_configcomposites.configuration',
            'config' => array(
                'type' => 'text',
                'cols' => '200',
                'rows' => '50',
                'default' => '### Insert the default indexer configuration here'.PHP_EOL.
                            '# extkey.contenttype {'.PHP_EOL.
                            '#     default config here.'.PHP_EOL.
                            '# }',
            ),
        ),
    ),
    'types' => array(
        '0' => array('showitem' => 'hidden, title, description, indices, configs, configuration'),
    ),
    'palettes' => array(
        '1' => array('showitem' => ''),
    ),
);
