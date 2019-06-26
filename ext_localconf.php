<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

// prepare extension config
$_EXTCONF = empty($_EXTCONF) ? array() : (is_array($_EXTCONF) ? $_EXTCONF : unserialize($_EXTCONF));

// Include service configuration
require_once tx_rnbase_util_Extensions::extPath('mksearch').'service/ext_localconf.php';

// Include indexer registrations
require_once tx_rnbase_util_Extensions::extPath('mksearch').'indexer/ext_localconf.php';

// Register hooks
// Hooks for converting Zend_Lucene index data
//$GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['engine_ZendLucene_indexNew_beforeAddingCoreDataToDocument'][] =
//  'EXT:' . $_EXTKEY . '/hooks/class.tx_mksearch_hooks_EngineZendLucene.php:tx_mksearch_hooks_EngineZendLucene->convertFields';
//$GLOBALS ['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['engine_ZendLucene_indexNew_beforeAddingAdditionalDataToDocument'][] =
//  'EXT:' . $_EXTKEY . '/hooks/class.tx_mksearch_hooks_EngineZendLucene.php:tx_mksearch_hooks_EngineZendLucene->convertFields';

// rnbase insert and update hooks (requires rn_base 0.14.6)
if (isset($_EXTCONF['enableRnBaseUtilDbHook']) && (int) $_EXTCONF['enableRnBaseUtilDbHook'] > 0) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rn_base']['util_db_do_insert_post'][] =
        'tx_mksearch_hooks_IndexerAutoUpdate->rnBaseDoInsertPost';
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rn_base']['util_db_do_update_post'][] =
        'tx_mksearch_hooks_IndexerAutoUpdate->rnBaseDoUpdatePost';
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rn_base']['util_db_do_delete_pre'][] =
        'tx_mksearch_hooks_IndexerAutoUpdate->rnBaseDoDeletePre';
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rn_base']['util_db_do_select_pre'][] =
    'tx_mksearch_hooks_DatabaseConnection->doSelectPre';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rn_base']['util_db_do_select_post'][] =
    'tx_mksearch_hooks_DatabaseConnection->doSelectPost';

// Hook for manipulating a single search term used with Zend_Lucene
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['engine_ZendLucene_buildQuery_manipulateSingleTerm'][] =
    'tx_mksearch_hooks_EngineZendLucene->manipulateSingleTerm';

// Hooks for auto-updating search indices
// TODO: Die Hooks per EM-Config zuschaltbar machen
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] =
    'tx_mksearch_hooks_IndexerAutoUpdate';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] =
    'tx_mksearch_hooks_IndexerAutoUpdate';
// Include PageTSConfig for backend module
tx_rnbase_util_Extensions::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:mksearch/mod1/pageTSconfig.txt">');

// Register information for the test and sleep tasks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_mksearch_scheduler_IndexTask'] = array(
    'extension' => $_EXTKEY,
    'title' => 'LLL:EXT:'.$_EXTKEY.'/locallang_db.xml:scheduler_indexTask_name',
    'description' => 'LLL:EXT:'.$_EXTKEY.'/locallang_db.xml:scheduler_indexTask_description',
    'additionalFields' => 'tx_mksearch_scheduler_IndexTaskAddFieldProvider',
);

if (tx_rnbase_util_Extensions::isLoaded('mksanitizedparameters')) {
    require_once tx_rnbase_util_Extensions::extPath($_EXTKEY, 'ext_mksanitizedparameter_rules.php');
}

require_once tx_rnbase_util_Extensions::extPath($_EXTKEY, 'Configuration/SignalSlotDispatcher.php');
require_once tx_rnbase_util_Extensions::extPath($_EXTKEY, 'Configuration/XClasses.php');

Tx_Rnbase_Utility_Cache::addExcludedParametersForCacheHash(array(
    'mksearch[pb-search-pointer]',
    'mksearch[submit]',
    'mksearch[term]',
));

// eigenes Feld für Vorbelegung je nach Indexer
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry']['mksearch_indexerConfigurationField'] = array(
    'nodeName' => 'indexerConfigurationField',
    'priority' => '70',
    'class' => 'DMK\\Mksearch\\Backend\\Form\\Element\\IndexerConfigurationField',
);

// no_search needs to be in the rootline fields so respectNoSearchFlagInRootline
// in indexers works correct
$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] .= ',no_search';
