<?php

$configurationFieldWizard = tx_rnbase_util_TYPO3::isTYPO76OrHigher() ? array() : array(
    'appendDefaultTSConfig' => array(
        'type' => 'userFunc',
        'notNewRecords' => 1,
        'userFunc' => 'EXT:mksearch/util/class.tx_mksearch_util_TCA.php:tx_mksearch_util_TCA->insertIndexerDefaultTSConfig',
        'params' => array(
            'insertBetween' => array('>', '</textarea'),
            'onMatchOnly' => '/^\s*$/',
        ),
    ),
);

return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY title',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
        'requestUpdate' => 'extkey,contenttype',
        'iconfile' => 'EXT:mksearch/icons/icon_tx_mksearch_indexconfigs.gif',
    ),
    'interface' => array(
        'showRecordFieldList' => 'hidden,title,description,extkey,contenttype,config,composites',
    ),
    'columns' => array(
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs.hidden',
                'config' => array(
                'type' => 'check',
                'default' => '0',
            ),
        ),
        'title' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs.title',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'max' => '100',
                'eval' => 'required,trim',
            ),
        ),
        'description' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs.description',
            'config' => array(
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ),
        ),
        'extkey' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs.extkey',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(array('', '')),
                'itemsProcFunc' => 'EXT:mksearch/util/class.tx_mksearch_util_TCA.php:tx_mksearch_util_TCA->getIndexerExtKeys',
                'size' => '1',
                'maxitems' => '1',
            ),
            'onChange' => 'reload',
        ),
        'contenttype' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs.contenttype',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(array('', '')),
                'itemsProcFunc' => 'EXT:mksearch/util/class.tx_mksearch_util_TCA.php:tx_mksearch_util_TCA->getIndexerContentTypes',
                'size' => '1',
                'maxitems' => '1',
                'eval' => 'required,trim',
            ),
            'onChange' => 'reload',
        ),
        'configuration' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs.configuration',
            'config' => array(
                'type' => 'text',
                'cols' => '200',
                'rows' => '50',
                'wizards' => $configurationFieldWizard,
                // @see \DMK\Mksearch\Backend\Form\Element\IndexerConfigurationField
                'renderType' => 'indexerConfigurationField',
            ),
        ),
        'composites' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs.composites',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_mksearch_configcomposites',
                'foreign_table_where' => ' AND tx_mksearch_configcomposites.pid=###CURRENT_PID### ORDER BY tx_mksearch_configcomposites.title',
                'MM' => 'tx_mksearch_configcomposites_indexerconfigs_mm',
                'MM_opposite_field' => 'configs',
                'size' => 20,
                'minitems' => 0,
                'maxitems' => 100,
                'fieldControl' => array('editPopup' => true, 'addRecord' => true),
                'wizards' => Tx_Rnbase_Utility_TcaTool::getWizards(
                    'tx_mksearch_configcomposites',
                    array('add' => true, 'edit' => true, 'list' => true)
                ),
            ),
        ),
    ),
    'types' => array(
        '0' => array('showitem' => 'hidden, title, description, extkey, contenttype, configuration, composites'),
    ),
    'palettes' => array(
        '1' => array('showitem' => ''),
    ),
);
