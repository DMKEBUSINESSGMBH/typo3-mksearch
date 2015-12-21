<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}
// Search engine and indexer services
tx_rnbase_util_Extensions::addService($_EXTKEY,  'mksearch_engine' /* sv type */,  'tx_mksearch_service_engine_ZendLucene' /* sv key */,
	array(
		'title' => 'Search engine Zend Lucene',
		'description' => 'Service which provides access to ZEND Lucene search engine',
		'subtype' => 'zend_lucene',
		'available' => TRUE,
		'priority' => 50,
		'quality' => 50,
		'os' => '',
		'exec' => '',
		'classFile' => tx_rnbase_util_Extensions::extPath($_EXTKEY).'service/engine/class.tx_mksearch_service_engine_ZendLucene.php',
		'className' => 'tx_mksearch_service_engine_ZendLucene',
	)
);

tx_rnbase_util_Extensions::addService($_EXTKEY,  'mksearch_engine' /* sv type */,  'tx_mksearch_service_engine_Solr' /* sv key */,
	array(
		'title' => 'Search engine Solr',
		'description' => 'Service which provides access to Apache SOLR search engine',
		'subtype' => 'solr',
		'available' => TRUE,
		'priority' => 50,
		'quality' => 50,
		'os' => '',
		'exec' => '',
		'classFile' => tx_rnbase_util_Extensions::extPath($_EXTKEY).'service/engine/class.tx_mksearch_service_engine_Solr.php',
		'className' => 'tx_mksearch_service_engine_Solr',
	)
);

tx_rnbase_util_Extensions::addService(
	$_EXTKEY,
	'mksearch_engine' /* sv type */,
	'tx_mksearch_service_engine_ElasticSearch' /* sv key */,
	array(
		'title' => 'Search engine ElasticSearch',
		'description' => 'Service which provides access to ElasticSearch search engine',
		'subtype' => 'elasticsearch',
		'available' => TRUE,
		'priority' => 50,
		'quality' => 50,
		'os' => '',
		'exec' => '',
		'classFile' => tx_rnbase_util_Extensions::extPath($_EXTKEY).'service/engine/class.tx_mksearch_service_engine_ElasticSearch.php',
		'className' => 'tx_mksearch_service_engine_ElasticSearch',
	)
);

// Services for mksearch-internal use
tx_rnbase_util_Extensions::addService($_EXTKEY, 'mksearch', 'tx_mksearch_indexer_internal_index',
		array(
			'title' => 'Index',
			'description' => 'Service for indices',
			'subtype' => 'int_index',
			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,
			'os' => '',
			'exec' => '',
			'classFile' => tx_rnbase_util_Extensions::extPath($_EXTKEY).'service/internal/class.tx_mksearch_service_internal_Index.php',
			'className' => 'tx_mksearch_service_internal_Index',
		)
	);
tx_rnbase_util_Extensions::addService($_EXTKEY, 'mksearch', 'tx_mksearch_indexer_internal_composite',
		array(
			'title' => 'Indexer configuration composites',
			'description' => 'Service for indexer configuration composites',
			'subtype' => 'int_composite',
			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,
			'os' => '',
			'exec' => '',
			'classFile' => tx_rnbase_util_Extensions::extPath($_EXTKEY).'service/internal/class.tx_mksearch_service_internal_Composite.php',
			'className' => 'tx_mksearch_service_internal_Composite',
		)
	);
tx_rnbase_util_Extensions::addService($_EXTKEY, 'mksearch', 'tx_mksearch_indexer_internal_config',
		array(
			'title' => 'Indexer configuration',
			'description' => 'Service for indexer configurations',
			'subtype' => 'int_config',
			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,
			'os' => '',
			'exec' => '',
			'classFile' => tx_rnbase_util_Extensions::extPath($_EXTKEY).'service/internal/class.tx_mksearch_service_internal_Config.php',
			'className' => 'tx_mksearch_service_internal_Config',
		)
	);
tx_rnbase_util_Extensions::addService($_EXTKEY, 'mksearch', 'tx_mksearch_service_internal_Keyword',
		array(
			'title' => 'Keyword Service',
			'description' => 'Service for keywords',
			'subtype' => 'keyword',
			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,
			'os' => '',
			'exec' => '',
			'classFile' => tx_rnbase_util_Extensions::extPath($_EXTKEY).'service/internal/class.tx_mksearch_service_internal_Keyword.php',
			'className' => 'tx_mksearch_service_internal_Keyword',
		)
	);

tx_rnbase_util_Extensions::addService($_EXTKEY, 'mksearch', 'tx_mksearch_service_irfaq_Expert',
		array(
			'title' => 'Irfaq Expert Service',
			'description' => 'Service for Irfaq Experts',
			'subtype' => 'irfaq_expert',
			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,
			'os' => '',
			'exec' => '',
			'classFile' => tx_rnbase_util_Extensions::extPath($_EXTKEY).'service/irfaq/class.tx_mksearch_service_irfaq_Expert.php',
			'className' => 'tx_mksearch_service_irfaq_Expert',
		)
	);

tx_rnbase_util_Extensions::addService($_EXTKEY, 'mksearch', 'tx_mksearch_service_irfaq_Category',
		array(
			'title' => 'Irfaq Category Service',
			'description' => 'Service for Irfaq Categories',
			'subtype' => 'irfaq_category',
			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,
			'os' => '',
			'exec' => '',
			'classFile' => tx_rnbase_util_Extensions::extPath($_EXTKEY).'service/irfaq/class.tx_mksearch_service_irfaq_Category.php',
			'className' => 'tx_mksearch_service_irfaq_Category',
		)
	);

tx_rnbase_util_Extensions::addService($_EXTKEY, 'mksearch', 'tx_mksearch_service_irfaq_Question',
		array(
			'title' => 'Irfaq Question Service',
			'description' => 'Service for Irfaq Questions',
			'subtype' => 'irfaq_question',
			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,
			'os' => '',
			'exec' => '',
			'classFile' => tx_rnbase_util_Extensions::extPath($_EXTKEY).'service/irfaq/class.tx_mksearch_service_irfaq_Question.php',
			'className' => 'tx_mksearch_service_irfaq_Question',
		)
	);