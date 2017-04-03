<?php
return array(
	'ctrl' => Array (
		'title' => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_keywords',
		'label' => 'keyword',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY keyword',
		'delete' => 'deleted',
		'enablecolumns' => Array (
			'disabled' => 'hidden',
		),
		'iconfile' => 'EXT:mksearch/icons/tx_mksearch_keywords.gif',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'hidden, keyword, link',
	),
	'interface' => array (
		'showRecordFieldList' => 'hidden,searchword,link'
	),
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
					'wizards' => Tx_Rnbase_Utility_TcaTool::getWizards(
					'',
					array('link' => TRUE)
				)
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'hidden;;1;;1-1-1, keyword, link')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	),
);
