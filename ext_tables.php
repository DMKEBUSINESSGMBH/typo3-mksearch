<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

// Show tt_content-field pi_flexform
$TCA['tt_content']['types']['list']['subtypes_addlist']['tx_mksearch']='pi_flexform';
// Hide some fields
$TCA['tt_content']['types']['list']['subtypes_excludelist']['tx_mksearch']='layout,select_key,pages';

//add our own header_layout. this one isn't displayed in the FE (like the Hidden type)
//as long as there is no additional configuration how to display this type.
//so this type is just like the 100 type in the FE but this type is indexed instead 
//of the standard type (100)
$aTempConfig = $TCA['tt_content']['columns']['header_layout']['config']['items'];
$aTempConfig[] = Array('LLL:EXT:'.$_EXTKEY.'/locallang_db.xml:plugin.mksearch.tt_content.header_layout', '101');
$TCA['tt_content']['columns']['header_layout']['config']['items'] = $aTempConfig;

// Add flexform and plugin
t3lib_extMgm::addPiFlexFormValue('tx_mksearch','FILE:EXT:'.$_EXTKEY.'/flexform_main.xml');
t3lib_extMgm::addPlugin(Array('LLL:EXT:'.$_EXTKEY.'/locallang_db.xml:plugin.mksearch.label','tx_mksearch'));

t3lib_extMgm::addStaticFile($_EXTKEY,'static/static_extension_template/', 'MK Search');

// initalize 'context sensitive help' (csh)
require_once t3lib_extMgm::extPath($_EXTKEY).'res/help/ext_csh.php';

if (TYPO3_MODE == 'BE') {
    require_once(t3lib_extMgm::extPath('mksearch').'mod1/ext_tables.php');
}

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
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icons/icon_tx_mksearch_indices.gif',
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
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icons/icon_tx_mksearch_configcomposites.gif',
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
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icons/icon_tx_mksearch_indexconfigs.gif',
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
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'icons/tx_mksearch_keywords.gif',
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'hidden, keyword, link',
	)
);

