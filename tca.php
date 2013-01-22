<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_mksearch_indices'] = array (
	'ctrl' => $TCA['tx_mksearch_indices']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,title,description,name,composites,configuration'
	),
	'feInterface' => $TCA['tx_mksearch_indices']['feInterface'],
	'columns' => array (
		'hidden' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'title' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices.title',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'max' => '100',
				'eval' => 'required,trim',
			)
		),
		'description' => Array(
			'exclude' => 1,
			'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices.description',
			'config' => Array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '3',
			),
		),
		'name' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices.name',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'max' => '100',
				'eval' => 'required,trim',
			)
		),
		'composites' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices.composites',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'tx_mksearch_configcomposites',
				'foreign_table_where' => ' AND tx_mksearch_configcomposites.pid=###CURRENT_PID### ORDER BY tx_mksearch_configcomposites.title',
				'MM' => 'tx_mksearch_indices_configcomposites_mm',
				'size' => 20,
				'minitems' => 0,
				'maxitems' => 100,
				'wizards' => array(
					'_PADDING'  => 2,
					'_VERTICAL' => 1,
					'add' => array(
						'type'   => 'script',
						'title'  => 'Create new record',
						'icon'   => 'add.gif',
						'params' => array(
							'table'    => 'tx_mksearch_configcomposites',
							'pid'      => '###CURRENT_PID###',
							'setValue' => 'prepend'
						),
						'script' => 'wizard_add.php',
					),
					'list' => array(
						'type'   => 'script',
						'title'  => 'List',
						'icon'   => 'list.gif',
						'params' => array(
							'table' => 'tx_mksearch_configcomposites',
							'pid'   => '###CURRENT_PID###',
						),
						'script' => 'wizard_list.php',
					),
					'edit' => array(
						'type'                     => 'popup',
						'title'                    => 'Edit',
						'script'                   => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon'                     => 'edit2.gif',
						'JSopenParams'             => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
				),
			),
		),
		'engine' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices_engine',
			'config' => array (
				'type' => 'radio',
				'items' => array(
					array('LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices_engine_zendlucene','zend_lucene'),
					array('LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices_engine_solr','solr'),
				),
				'eval' => 'required',
				'default' => 'zend_lucene'
			)
		),
		'solrversion' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices_solrversion',
			'config' => array (
				'type' => 'radio',
				'items' => array(
					array('LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices_solrversion_35',35),
					array('LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices_solrversion_40',40),
				),
				'default' => 35
			),
			'displayCond' => 'FIELD:engine:=:solr'
		),
		'configuration' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs.configuration',
			'config' => array (
				'type' => 'text',
				'cols' => '200',
				'rows' => '50',
				'default' => '# Insert the default config for this index.'
			)
		)
	),
	'types' => array (
		'0' => array('showitem' => 'hidden;;1, title, description, engine, solrversion, configuration, name, composites')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);

$TCA['tx_mksearch_configcomposites'] = array (
	'ctrl' => $TCA['tx_mksearch_configcomposites']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,title,description,indices,configs,configuration'
	),
	'feInterface' => $TCA['tx_mksearch_configcomposites']['feInterface'],
	'columns' => array (
		'hidden' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_configcomposites.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'title' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_configcomposites.title',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'max' => '100',
				'eval' => 'required,trim',
			)
		),
		'description' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_configcomposites.description',
			'config' => array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
			)
		),
		'indices' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_configcomposites.indices',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'tx_mksearch_indices',
				'foreign_table_where' => ' AND tx_mksearch_indices.pid=###CURRENT_PID### ORDER BY tx_mksearch_indices.title',
				'MM' => 'tx_mksearch_indices_configcomposites_mm',
				'MM_opposite_field' => 'composites',
				'size' => 20,
				'minitems' => 0,
				'maxitems' => 100,
				'wizards' => array(
					'_PADDING'  => 2,
					'_VERTICAL' => 1,
					'add' => array(
						'type'   => 'script',
						'title'  => 'Create new record',
						'icon'   => 'add.gif',
						'params' => array(
							'table'    => 'tx_mksearch_indices',
							'pid'      => '###CURRENT_PID###',
							'setValue' => 'prepend'
						),
						'script' => 'wizard_add.php',
					),
					'list' => array(
						'type'   => 'script',
						'title'  => 'List',
						'icon'   => 'list.gif',
						'params' => array(
							'table' => 'tx_mksearch_indices',
							'pid'   => '###CURRENT_PID###',
						),
						'script' => 'wizard_list.php',
					),
					'edit' => array(
						'type'                     => 'popup',
						'title'                    => 'Edit',
						'script'                   => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon'                     => 'edit2.gif',
						'JSopenParams'             => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
				),
			)
		),
		'configs' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_configcomposites.configs',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'tx_mksearch_indexerconfigs',
				'foreign_table_where' => ' AND tx_mksearch_indexerconfigs.pid=###CURRENT_PID### ORDER BY tx_mksearch_indexerconfigs.title',
				'MM' => 'tx_mksearch_configcomposites_indexerconfigs_mm',
				'size' => 20,
				'minitems' => 0,
				'maxitems' => 100,
				'wizards' => array(
					'_PADDING'  => 2,
					'_VERTICAL' => 1,
					'add' => array(
						'type'   => 'script',
						'title'  => 'Create new record',
						'icon'   => 'add.gif',
						'params' => array(
							'table'    => 'tx_mksearch_indexerconfigs',
							'pid'      => '###CURRENT_PID###',
							'setValue' => 'prepend'
						),
						'script' => 'wizard_add.php',
					),
					'list' => array(
						'type'   => 'script',
						'title'  => 'List',
						'icon'   => 'list.gif',
						'params' => array(
							'table' => 'tx_mksearch_indexerconfigs',
							'pid'   => '###CURRENT_PID###',
						),
						'script' => 'wizard_list.php',
					),
					'edit' => array(
						'type'                     => 'popup',
						'title'                    => 'Edit',
						'script'                   => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon'                     => 'edit2.gif',
						'JSopenParams'             => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
				),
			)
		),
		'configuration' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_configcomposites.configuration',
			'config' => array (
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
	'types' => array (
		'0' => array('showitem' => 'hidden;;1, title, description, indices, configs, configuration')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);

$TCA['tx_mksearch_indexerconfigs'] = array (
	'ctrl' => $TCA['tx_mksearch_indexerconfigs']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,title,description,extkey,contenttype,config,composites'
	),
	'feInterface' => $TCA['tx_mksearch_indexerconfigs']['feInterface'],
	'columns' => array (
		'hidden' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs.hidden',
				'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'title' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs.title',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'max' => '100',
				'eval' => 'required,trim',
			)
		),
		'description' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs.description',
			'config' => array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
			)
		),
		'extkey' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs.extkey',
			'config' => array (
				'type' => 'select',
				'items' => array(array('','')),
				'itemsProcFunc' => 'EXT:mksearch/util/class.tx_mksearch_util_TCA.php:tx_mksearch_util_TCA->getIndexerExtKeys',
				'size' => '1',
				'maxitems' => '1',
			)
		),
		'contenttype' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs.contenttype',
			'config' => array (
				'type' => 'select',
				'items' => array(array('','')),
				'itemsProcFunc' => 'EXT:mksearch/util/class.tx_mksearch_util_TCA.php:tx_mksearch_util_TCA->getIndexerContentTypes',
				'size' => '1',
				'maxitems' => '1',
				'eval' => 'required,trim',
			)
		),
		'configuration' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs.configuration',
			'config' => array (
				'type' => 'text',
				'cols' => '200',
				'rows' => '50',
				'wizards' => array(
					'appendDefaultTSConfig' => array(
						'type'   => 'userFunc',
						'notNewRecords' => 1,
						'userFunc' => 'EXT:mksearch/util/class.tx_mksearch_util_TCA.php:tx_mksearch_util_TCA->insertIndexerDefaultTSConfig',
						'params' => array(
							'insertBetween' => array('>', "</textarea"),
							'onMatchOnly' => '/^\s*$/',
						),
					),
				)
			)
		),
		'composites' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs.composites',
			'config' => array (
				'type' => 'select',
				'foreign_table' => 'tx_mksearch_configcomposites',
				'foreign_table_where' => ' AND tx_mksearch_configcomposites.pid=###CURRENT_PID### ORDER BY tx_mksearch_configcomposites.title',
				'MM' => 'tx_mksearch_configcomposites_indexerconfigs_mm',
				'MM_opposite_field' => 'configs',
				'size' => 20,
				'minitems' => 0,
				'maxitems' => 100,
				'wizards' => array(
					'_PADDING'  => 2,
					'_VERTICAL' => 1,
					'add' => array(
						'type'   => 'script',
						'title'  => 'Create new record',
						'icon'   => 'add.gif',
						'params' => array(
							'table'    => 'tx_mksearch_configcomposites',
							'pid'      => '###CURRENT_PID###',
							'setValue' => 'prepend'
						),
						'script' => 'wizard_add.php',
					),
					'list' => array(
						'type'   => 'script',
						'title'  => 'List',
						'icon'   => 'list.gif',
						'params' => array(
							'table' => 'tx_mksearch_configcomposites',
							'pid'   => '###CURRENT_PID###',
						),
						'script' => 'wizard_list.php',
					),
					'edit' => array(
						'type'                     => 'popup',
						'title'                    => 'Edit',
						'script'                   => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon'                     => 'edit2.gif',
						'JSopenParams'             => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
				),
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'hidden;;1, title, description, extkey, contenttype, configuration, composites')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);

$TCA['tx_mksearch_keywords'] = array (
	'ctrl' => $TCA['tx_mksearch_keywords']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,searchword,link'
	),
	'feInterface' => $TCA['tx_mksearch_keywords']['feInterface'],
	'columns' => array (
		'hidden' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'keyword' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_keywords_keyword',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			)
		),
		'link' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_keywords_link',
			'config' => Array (
				'type' => 'input',
				'size' => '15',
				'max' => '255',
				'checkbox' => '',
				'eval' => 'trim',
				'wizards' => Array(
					'_PADDING' => 2,
					'link' => Array(
						'type' => 'popup',
						'title' => 'Link',
						'icon' => 'link_popup.gif',
						'script' => 'browse_links.php?mode=wizard',
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
					)
				)
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'hidden;;1;;1-1-1, keyword, link')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);
