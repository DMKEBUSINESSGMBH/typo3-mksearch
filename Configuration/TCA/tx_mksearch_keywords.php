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
    'ctrl' => [
        'title' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_keywords',
        'label' => 'keyword',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'default_sortby' => 'ORDER BY keyword',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:mksearch/Resources/Public/Icons/tx_mksearch_keywords.gif',
    ],
    'feInterface' => [
        'fe_admin_fieldList' => 'hidden, keyword, link',
    ],
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => '0',
            ],
        ],
        'keyword' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_keywords_keyword',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'required' => true,
            ],
        ],
        'link' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_keywords_link',
            'config' => [
                'type' => 'link',
                'size' => '15',
                'checkbox' => '',
                'wizards' => Sys25\RnBase\Backend\Utility\TcaTool::getWizards(
                    '',
                    ['link' => true]
                ),
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'hidden, keyword, link'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
