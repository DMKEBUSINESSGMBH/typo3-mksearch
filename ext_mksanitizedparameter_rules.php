<?php

defined('TYPO3') || exit('Access denied.');

$rulesForFrontend = [
    'mksearch' => [
        // wird in Solr verwendet und wird vor der eigentlichen Suche bereinigt.
        // Also hier nix machen.
        'term' => FILTER_UNSAFE_RAW,
        'fq' => [
            \DMK\MkSanitizedParameters\Rules::DEFAULT_RULES_KEY => FILTER_UNSAFE_RAW,
        ],
    ],
];

\DMK\MkSanitizedParameters\Rules::addRulesForFrontend($rulesForFrontend);
