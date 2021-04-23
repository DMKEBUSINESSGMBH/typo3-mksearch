<?php

if (!defined('TYPO3_MODE')) {
    exit('Access denied.');
}

$_EXT_PATH = tx_rnbase_util_Extensions::extPath('mksearch');

// initalize 'context sensitive help' (csh)
require_once $_EXT_PATH.'res/help/ext_csh.php';

if (TYPO3_MODE == 'BE') {
    require_once $_EXT_PATH.'mod1/ext_tables.php';

    // Add plugin wizards
    // register icon
    Tx_Rnbase_Backend_Utility_Icons::getIconRegistry()->registerIcon(
        'ext-mksearch-wizard-icon',
        'TYPO3\\CMS\Core\\Imaging\\IconProvider\\BitmapIconProvider',
        ['source' => 'EXT:mksearch/ext_icon.gif']
    );
    // Wizardkonfiguration hinzufügen
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:mksearch/Configuration/TSconfig/ContentElementWizard.txt">'
    );

    Tx_Rnbase_Backend_Utility_Icons::getIconRegistry()->registerIcon(
        'apps-pagetree-folder-contains-mksearch',
        'TYPO3\\CMS\Core\\Imaging\\IconProvider\\BitmapIconProvider',
        ['source' => 'EXT:mksearch/icons/icon_folder.gif']
    );
}
