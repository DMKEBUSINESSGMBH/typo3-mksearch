<?php

declare(strict_types=1);

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
        'title' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_configcomposites',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'default_sortby' => 'ORDER BY title',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:mksearch/Resources/Public/Icons/icon_tx_mksearch_configcomposites.gif',
    ],
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_configcomposites.hidden',
            'config' => [
                'type' => 'check',
                'default' => '0',
            ],
        ],
        'title' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_configcomposites.title',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'max' => '100',
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'description' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_configcomposites.description',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ],
        ],
        'indices' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_configcomposites.indices',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_mksearch_indices',
                'foreign_table_where' => ' AND tx_mksearch_indices.pid=###CURRENT_PID### ORDER BY tx_mksearch_indices.title',
                'MM' => 'tx_mksearch_indices_configcomposites_mm',
                'MM_opposite_field' => 'composites',
                'size' => 20,
                'minitems' => 0,
                'maxitems' => 100,
                'fieldControl' => ['editPopup' => true, 'addRecord' => true],
                'wizards' => Sys25\RnBase\Backend\Utility\TcaTool::getWizards(
                    'tx_mksearch_indices',
                    ['add' => true, 'edit' => true, 'list' => true]
                ),
            ],
        ],
        'configs' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_configcomposites.configs',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_mksearch_indexerconfigs',
                'foreign_table_where' => ' AND tx_mksearch_indexerconfigs.pid=###CURRENT_PID### ORDER BY tx_mksearch_indexerconfigs.title',
                'MM' => 'tx_mksearch_configcomposites_indexerconfigs_mm',
                'size' => 20,
                'minitems' => 0,
                'maxitems' => 100,
                'fieldControl' => ['editPopup' => true, 'addRecord' => true],
                'wizards' => Sys25\RnBase\Backend\Utility\TcaTool::getWizards(
                    'tx_mksearch_indexerconfigs',
                    ['add' => true, 'edit' => true, 'list' => true]
                ),
            ],
        ],
        'configuration' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_configcomposites.configuration',
            'config' => [
                'type' => 'text',
                'cols' => '200',
                'rows' => '50',
                'default' => '### Insert the default indexer configuration here'.PHP_EOL.
                            '# extkey.contenttype {'.PHP_EOL.
                            '#     default config here.'.PHP_EOL.
                            '# }',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'hidden, title, description, indices, configs, configuration'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
