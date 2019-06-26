<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$_EXT_PATH = tx_rnbase_util_Extensions::extPath($_EXTKEY);

// Show tt_content-field pi_flexform
$TCA['tt_content']['types']['list']['subtypes_addlist']['tx_mksearch'] = 'pi_flexform';
// Hide some fields
$TCA['tt_content']['types']['list']['subtypes_excludelist']['tx_mksearch'] = 'select_key';

//add our own header_layout. this one isn't displayed in the FE (like the Hidden type)
//as long as there is no additional configuration how to display this type.
//so this type is just like the 100 type in the FE but this type is indexed instead
//of the standard type (100)
$aTempConfig = $TCA['tt_content']['columns']['header_layout']['config']['items'];
$aTempConfig[] = array('LLL:EXT:'.$_EXTKEY.'/locallang_db.xml:plugin.mksearch.tt_content.header_layout', '101');
$TCA['tt_content']['columns']['header_layout']['config']['items'] = $aTempConfig;

// Add flexform and plugin
tx_rnbase_util_Extensions::addPiFlexFormValue('tx_mksearch', 'FILE:EXT:'.$_EXTKEY.'/flexform_main.xml');
tx_rnbase_util_Extensions::addPlugin(
    ['LLL:EXT:'.$_EXTKEY.'/locallang_db.xml:plugin.mksearch.label','tx_mksearch'],
    'list_type',
    'mksearch'
);

tx_rnbase_util_Extensions::addStaticFile($_EXTKEY, 'static/static_extension_template/', 'MK Search');

// initalize 'context sensitive help' (csh)
require_once $_EXT_PATH.'res/help/ext_csh.php';

if (TYPO3_MODE == 'BE') {
    require_once $_EXT_PATH.'mod1/ext_tables.php';

    // Add plugin wizards
    // register icon
    Tx_Rnbase_Backend_Utility_Icons::getIconRegistry()->registerIcon(
        'ext-mksearch-wizard-icon',
        'TYPO3\\CMS\Core\\Imaging\\IconProvider\\BitmapIconProvider',
        array('source' => 'EXT:mksearch/ext_icon.gif')
    );
    // Wizardkonfiguration hinzufügen
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:mksearch/Configuration/TSconfig/ContentElementWizard.txt">'
    );

    // icon für sysfolder registrieren
    tx_rnbase::load('tx_rnbase_util_TYPO3');
    if (tx_rnbase_util_TYPO3::isTYPO80OrHigher()) {
        // \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
        $GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = array(
            'MK Search',
            'mksearch',
            'apps-pagetree-folder-contains-mksearch',
        );
        Tx_Rnbase_Backend_Utility_Icons::getIconRegistry()->registerIcon(
            'apps-pagetree-folder-contains-mksearch',
            'TYPO3\\CMS\Core\\Imaging\\IconProvider\\BitmapIconProvider',
            array('source' => 'EXT:mksearch/icons/icon_folder.gif')
        );
    } else {
        $GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = array(
            'MK Search',
            'mksearch',
            'EXT:mksearch/icons/icon_folder.gif'
        );

        $spriteManager = tx_rnbase_util_Typo3Classes::getSpriteManagerClass();
        $spriteManager::addTcaTypeIcon('pages', 'contains-'.$_EXTKEY, 'EXT:mksearch/icons/icon_folder.gif');
    }
}

//TCA registrieren
require(tx_rnbase_util_Extensions::extPath($_EXTKEY).'tca/ext_tables.php');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_mksearch_indices');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_mksearch_configcomposites');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_mksearch_indexerconfigs');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_mksearch_keywords');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_mksearch_queue');
