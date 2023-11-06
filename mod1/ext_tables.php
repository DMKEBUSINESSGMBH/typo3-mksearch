<?php

defined('TYPO3') or exit('Access denied.');

if (!\Sys25\RnBase\Utility\TYPO3::isTYPO121OrHigher()) {
    \Sys25\RnBase\Utility\Extensions::registerModule(
        'mksearch',
        'web',
        'M1',
        'bottom',
        [],
        [
            'access' => 'user,group',
            'routeTarget' => 'tx_mksearch_mod1_Module',
            'icon' => 'EXT:mksearch/Resources/Public/Icons/Extension.svg',
            'labels' => 'LLL:EXT:mksearch/Resources/Private/Language/BackendModule/locallang_mod.xlf',
        ]
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
        'web_MksearchM1',
        'tx_mksearch_mod1_ConfigIndizes',
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mksearch', 'mod1/class.tx_mksearch_mod1_ConfigIndizes.php'),
        'LLL:EXT:mksearch/Resources/Private/Language/BackendModule/locallang.xlf:func_config_indizes'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
        'web_MksearchM1',
        'tx_mksearch_mod1_Keywords',
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mksearch', 'mod1/class.tx_mksearch_mod1_Keywords.php'),
        'LLL:EXT:mksearch/Resources/Private/Language/BackendModule/locallang.xlf:func_keywords'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
        'web_MksearchM1',
        'tx_mksearch_mod1_IndizeIndizes',
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mksearch', 'mod1/class.tx_mksearch_mod1_IndizeIndizes.php'),
        'LLL:EXT:mksearch/Resources/Private/Language/BackendModule/locallang.xlf:func_indize_indizes'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
        'web_MksearchM1',
        'tx_mksearch_mod1_SolrAdmin',
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mksearch', 'mod1/class.tx_mksearch_mod1_SolrAdmin.php'),
        'LLL:EXT:mksearch/Resources/Private/Language/BackendModule/locallang.xlf:func_solradmin'
    );
}
