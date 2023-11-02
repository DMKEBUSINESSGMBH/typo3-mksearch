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
        'routes' => [
            '_default' => [
                'target' => 'tx_mksearch_mod1_Module',
            ],
        ],
    ],
];
