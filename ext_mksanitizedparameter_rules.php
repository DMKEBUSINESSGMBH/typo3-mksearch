<?php

defined('TYPO3_MODE') || die('Access denied.');

// $rulesForFrontend = array(
//     'mksearch'    => array(
//        // wird in Solr verwendet und wird vor der eigentlichen Suche bereinigt.
//        // Also hier nix machen.
//        'term' => FILTER_UNSAFE_RAW,
//     ),
// );

$rulesForFrontend =
    unserialize('a:1:{s:8:"mksearch";a:1:{s:4:"term";i:516;}}');

tx_mksanitizedparameters_Rules::addRulesForFrontend($rulesForFrontend);
