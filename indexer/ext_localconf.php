<?php

if (!defined('TYPO3_MODE')) {
    exit('Access denied.');
}

// Activate indexer services
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['active'][] = 'core.page';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['active'][] = 'core.tt_content';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['active'][] = 'tt_news.news';

// Define table to content type mappings

tx_mksearch_util_Config::registerIndexer(
    'core',
    'page',
    'tx_mksearch_indexer_Page',
    [
        'pages',
        //@todo handle page overlay
        'pages_language_overlay',
    ]
);

tx_mksearch_util_Config::registerIndexer(
    'core',
    'tt_content',
    'tx_mksearch_indexer_TtContent',
    [
        //Main Table
        'tt_content',
        //related tables
        'pages',
    ]
);

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('tt_news')) {
    tx_mksearch_util_Config::registerIndexer('tt_news', 'news', 'tx_mksearch_indexer_TtNewsNews', ['tt_news', 'tt_news_cat']);
}
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('news')) {
    tx_mksearch_util_Config::registerIndexer(
        'tx_news',
        'news',
        'tx_mksearch_indexer_TxNewsNews',
        [
            //Main Table
            'tx_news_domain_model_news',
            //related/monitored tables
            'sys_category',
            'tx_news_domain_model_tag',
        ]
    );
}

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('tt_address')) {
    tx_mksearch_util_Config::registerIndexer('tt_address', 'address', 'tx_mksearch_indexer_TtAddressAddress', ['tt_address']);
}

tx_mksearch_util_Config::registerIndexer('core', 'file', 'tx_mksearch_indexer_FAL', ['sys_file', 'sys_file_metadata']);

// seminars Extension
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('seminars')) {
    tx_mksearch_util_Config::registerIndexer(
        'seminars',
        'seminar',
        'tx_mksearch_indexer_seminars_Seminar',
        [
            //main table
            'tx_seminars_seminars',
            //tables with related data
            'tx_seminars_categories',
            'tx_seminars_organizers',
            'tx_seminars_sites',
            'tx_seminars_speakers',
            'tx_seminars_target_groups',
            'tx_seminars_timeslots',
        ]
    );
}

// irfaq Extension
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('irfaq')) {
    tx_mksearch_util_Config::registerIndexer(
        'irfaq',
        'question',
        'tx_mksearch_indexer_Irfaq',
        [
            //main table
            'tx_irfaq_q',
            //tables with related data
            'tx_irfaq_expert',
            'tx_irfaq_cat',
        ]
    );
}

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('efaq')) {
    tx_mksearch_util_Config::registerIndexer('efaq', 'faq', 'tx_mksearch_indexer_Efaq', ['tx_efaq_faqs']);
}

// cal Extension
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cal')) {
    tx_mksearch_util_Config::registerIndexer(
        'cal',
        'event',
        'tx_mksearch_indexer_Cal',
        [
            //main table
            'tx_cal_event',
            //tables with related data
            'tx_cal_category',
            'tx_cal_calendar',
        ]
    );
}

// cal Extension
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('a21glossary')) {
    tx_mksearch_util_Config::registerIndexer(
        'a21glossary',
        'main',
        'tx_mksearch_indexer_A21Glossary',
        ['tx_a21glossary_main']
    );
}

// Configure core page indexer service
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config']['core']['page']['indexedFields'] = ['subtitle', 'url', 'keywords', 'description', 'author', /*'author_email',*/ 'nav_title', 'alias'];

// Configure tt_news indexer service
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config']['tt_news']['news']['indexedFields'] = ['imagealttext', 'imagetitletext', 'short', 'bodytext', 'keywords'];
