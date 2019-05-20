<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

if (tx_rnbase_util_TYPO3::isTYPO60OrHigher()) {
    return;
}

// fallback for TYPO3 < 6
call_user_func(
    function ($extPath) {
        foreach (array(
            'tt_content',
        ) as $table) {
            require $extPath.'Configuration/TCA/Overrides/'.$table.'.php';
        }

        foreach (array(
            'tx_mksearch_indices',
            'tx_mksearch_configcomposites',
            'tx_mksearch_indexerconfigs',
            'tx_mksearch_keywords',
        ) as $table) {
            $GLOBALS['TCA'][$table] = require $extPath.'Configuration/TCA/'.$table.'.php';
        }
    },
    tx_rnbase_util_Extensions::extPath('mksearch')
);
