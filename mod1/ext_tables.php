<?php
defined('TYPO3_MODE') or die('Access denied.');

if (TYPO3_MODE == 'BE') {

	if (tx_rnbase_util_TYPO3::isTYPO60OrHigher()) {
		tx_rnbase::load('tx_mksearch_mod1_Module');
		tx_rnbase_util_Extensions::registerModule(
			'mksearch',
			'web',
			'M1',
			'bottom',
			array(),
			array(
				'access' => 'user,group',
				'routeTarget' => 'tx_mksearch_mod1_Module',
				'icon' => 'EXT:mksearch/mod1/moduleicon.gif',
				'labels' => 'LLL:EXT:mksearch/mod1/locallang_mod.xml',
			)
		);
	} else {
		tx_rnbase_util_Extensions::addModulePath(
			'web_MksearchM1',
			tx_rnbase_util_Extensions::extPath('mksearch', 'mod1/')
		);
		tx_rnbase_util_Extensions::addModule(
			'web',
			'MksearchM1',
			'bottom',
			tx_rnbase_util_Extensions::extPath('mksearch', 'mod1/')
		);
	}

	tx_rnbase_util_Extensions::insertModuleFunction(
		'web_MksearchM1',
		'tx_mksearch_mod1_ConfigIndizes',
		tx_rnbase_util_Extensions::extPath('mksearch', 'mod1/class.tx_mksearch_mod1_ConfigIndizes.php'),
		'LLL:EXT:mksearch/mod1/locallang.xml:func_config_indizes'
	);
	tx_rnbase_util_Extensions::insertModuleFunction(
		'web_MksearchM1',
		'tx_mksearch_mod1_Keywords',
		tx_rnbase_util_Extensions::extPath('mksearch', 'mod1/class.tx_mksearch_mod1_Keywords.php'),
		'LLL:EXT:mksearch/mod1/locallang.xml:func_keywords'
	);
	tx_rnbase_util_Extensions::insertModuleFunction(
		'web_MksearchM1',
		'tx_mksearch_mod1_IndizeIndizes',
		tx_rnbase_util_Extensions::extPath('mksearch', 'mod1/class.tx_mksearch_mod1_IndizeIndizes.php'),
		'LLL:EXT:mksearch/mod1/locallang.xml:func_indize_indizes'
	);
	tx_rnbase_util_Extensions::insertModuleFunction(
		'web_MksearchM1',
		'tx_mksearch_mod1_SolrAdmin',
		tx_rnbase_util_Extensions::extPath('mksearch', 'mod1/class.tx_mksearch_mod1_SolrAdmin.php'),
		'LLL:EXT:mksearch/mod1/locallang.xml:func_solradmin'
	);

// 	tx_rnbase_util_Extensions::insertModuleFunction(
// 		'web_MksearchM1',
// 		'tx_mksearch_mod1_ConfigIndizesDbList',
// 		tx_rnbase_util_Extensions::extPath('mksearch', 'mod1/class.tx_mksearch_mod1_ConfigIndizesDbList.php'),
// 		'LLL:EXT:mksearch/mod1/locallang.xml:func_config_indizesdblist'
// 	);

	tx_rnbase::load('Tx_Rnbase_Backend_Utility');
}
