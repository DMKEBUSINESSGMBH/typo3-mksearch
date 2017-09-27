<?php

return array(
    'ctrl' => array(
        'title'     => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices',
        'label'     => 'title',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY title',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
        'iconfile'          => 'EXT:mksearch/icons/icon_tx_mksearch_indices.gif',
        'requestUpdate'        => 'engine'
    ),
    'interface' => array(
        'showRecordFieldList' => 'hidden,title,description,name,composites,configuration'
    ),
    'columns' => array(
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices.hidden',
            'config'  => array(
                'type'    => 'check',
                'default' => '0'
            )
        ),
        'title' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices.title',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'max' => '100',
                'eval' => 'required,trim',
            )
        ),
        'description' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices.description',
            'config' => array(
                'type' => 'text',
                'cols' => '30',
                'rows' => '3',
            ),
        ),
        'name' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices.name',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'max' => '100',
                'eval' => 'required,trim',
            )
        ),
        'composites' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices.composites',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_mksearch_configcomposites',
                'foreign_table_where' => ' AND tx_mksearch_configcomposites.pid=###CURRENT_PID### ORDER BY tx_mksearch_configcomposites.title',
                'MM' => 'tx_mksearch_indices_configcomposites_mm',
                'size' => 20,
                'minitems' => 0,
                'maxitems' => 100,
                'wizards' => Tx_Rnbase_Utility_TcaTool::getWizards(
                    'tx_mksearch_configcomposites',
                    array('add' => true, 'edit' => true, 'list' => true)
                ),
            ),
        ),
        'engine' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices_engine',
            'config' => array(
                'type' => 'radio',
                'items' => array(
                    array('LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices_engine_zendlucene','zend_lucene'),
                    array('LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices_engine_solr','solr'),
                    array('LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices_engine_elasticsearch','elasticsearch'),
                ),
                'eval' => 'required',
                'default' => 'zend_lucene',
            ),
            'onChange' => 'reload'
        ),
        'solrversion' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices_solrversion',
            'config' => array(
                'type' => 'radio',
                'items' => array(
                    array('LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices_solrversion_35',35),
                    array('LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices_solrversion_40',40),
                ),
                'default' => 35
            ),
            'displayCond' => 'FIELD:engine:=:solr'
        ),
        'configuration' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs.configuration',
            'config' => array(
                'type' => 'text',
                'cols' => '200',
                'rows' => '50',
                'default' => '# Insert the default config for this index.'.PHP_EOL.PHP_EOL.'default {'.PHP_EOL.'	# insert default configuration for indexers here.'.PHP_EOL.'	# core.tt_content.lang = 1'.PHP_EOL.'}'
            )
        )
    ),
    'types' => array(
        '0' => array('showitem' => 'hidden;;1, title, description, engine, solrversion, configuration, name, composites')
    ),
    'palettes' => array(
        '1' => array('showitem' => '')
    )
);
