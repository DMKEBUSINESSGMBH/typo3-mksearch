<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "mksearch".
 *
 * Auto generated 09-09-2014 11:32
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/
$EM_CONF['mksearch'] = [
    'title' => 'MK Search',
    'description' => 'Generic highly adjustable and extendable search engine framework, using Zend Lucene, Apache Solr or ElasticSearch. But support for other search engines can be provided easily.',
    'category' => 'plugin',
    'author' => 'Michael Wagner, Hannes Bochmann, Rene Nitzsche',
    'author_email' => 'dev@dmk-ebusiness.de',
    'shy' => '',
    'dependencies' => 'rn_base',
    'version' => '11.0.7',
    'conflicts' => '',
    'priority' => '',
    'module' => '',
    'state' => 'stable',
    'internal' => '',
    'uploadfolder' => 0,
    'createDirs' => '',
    'modify_tables' => '',
    'clearCacheOnLoad' => 1,
    'lockType' => '',
    'author_company' => 'DMK E-Business GmbH',
    'constraints' => [
        'depends' => [
            'rn_base' => '1.15.0-',
            'typo3' => '10.4.25-11.5.99',
        ],
        'conflicts' => [],
        'suggests' => [
            'mksanitizedparameters' => '11.0.0-',
        ],
    ],
    'suggests' => [],
    'autoload' => [
        'classmap' => [
            'Classes/',
            'action/',
            'filter/',
            'hooks/',
            'indexer/',
            'interface/',
            'lib/',
            'marker/',
            'mod1/',
            'model/',
            'scheduler/',
            'search/',
            'service/',
            'tests/',
            'util/',
            'view/',
        ],
    ],
];
