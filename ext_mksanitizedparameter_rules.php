<?php
defined('TYPO3_MODE') || die('Access denied.');

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase.php');
tx_rnbase::load('tx_mksanitizedparameters_Rules');

// $rulesForFrontend = array(
// 	'mksearch'	=> array(
//		// wird in Solr verwendet und wird vor der eigentlichen Suche bereinigt. 
//		// Also hier nix machen.
// 		'term' => FILTER_UNSAFE_RAW,
// 	),
// );
 
$rulesForFrontend = 
	unserialize('a:1:{s:8:"mksearch";a:1:{s:4:"term";i:516;}}');

tx_mksanitizedparameters_Rules::addRulesForFrontend($rulesForFrontend);
