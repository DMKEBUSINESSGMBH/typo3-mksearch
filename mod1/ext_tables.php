<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE == 'BE') {
	
    t3lib_extMgm::addModulePath('web_txmksearchM1', t3lib_extMgm::extPath($_EXTKEY, 'mod1/'));
    t3lib_extMgm::addModule('web', 'txmksearchM1', 'bottom', t3lib_extMgm::extPath($_EXTKEY, 'mod1/'));
    
    t3lib_extMgm::insertModuleFunction(
    	'web_txmksearchM1',
    	'tx_mksearch_mod1_ConfigIndizes',
    	t3lib_extMgm::extPath($_EXTKEY, 'mod1/class.tx_mksearch_mod1_ConfigIndizes.php'),
		'LLL:EXT:mksearch/mod1/locallang.xml:func_config_indizes'
    );
    t3lib_extMgm::insertModuleFunction(
    	'web_txmksearchM1',
    	'tx_mksearch_mod1_Keywords',
    	t3lib_extMgm::extPath($_EXTKEY, 'mod1/class.tx_mksearch_mod1_Keywords.php'),
		'LLL:EXT:mksearch/mod1/locallang.xml:func_keywords'
    );
	t3lib_extMgm::insertModuleFunction(
		'web_txmksearchM1',
		'tx_mksearch_mod1_IndizeIndizes',
  		t3lib_extMgm::extPath($_EXTKEY, 'mod1/class.tx_mksearch_mod1_IndizeIndizes.php'),
  		'LLL:EXT:mksearch/mod1/locallang.xml:func_indize_indizes'
  	);
	
	t3lib_extMgm::insertModuleFunction(
		'web_txmksearchM1',
		'tx_mksearch_mod1_ConfigIndizesDbList',
  		t3lib_extMgm::extPath($_EXTKEY, 'mod1/class.tx_mksearch_mod1_ConfigIndizesDbList.php'),
  		'LLL:EXT:mksearch/mod1/locallang.xml:func_config_indizesdblist'
  	);
}
