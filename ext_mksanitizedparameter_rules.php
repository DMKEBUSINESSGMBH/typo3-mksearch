<?php

defined('TYPO3_MODE') || die('Access denied.');

 $rulesForFrontend = [
     'mksearch' => [
        // wird in Solr verwendet und wird vor der eigentlichen Suche bereinigt.
        // Also hier nix machen.
        'term' => FILTER_UNSAFE_RAW,
         'fq' => [
             tx_mksanitizedparameters_Rules::DEFAULT_RULES_KEY => FILTER_UNSAFE_RAW,
         ],
     ],
 ];

tx_mksanitizedparameters_Rules::addRulesForFrontend($rulesForFrontend);
