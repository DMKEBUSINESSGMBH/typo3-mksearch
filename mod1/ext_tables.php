<?php

defined('TYPO3_MODE') or exit('Access denied.');

if (TYPO3_MODE == 'BE') {
    tx_rnbase_util_Extensions::registerModule(
        'mksearch',
        'web',
        'M1',
        'bottom',
        [],
        [
            'access' => 'user,group',
            'routeTarget' => 'tx_mksearch_mod1_Module',
            'icon' => 'EXT:mksearch/mod1/moduleicon.png',
            'labels' => 'LLL:EXT:mksearch/mod1/locallang_mod.xml',
        ]
    );

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
}
