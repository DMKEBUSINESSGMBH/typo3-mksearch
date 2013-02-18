<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}
require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
require_once(t3lib_extMgm::extPath($_EXTKEY).'tca/hooks/ext_tables.php');
$_EXT_PATH = t3lib_extMgm::extPath($_EXTKEY);
$_EXT_RELPATH = t3lib_extMgm::extRelPath($_EXTKEY);

$TCA['tx_mksearch_indices'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indices',
		'label'     => 'title',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY title',
		'delete' => 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => $_EXT_PATH.'tca/tca.php',
		'iconfile'          => $_EXT_RELPATH.'icons/icon_tx_mksearch_indices.gif',
		'requestUpdate'		=> 'engine'
	),
);

$TCA['tx_mksearch_configcomposites'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_configcomposites',
		'label'     => 'title',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY title',
		'delete' => 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => $_EXT_PATH.'tca/tca.php',
		'iconfile'          => $_EXT_RELPATH.'icons/icon_tx_mksearch_configcomposites.gif',
	),
);

$TCA['tx_mksearch_indexerconfigs'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:mksearch/locallang_db.xml:tx_mksearch_indexerconfigs',
		'label'     => 'title',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY title',
		'delete' => 'deleted',
		'enablecolumns' => array (
			'disabled' => 'hidden',
		),
		'requestUpdate' => 'extkey,contenttype',
		'dynamicConfigFile' => $_EXT_PATH.'tca/tca.php',
		'iconfile'          => $_EXT_RELPATH.'icons/icon_tx_mksearch_indexconfigs.gif',
	),
);

$TCA['tx_mksearch_keywords'] = Array (
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
		'dynamicConfigFile' => $_EXT_PATH.'tca/tca.php',
		'iconfile' => $_EXT_RELPATH.'icons/tx_mksearch_keywords.gif',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'hidden, keyword, link',
	)
);