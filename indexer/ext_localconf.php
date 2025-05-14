<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of the "mksearch" Extension for TYPO3 CMS.
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GNU Lesser General Public License can be found at
 * www.gnu.org/licenses/lgpl.html
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

if (!defined('TYPO3')) {
    exit('Access denied.');
}

// Activate indexer services
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['active'][] = 'core.page';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['active'][] = 'core.tt_content';

// Define table to content type mappings

tx_mksearch_util_Config::registerIndexer(
    'core',
    'page',
    'tx_mksearch_indexer_Page',
    [
        'pages',
        // @todo handle page overlay
        'pages_language_overlay',
    ]
);

tx_mksearch_util_Config::registerIndexer(
    'core',
    'tt_content',
    'tx_mksearch_indexer_TtContent',
    [
        // Main Table
        'tt_content',
        // related tables
        'pages',
    ]
);

if (TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('news')) {
    tx_mksearch_util_Config::registerIndexer(
        'tx_news',
        'news',
        'tx_mksearch_indexer_TxNewsNews',
        [
            // Main Table
            'tx_news_domain_model_news',
            // related/monitored tables
            'sys_category',
            'tx_news_domain_model_tag',
        ]
    );
}

if (TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('tt_address')) {
    tx_mksearch_util_Config::registerIndexer('tt_address', 'address', 'tx_mksearch_indexer_TtAddressAddress', ['tt_address']);
}

tx_mksearch_util_Config::registerIndexer('core', 'file', 'tx_mksearch_indexer_FAL', ['sys_file', 'sys_file_metadata']);

// seminars Extension
if (TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('seminars')) {
    tx_mksearch_util_Config::registerIndexer(
        'seminars',
        'seminar',
        'tx_mksearch_indexer_seminars_Seminar',
        [
            // main table
            'tx_seminars_seminars',
            // tables with related data
            'tx_seminars_categories',
            'tx_seminars_organizers',
            'tx_seminars_sites',
            'tx_seminars_speakers',
            'tx_seminars_target_groups',
            'tx_seminars_timeslots',
        ]
    );
}

// Configure core page indexer service
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config']['core']['page']['indexedFields'] = ['subtitle', 'url', 'keywords', 'description', 'author', /* 'author_email', */ 'nav_title', 'alias'];

// Configure tt_news indexer service
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config']['tt_news']['news']['indexedFields'] = ['imagealttext', 'imagetitletext', 'short', 'bodytext', 'keywords'];
