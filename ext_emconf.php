<?php

/*
 * Copyright notice
 *
 * (c) DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This file is part of the "mksearch" Extension for TYPO3 CMS.
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GNU Lesser General Public License can be found at
 * www.gnu.org/licenses/lgpl.html
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

$EM_CONF['mksearch'] = [
    'title' => 'MK Search',
    'description' => 'Generic highly adjustable and extendable search engine framework, using Zend Lucene, Apache Solr or ElasticSearch. But support for other search engines can be provided easily.',
    'category' => 'plugin',
    'author' => 'Michael Wagner, Hannes Bochmann, Rene Nitzsche',
    'author_email' => 'dev@dmk-ebusiness.de',
    'version' => '12.0.12',
    'state' => 'stable',
    'author_company' => 'DMK E-Business GmbH',
    'constraints' => [
        'depends' => [
            'rn_base' => '',
            'typo3' => '12.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [
            'mksanitizedparameters' => '',
        ],
    ],
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
