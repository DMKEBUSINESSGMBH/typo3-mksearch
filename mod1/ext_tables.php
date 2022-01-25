<?php

defined('TYPO3_MODE') or exit('Access denied.');

if (TYPO3_MODE == 'BE') {
    \Sys25\RnBase\Utility\Extensions::registerModule(
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

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
        'web_MksearchM1',
        'tx_mksearch_mod1_ConfigIndizes',
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mksearch', 'mod1/class.tx_mksearch_mod1_ConfigIndizes.php'),
        'LLL:EXT:mksearch/mod1/locallang.xml:func_config_indizes'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
        'web_MksearchM1',
        'tx_mksearch_mod1_Keywords',
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mksearch', 'mod1/class.tx_mksearch_mod1_Keywords.php'),
        'LLL:EXT:mksearch/mod1/locallang.xml:func_keywords'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
        'web_MksearchM1',
        'tx_mksearch_mod1_IndizeIndizes',
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mksearch', 'mod1/class.tx_mksearch_mod1_IndizeIndizes.php'),
        'LLL:EXT:mksearch/mod1/locallang.xml:func_indize_indizes'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
        'web_MksearchM1',
        'tx_mksearch_mod1_SolrAdmin',
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mksearch', 'mod1/class.tx_mksearch_mod1_SolrAdmin.php'),
        'LLL:EXT:mksearch/mod1/locallang.xml:func_solradmin'
    );
}
