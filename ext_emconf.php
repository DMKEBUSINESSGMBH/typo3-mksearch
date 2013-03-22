<?php

########################################################################
# Extension Manager/Repository config file for ext: "mksearch"
#
# Auto generated 07-01-2010 15:54
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'MK Search',
	'description' => 'Generic highly adjustable and extendable search engine framework, using Zend Lucene or Apache Solr.',
	'category' => 'plugin',
	'author' => 'Michael Wagner, Hannes Bochmann, Rene Nitzsche',
	'author_email' => 'kontakt@das-medienkombinat.de',
	'shy' => '',
	'dependencies' => 'rn_base',
	'version' => '1.0.13',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author_company' => 'das MedienKombinat GmbH',
	'constraints' => array(
		'depends' => array(
			'rn_base' => '0.11.13',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
	'_md5_values_when_last_written' => 'a:74:{s:9:"ChangeLog";s:4:"74a1";s:10:"README.txt";s:4:"2e0b";s:21:"ext_conf_template.txt";s:4:"6b14";s:12:"ext_icon.gif";s:4:"1bdc";s:17:"ext_localconf.php";s:4:"fe23";s:14:"ext_tables.php";s:4:"ac53";s:14:"ext_tables.sql";s:4:"d41d";s:17:"flexform_main.xml";s:4:"25a5";s:13:"locallang.xml";s:4:"ea4d";s:16:"locallang_db.xml";s:4:"0b36";s:7:"tca.php";s:4:"9a2a";s:42:"action/class.tx_mksearch_action_Search.php";s:4:"23a7";s:21:"cli/configuration.php";s:4:"cb90";s:15:"cli/crawler.php";s:4:"1d87";s:28:"cli/config/configuration.php";s:4:"a4d2";s:31:"cli/config/configuration_de.php";s:4:"ea50";s:34:"cli/config/configuration_de_be.php";s:4:"a4ed";s:34:"cli/config/configuration_de_cz.php";s:4:"400a";s:34:"cli/config/configuration_de_de.php";s:4:"bcef";s:34:"cli/config/configuration_de_ee.php";s:4:"a558";s:34:"cli/config/configuration_de_pl.php";s:4:"9a70";s:31:"cli/config/configuration_en.php";s:4:"94f0";s:34:"cli/config/configuration_en_be.php";s:4:"d014";s:34:"cli/config/configuration_en_cz.php";s:4:"f9ab";s:34:"cli/config/configuration_en_ee.php";s:4:"b8d2";s:31:"cli/config/configuration_fr.php";s:4:"9dba";s:34:"cli/config/configuration_fr_be.php";s:4:"1c63";s:34:"cli/config/configuration_fr_cz.php";s:4:"725c";s:34:"cli/config/configuration_fr_de.php";s:4:"e987";s:31:"cli/config/configuration_pl.php";s:4:"7040";s:34:"cli/config/configuration_pl_be.php";s:4:"44bb";s:34:"cli/config/configuration_pl_pl.php";s:4:"9ec0";s:31:"cli/config/configuration_ru.php";s:4:"cae3";s:34:"cli/config/configuration_ru_be.php";s:4:"887f";s:34:"cli/config/configuration_ru_cz.php";s:4:"2d93";s:34:"cli/config/configuration_ru_ee.php";s:4:"506d";s:33:"cli/config/configuration_test.php";s:4:"dd93";s:22:"cli/config/general.php";s:4:"dbfc";s:19:"doc/wizard_form.dat";s:4:"64ca";s:20:"doc/wizard_form.html";s:4:"4a3d";s:51:"filter/class.tx_mksearch_filter_SearchByReferer.php";s:4:"ad4d";s:46:"filter/class.tx_mksearch_filter_SearchForm.php";s:4:"517a";s:50:"hooks/class.tx_mksearch_hooks_EngineZendLucene.php";s:4:"48a6";s:49:"interface/class.tx_mksearch_interface_Indexer.php";s:4:"7f00";s:54:"interface/class.tx_mksearch_interface_SearchEngine.php";s:4:"6846";s:44:"marker/class.tx_mksearch_marker_CorePage.php";s:4:"b0ee";s:49:"marker/class.tx_mksearch_marker_CoreTtContent.php";s:4:"544f";s:43:"marker/class.tx_mksearch_marker_General.php";s:4:"85de";s:42:"marker/class.tx_mksearch_marker_Search.php";s:4:"2dde";s:54:"marker/class.tx_mksearch_marker_SearchResultSimple.php";s:4:"cfc9";s:49:"model/class.tx_mksearch_model_IndexerDocument.php";s:4:"96e0";s:46:"model/class.tx_mksearch_model_IndexerField.php";s:4:"809f";s:43:"model/class.tx_mksearch_model_SearchHit.php";s:4:"6634";s:25:"service/ext_localconf.php";s:4:"7979";s:62:"service/engine/class.tx_mksearch_service_engine_ZendLucene.php";s:4:"ac02";s:66:"service/indexer/class.tx_mksearch_service_indexer_BaseDataBase.php";s:4:"a106";s:70:"service/indexer/class.tx_mksearch_service_indexer_BaseRnBaseSearch.php";s:4:"93d3";s:64:"service/indexer/class.tx_mksearch_service_indexer_TtNewsNews.php";s:4:"e63b";s:70:"service/indexer/core/class.tx_mksearch_service_indexer_core_Config.php";s:4:"aa47";s:68:"service/indexer/core/class.tx_mksearch_service_indexer_core_Page.php";s:4:"45b2";s:70:"service/indexer/core/class.tx_mksearch_service_indexer_core_PageTV.php";s:4:"cf4d";s:73:"service/indexer/core/class.tx_mksearch_service_indexer_core_TtContent.php";s:4:"f9f9";s:75:"service/indexer/core/class.tx_mksearch_service_indexer_core_TtContentTV.php";s:4:"4ac6";s:46:"static/static_extension_template/constants.txt";s:4:"d41d";s:42:"static/static_extension_template/setup.txt";s:4:"6a35";s:21:"templates/search.html";s:4:"333d";s:25:"templates/searchForm.html";s:4:"aa9e";s:33:"templates/searchResultSimple.html";s:4:"566c";s:38:"util/class.tx_mksearch_util_Config.php";s:4:"df62";s:36:"util/class.tx_mksearch_util_Misc.php";s:4:"309d";s:47:"util/class.tx_mksearch_util_ServiceRegistry.php";s:4:"4faa";s:36:"util/class.tx_mksearch_util_Tree.php";s:4:"0411";s:39:"util/class.tx_mksearch_util_Wizicon.php";s:4:"1b9f";s:38:"view/class.tx_mksearch_view_Search.php";s:4:"8c6c";}',
);

?>
