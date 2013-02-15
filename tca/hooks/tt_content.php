<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

// Extend table tt_news
t3lib_div::loadTCA('tt_content');
tx_rnbase::load('tx_mksearch_indexer_ttcontent_Normal');

$tempTtContent = array(
	'tx_mksearch_is_indexable' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:mksearch/locallang_db.xml:tt_content.tx_mksearch_is_indexable',
		'config' => array (
			'type'	=> 'select',
			'items' => array(
    			array(
    				'LLL:EXT:mksearch/locallang_db.xml:tt_content.tx_mksearch_is_indexable.use_indexer_config', 
    				tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION
    			),
    			array(
    				'LLL:EXT:mksearch/locallang_db.xml:tt_content.tx_mksearch_is_indexable.yes', 
    				tx_mksearch_indexer_ttcontent_Normal::IS_INDEXABLE
    			),
    			array(
    				'LLL:EXT:mksearch/locallang_db.xml:tt_content.tx_mksearch_is_indexable.no', 
    				tx_mksearch_indexer_ttcontent_Normal::IS_NOT_INDEXABLE
    			),
			),
			'default' => tx_mksearch_indexer_ttcontent_Normal::USE_INDEXER_CONFIGURATION
		)
	),
);

t3lib_extMgm::addTCAcolumns('tt_content',$tempTtContent,1);
t3lib_extMgm::addToAllTCAtypes('tt_content', 'tx_mksearch_is_indexable');