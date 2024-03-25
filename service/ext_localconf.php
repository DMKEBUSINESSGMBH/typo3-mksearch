<?php

if (!defined('TYPO3')) {
    exit('Access denied.');
}
// Search engine and indexer services
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
    'mksearch',
    'mksearch_engine' /* sv type */ ,
    'tx_mksearch_service_engine_ZendLucene' /* sv key */ ,
    [
        'title' => 'Search engine Zend Lucene',
        'description' => 'Service which provides access to ZEND Lucene search engine',
        'subtype' => 'zend_lucene',
        'available' => true,
        'priority' => 50,
        'quality' => 50,
        'os' => '',
        'exec' => '',
        'classFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mksearch').'service/engine/class.tx_mksearch_service_engine_ZendLucene.php',
        'className' => 'tx_mksearch_service_engine_ZendLucene',
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
    'mksearch',
    'mksearch_engine' /* sv type */ ,
    'tx_mksearch_service_engine_Solr' /* sv key */ ,
    [
        'title' => 'Search engine Solr',
        'description' => 'Service which provides access to Apache SOLR search engine',
        'subtype' => 'solr',
        'available' => true,
        'priority' => 50,
        'quality' => 50,
        'os' => '',
        'exec' => '',
        'classFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mksearch').'service/engine/class.tx_mksearch_service_engine_Solr.php',
        'className' => 'tx_mksearch_service_engine_Solr',
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
    'mksearch',
    'mksearch_engine' /* sv type */ ,
    'tx_mksearch_service_engine_ElasticSearch' /* sv key */ ,
    [
        'title' => 'Search engine ElasticSearch',
        'description' => 'Service which provides access to ElasticSearch search engine',
        'subtype' => 'elasticsearch',
        'available' => true,
        'priority' => 50,
        'quality' => 50,
        'os' => '',
        'exec' => '',
        'classFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mksearch').'service/engine/class.tx_mksearch_service_engine_ElasticSearch.php',
        'className' => 'tx_mksearch_service_engine_ElasticSearch',
    ]
);

// Services for mksearch-internal use
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
    'mksearch',
    'mksearch',
    'tx_mksearch_indexer_internal_index',
    [
        'title' => 'Index',
        'description' => 'Service for indices',
        'subtype' => 'int_index',
        'available' => true,
        'priority' => 50,
        'quality' => 50,
        'os' => '',
        'exec' => '',
        'classFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mksearch').'service/internal/class.tx_mksearch_service_internal_Index.php',
        'className' => 'tx_mksearch_service_internal_Index',
    ]
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
    'mksearch',
    'mksearch',
    'tx_mksearch_indexer_internal_composite',
    [
        'title' => 'Indexer configuration composites',
        'description' => 'Service for indexer configuration composites',
        'subtype' => 'int_composite',
        'available' => true,
        'priority' => 50,
        'quality' => 50,
        'os' => '',
        'exec' => '',
        'classFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mksearch').'service/internal/class.tx_mksearch_service_internal_Composite.php',
        'className' => 'tx_mksearch_service_internal_Composite',
    ]
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
    'mksearch',
    'mksearch',
    'tx_mksearch_indexer_internal_config',
    [
        'title' => 'Indexer configuration',
        'description' => 'Service for indexer configurations',
        'subtype' => 'int_config',
        'available' => true,
        'priority' => 50,
        'quality' => 50,
        'os' => '',
        'exec' => '',
        'classFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mksearch').'service/internal/class.tx_mksearch_service_internal_Config.php',
        'className' => 'tx_mksearch_service_internal_Config',
    ]
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
    'mksearch',
    'mksearch',
    'tx_mksearch_service_internal_Keyword',
    [
        'title' => 'Keyword Service',
        'description' => 'Service for keywords',
        'subtype' => 'keyword',
        'available' => true,
        'priority' => 50,
        'quality' => 50,
        'os' => '',
        'exec' => '',
        'classFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mksearch').'service/internal/class.tx_mksearch_service_internal_Keyword.php',
        'className' => 'tx_mksearch_service_internal_Keyword',
    ]
);
