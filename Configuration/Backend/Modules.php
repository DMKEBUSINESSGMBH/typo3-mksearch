<?php

return [
    'web_MksearchM1' => [
        'parent' => 'web',
        'position' => ['bottom'],
        'access' => 'user,group',
        'workspaces' => 'live',
        'path' => '/module/web/mksearch',
        'icon' => 'EXT:mksearch/Resources/Public/Icons/Extension.svg',
        'labels' => 'LLL:EXT:mksearch/Resources/Private/Language/BackendModule/locallang_mod.xlf',
        'extensionName' => 'Mksearch',
    ],
    'web_MksearchM1_config_indices' => [
        'parent' => 'web_MksearchM1',
        'access' => 'user,group',
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
        'access' => 'user,group',
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
        'access' => 'user,group',
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
        'access' => 'user,group',
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
