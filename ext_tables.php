<?php

if (!defined('TYPO3')) {
    exit('Access denied.');
}

$_EXT_PATH = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mksearch');

require_once $_EXT_PATH.'mod1/ext_tables.php';

// Add plugin wizards
// register icon
\Sys25\RnBase\Backend\Utility\Icons::getIconRegistry()->registerIcon(
    'ext-mksearch-wizard-icon',
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    ['source' => 'EXT:mksearch/Resources/Public/Icons/Extension.svg']
);
// Wizardkonfiguration hinzufügen
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:mksearch/Configuration/TSconfig/ContentElementWizard.txt">'
);

\Sys25\RnBase\Backend\Utility\Icons::getIconRegistry()->registerIcon(
    'apps-pagetree-folder-contains-mksearch',
    'TYPO3\\CMS\Core\\Imaging\\IconProvider\\BitmapIconProvider',
    ['source' => 'EXT:mksearch/Resources/Public/Icons/icon_folder.gif']
);
