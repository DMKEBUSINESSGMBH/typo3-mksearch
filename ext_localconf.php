<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}

// Include service configuration
require_once(t3lib_extMgm::extPath('mksearch').'service/ext_localconf.php');

// Include indexer registrations
require_once(t3lib_extMgm::extPath('mksearch').'indexer/ext_localconf.php');

// Setting up scripts that can be run from the cli_dispatch.phpsh script.
$TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys'][$_EXTKEY] = array('EXT:'.$_EXTKEY.'/cli/crawler.php','_CLI_mksearch_luceneindexer');

// Register hooks
// Hooks for converting Zend_Lucene index data
//$GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['engine_ZendLucene_indexNew_beforeAddingCoreDataToDocument'][] =	
//	'EXT:' . $_EXTKEY . '/hooks/class.tx_mksearch_hooks_EngineZendLucene.php:tx_mksearch_hooks_EngineZendLucene->convertFields';
//$GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['engine_ZendLucene_indexNew_beforeAddingAdditionalDataToDocument'][] =	
//	'EXT:' . $_EXTKEY . '/hooks/class.tx_mksearch_hooks_EngineZendLucene.php:tx_mksearch_hooks_EngineZendLucene->convertFields';

// Hook for manipulating a single search term used with Zend_Lucene
$GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['engine_ZendLucene_buildQuery_manipulateSingleTerm'][] =	
	'EXT:' . $_EXTKEY . '/hooks/class.tx_mksearch_hooks_EngineZendLucene.php:tx_mksearch_hooks_EngineZendLucene->manipulateSingleTerm';

// Hooks for auto-updating search indices
// TODO: Die Hooks per EM-Config zuschaltbar machen
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] =
	'EXT:' . $_EXTKEY . '/hooks/class.tx_mksearch_hooks_IndexerAutoUpdate.php:tx_mksearch_hooks_IndexerAutoUpdate';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] =
	'EXT:' . $_EXTKEY . '/hooks/class.tx_mksearch_hooks_IndexerAutoUpdate.php:tx_mksearch_hooks_IndexerAutoUpdate';
// Include PageTSConfig for backend module
t3lib_extMgm::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:mksearch/mod1/pageTSconfig.txt">');


//require_once t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php';
//tx_rnbase::load('tx_mksearch_scheduler_IndexTask');
//tx_rnbase::load('tx_mksearch_scheduler_IndexTaskAddFieldProvider');

// Register information for the test and sleep tasks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_mksearch_scheduler_IndexTask'] = array(
	'extension'        => $_EXTKEY,
	'title'            => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:scheduler_indexTask_name',
	'description'      => 'LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:scheduler_indexTask_description',
	'additionalFields' => 'tx_mksearch_scheduler_IndexTaskAddFieldProvider'
);
