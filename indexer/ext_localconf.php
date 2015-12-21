<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}


// Activate indexer services

// Activate at most ONE of 'core.page' OR 'templavoila.page' indexer services!
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['active'][] = 'core.page';
//$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['active'][] = 'templavoila.page';
// Activate at most ONE of 'core.tt_content' OR 'templavoila.tt_content' indexer services!
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['active'][] = 'core.tt_content';
//$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['active'][] = 'templavoila.tt_content';

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['active'][] = 'tt_news.news';

// Define table to content type mappings

tx_rnbase::load('tx_rnbase_util_TYPO3');
tx_rnbase::load('tx_mksearch_util_Config');

tx_mksearch_util_Config::registerIndexer(
	'core',
	'page',
	'tx_mksearch_indexer_Page',
	array(
		'pages',
		//@todo handle page overlay
		'pages_language_overlay'
	)
);

tx_mksearch_util_Config::registerIndexer(
	'core',
	'tt_content',
	'tx_mksearch_indexer_TtContent',
	array(
		//Main Table
		'tt_content',
		//related tables
		'pages'
	)
);


if (tx_rnbase_util_Extensions::isLoaded('tt_news')) {
	tx_mksearch_util_Config::registerIndexer('tt_news', 'news', 'tx_mksearch_indexer_TtNewsNews', array('tt_news', 'tt_news_cat'));
}

if (tx_rnbase_util_Extensions::isLoaded('tt_address')) {
	tx_mksearch_util_Config::registerIndexer('tt_address', 'address', 'tx_mksearch_indexer_TtAddressAddress', array('tt_address'));
}

if (tx_rnbase_util_Extensions::isLoaded('dam')) {
	tx_mksearch_util_Config::registerIndexer('dam', 'media', 'tx_mksearch_indexer_DamMedia', array('tx_dam'));
}

if (tx_rnbase_util_TYPO3::isTYPO60OrHigher()) {
	tx_mksearch_util_Config::registerIndexer('core', 'file', 'tx_mksearch_indexer_FAL', array('sys_file', 'sys_file_metadata'));
}

// seminars Extension
if (tx_rnbase_util_Extensions::isLoaded('seminars')) {
	tx_mksearch_util_Config::registerIndexer(
		'seminars',
		'seminar',
		'tx_mksearch_indexer_seminars_Seminar',
		array(
			//main table
			'tx_seminars_seminars',
			//tables with related data
			'tx_seminars_categories',
			'tx_seminars_organizers',
			'tx_seminars_sites',
			'tx_seminars_speakers',
			'tx_seminars_target_groups',
			'tx_seminars_timeslots',
		)
	);
}

// irfaq Extension
if (tx_rnbase_util_Extensions::isLoaded('irfaq')) {
	tx_mksearch_util_Config::registerIndexer(
		'irfaq',
		'question',
		'tx_mksearch_indexer_Irfaq',
		array(
			//main table
			'tx_irfaq_q',
			//tables with related data
			'tx_irfaq_expert',
			'tx_irfaq_cat',
		)
	);
}

if (tx_rnbase_util_Extensions::isLoaded('efaq')) {
	tx_mksearch_util_Config::registerIndexer('efaq', 'faq', 'tx_mksearch_indexer_Efaq', array('tx_efaq_faqs'));
}

// cal Extension
if (tx_rnbase_util_Extensions::isLoaded('cal')) {
	tx_mksearch_util_Config::registerIndexer(
		'cal',
		'event',
		'tx_mksearch_indexer_Cal',
		array(
			//main table
			'tx_cal_event',
			//tables with related data
			'tx_cal_category',
			'tx_cal_calendar',
		)
	);
}

// cal Extension
if (tx_rnbase_util_Extensions::isLoaded('a21glossary')) {
	tx_mksearch_util_Config::registerIndexer(
		'a21glossary',
		'main',
		'tx_mksearch_indexer_A21Glossary',
		array('tx_a21glossary_main')
	);
}


// Configure core page indexer service
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config']['core']['page']['indexedFields'] = array('subtitle', 'url', 'keywords', 'description', 'author', /*'author_email',*/ 'nav_title', 'alias', );

// Configure templavoila page indexer service. Simply re-use core page indexer service options
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config']['templavoila']['page'] =
	&$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config']['core']['page'];

// Configure templavoila tt_content indexer service. Simply re-use core tt_content indexer service options
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config']['templavoila']['tt_content'] =
	&$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config']['core']['tt_content'];

// Configure tt_news indexer service
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['config']['tt_news']['news']['indexedFields'] = array('imagealttext', 'imagetitletext', 'short', 'bodytext', 'keywords');