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

return [
    'web_MksearchM1' => [
        'parent' => 'web',
        'position' => ['bottom'],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/web/mksearch',
        'icon' => 'EXT:mksearch/Resources/Public/Icons/Extension.svg',
        'labels' => 'LLL:EXT:mksearch/Resources/Private/Language/BackendModule/locallang_mod.xlf',
        'extensionName' => 'Mksearch',
    ],
    'web_MksearchM1_config_indices' => [
        'parent' => 'web_MksearchM1',
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/web/mksearch/config_indices',
        'icon' => 'EXT:mksearch/Resources/Public/Icons/Extension.svg',
        'labels' => [
            'title' => 'LLL:EXT:mksearch/Resources/Private/Language/BackendModule/locallang.xlf:func_config_indizes',
        ],
        'routes' => [
            'pageNotSelected' => [
                'target' => 'tx_mksearch_mod1_Module',
            ],
            '_default' => [
                'target' => 'tx_mksearch_mod1_ConfigIndizes::main',
            ],
        ],
        'moduleData' => [
            'langFiles' => [],
            'pages' => '0',
            'depth' => 0,
        ],
    ],
    'web_MksearchM1_keywords' => [
        'parent' => 'web_MksearchM1',
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/web/mksearch/keywords',
        'icon' => 'EXT:mksearch/Resources/Public/Icons/Extension.svg',
        'labels' => [
            'title' => 'LLL:EXT:mksearch/Resources/Private/Language/BackendModule/locallang.xlf:func_keywords',
        ],
        'routes' => [
            'pageNotSelected' => [
                'target' => 'tx_mksearch_mod1_Module',
            ],
            '_default' => [
                'target' => 'tx_mksearch_mod1_Keywords::main',
            ],
        ],
        'moduleData' => [
            'langFiles' => [],
            'pages' => '0',
            'depth' => 0,
        ],
    ],
    'web_MksearchM1_indices' => [
        'parent' => 'web_MksearchM1',
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/web/mksearch/indices',
        'icon' => 'EXT:mksearch/Resources/Public/Icons/Extension.svg',
        'labels' => [
            'title' => 'LLL:EXT:mksearch/Resources/Private/Language/BackendModule/locallang.xlf:func_indize_indizes',
        ],
        'routes' => [
            'pageNotSelected' => [
                'target' => 'tx_mksearch_mod1_Module',
            ],
            '_default' => [
                'target' => 'tx_mksearch_mod1_IndizeIndizes::main',
            ],
        ],
        'moduleData' => [
            'langFiles' => [],
            'pages' => '0',
            'depth' => 0,
        ],
    ],
    'web_MksearchM1_solradmin' => [
        'parent' => 'web_MksearchM1',
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/web/mksearch/solradmin',
        'icon' => 'EXT:mksearch/Resources/Public/Icons/Extension.svg',
        'labels' => [
            'title' => 'LLL:EXT:mksearch/Resources/Private/Language/BackendModule/locallang.xlf:func_solradmin',
        ],
        'routes' => [
            'pageNotSelected' => [
                'target' => 'tx_mksearch_mod1_Module',
            ],
            '_default' => [
                'target' => 'tx_mksearch_mod1_SolrAdmin::main',
            ],
        ],
        'moduleData' => [
            'langFiles' => [],
            'pages' => '0',
            'depth' => 0,
        ],
    ],
];
