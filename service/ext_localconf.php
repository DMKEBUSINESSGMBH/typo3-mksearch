<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}
// Search engine and indexer services
t3lib_extMgm::addService($_EXTKEY,  'mksearch_engine' /* sv type */,  'tx_mksearch_service_engine_ZendLucene' /* sv key */,
	array(
		'title' => 'Search engine Zend Lucene',
		'description' => 'Service which provides access to ZEND Lucene search engine',
		'subtype' => 'zend_lucene',
		'available' => TRUE,
		'priority' => 50,
		'quality' => 50,
		'os' => '',
		'exec' => '',
		'classFile' => t3lib_extMgm::extPath($_EXTKEY).'service/engine/class.tx_mksearch_service_engine_ZendLucene.php',
		'className' => 'tx_mksearch_service_engine_ZendLucene',
	)
);

// Search engine and indexer services
t3lib_extMgm::addService($_EXTKEY,  'mksearch_engine' /* sv type */,  'tx_mksearch_service_engine_Solr' /* sv key */,
	array(
		'title' => 'Search engine Solr',
		'description' => 'Service which provides access to Apache SOLR search engine',
		'subtype' => 'solr',
		'available' => TRUE,
		'priority' => 50,
		'quality' => 50,
		'os' => '',
		'exec' => '',
		'classFile' => t3lib_extMgm::extPath($_EXTKEY).'service/engine/class.tx_mksearch_service_engine_Solr.php',
		'className' => 'tx_mksearch_service_engine_Solr',
	)
);


// Activate indexer services

// Activate at most ONE of 'core.page' OR 'templavoila.page' indexer services!
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['active'][] = 'core.page';
//$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['active'][] = 'templavoila.page';
// Activate at most ONE of 'core.tt_content' OR 'templavoila.tt_content' indexer services!
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['active'][] = 'core.tt_content';
//$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['active'][] = 'templavoila.tt_content';

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['mksearch']['indexer']['active'][] = 'tt_news.news';

// Define table to content type mappings
require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
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
tx_mksearch_util_Config::registerIndexer('tt_news', 'news', 'tx_mksearch_indexer_TtNewsNews', array('tt_news'));
tx_mksearch_util_Config::registerIndexer('tt_address', 'address', 'tx_mksearch_indexer_TtAddressAddress', array('tt_address'));
tx_mksearch_util_Config::registerIndexer('dam', 'media', 'tx_mksearch_indexer_DamMedia', array('tx_dam'));

// seminars Extension
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

// irfaq Extension
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


// Services for mksearch-internal use
t3lib_extMgm::addService($_EXTKEY, 'mksearch', 'tx_mksearch_indexer_internal_index',
		array(
			'title' => 'Index',
			'description' => 'Service for indices',
			'subtype' => 'int_index',
			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,
			'os' => '',
			'exec' => '',
			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'service/internal/class.tx_mksearch_service_internal_Index.php',
			'className' => 'tx_mksearch_service_internal_Index',
		)
	);
t3lib_extMgm::addService($_EXTKEY, 'mksearch', 'tx_mksearch_indexer_internal_composite',
		array(
			'title' => 'Indexer configuration composites',
			'description' => 'Service for indexer configuration composites',
			'subtype' => 'int_composite',
			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,
			'os' => '',
			'exec' => '',
			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'service/internal/class.tx_mksearch_service_internal_Composite.php',
			'className' => 'tx_mksearch_service_internal_Composite',
		)
	);
t3lib_extMgm::addService($_EXTKEY, 'mksearch', 'tx_mksearch_indexer_internal_config',
		array(
			'title' => 'Indexer configuration',
			'description' => 'Service for indexer configurations',
			'subtype' => 'int_config',
			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,
			'os' => '',
			'exec' => '',
			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'service/internal/class.tx_mksearch_service_internal_Config.php',
			'className' => 'tx_mksearch_service_internal_Config',
		)
	);
t3lib_extMgm::addService($_EXTKEY, 'mksearch', 'tx_mksearch_service_internal_Keyword',
		array(
			'title' => 'Keyword Service',
			'description' => 'Service for keywords',
			'subtype' => 'keyword',
			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,
			'os' => '',
			'exec' => '',
			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'service/internal/class.tx_mksearch_service_internal_Keyword.php',
			'className' => 'tx_mksearch_service_internal_Keyword',
		)
	);

t3lib_extMgm::addService($_EXTKEY, 'mksearch', 'tx_mksearch_service_irfaq_Expert',
		array(
			'title' => 'Irfaq Expert Service',
			'description' => 'Service for Irfaq Experts',
			'subtype' => 'irfaq_expert',
			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,
			'os' => '',
			'exec' => '',
			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'service/irfaq/class.tx_mksearch_service_irfaq_Expert.php',
			'className' => 'tx_mksearch_service_irfaq_Expert',
		)
	);
	
t3lib_extMgm::addService($_EXTKEY, 'mksearch', 'tx_mksearch_service_irfaq_Category',
		array(
			'title' => 'Irfaq Category Service',
			'description' => 'Service for Irfaq Categories',
			'subtype' => 'irfaq_category',
			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,
			'os' => '',
			'exec' => '',
			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'service/irfaq/class.tx_mksearch_service_irfaq_Category.php',
			'className' => 'tx_mksearch_service_irfaq_Category',
		)
	);
	
t3lib_extMgm::addService($_EXTKEY, 'mksearch', 'tx_mksearch_service_irfaq_Question',
		array(
			'title' => 'Irfaq Question Service',
			'description' => 'Service for Irfaq Questions',
			'subtype' => 'irfaq_question',
			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,
			'os' => '',
			'exec' => '',
			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'service/irfaq/class.tx_mksearch_service_irfaq_Question.php',
			'className' => 'tx_mksearch_service_irfaq_Question',
		)
	);