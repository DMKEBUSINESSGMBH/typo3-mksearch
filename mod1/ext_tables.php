<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE == 'BE') {

	tx_rnbase_util_Extensions::addModulePath('web_txmksearchM1', tx_rnbase_util_Extensions::extPath($_EXTKEY, 'mod1/'));
	tx_rnbase_util_Extensions::addModule('web', 'txmksearchM1', 'bottom', tx_rnbase_util_Extensions::extPath($_EXTKEY, 'mod1/'));

	tx_rnbase_util_Extensions::insertModuleFunction(
		'web_txmksearchM1',
		'tx_mksearch_mod1_ConfigIndizes',
		tx_rnbase_util_Extensions::extPath($_EXTKEY, 'mod1/class.tx_mksearch_mod1_ConfigIndizes.php'),
		'LLL:EXT:mksearch/mod1/locallang.xml:func_config_indizes'
	);
	tx_rnbase_util_Extensions::insertModuleFunction(
		'web_txmksearchM1',
		'tx_mksearch_mod1_Keywords',
		tx_rnbase_util_Extensions::extPath($_EXTKEY, 'mod1/class.tx_mksearch_mod1_Keywords.php'),
		'LLL:EXT:mksearch/mod1/locallang.xml:func_keywords'
	);
	tx_rnbase_util_Extensions::insertModuleFunction(
		'web_txmksearchM1',
		'tx_mksearch_mod1_IndizeIndizes',
		tx_rnbase_util_Extensions::extPath($_EXTKEY, 'mod1/class.tx_mksearch_mod1_IndizeIndizes.php'),
		'LLL:EXT:mksearch/mod1/locallang.xml:func_indize_indizes'
	);
	tx_rnbase_util_Extensions::insertModuleFunction(
		'web_txmksearchM1',
		'tx_mksearch_mod1_SolrAdmin',
		tx_rnbase_util_Extensions::extPath($_EXTKEY, 'mod1/class.tx_mksearch_mod1_SolrAdmin.php'),
		'LLL:EXT:mksearch/mod1/locallang.xml:func_solradmin'
	);

// 	tx_rnbase_util_Extensions::insertModuleFunction(
// 		'web_txmksearchM1',
// 		'tx_mksearch_mod1_ConfigIndizesDbList',
// 		tx_rnbase_util_Extensions::extPath($_EXTKEY, 'mod1/class.tx_mksearch_mod1_ConfigIndizesDbList.php'),
// 		'LLL:EXT:mksearch/mod1/locallang.xml:func_config_indizesdblist'
// 	);

	tx_rnbase::load('Tx_Rnbase_Backend_Utility');
}
