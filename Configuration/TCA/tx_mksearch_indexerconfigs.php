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
        'title' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indexerconfigs',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'default_sortby' => 'ORDER BY title',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'requestUpdate' => 'extkey,contenttype',
        'iconfile' => 'EXT:mksearch/Resources/Public/Icons/icon_tx_mksearch_indexconfigs.gif',
    ],
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indexerconfigs.hidden',
            'config' => [
                'type' => 'check',
                'default' => '0',
            ],
        ],
        'title' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indexerconfigs.title',
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
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indexerconfigs.description',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ],
        ],
        'extkey' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indexerconfigs.extkey',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [['label' => '', 'value' => '']],
                'itemsProcFunc' => 'tx_mksearch_util_TCA->getIndexerExtKeys',
                'size' => '1',
                'maxitems' => '1',
            ],
            'onChange' => 'reload',
        ],
        'contenttype' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indexerconfigs.contenttype',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [['label' => '', 'value' => '']],
                'itemsProcFunc' => 'tx_mksearch_util_TCA->getIndexerContentTypes',
                'size' => '1',
                'maxitems' => '1',
                'eval' => 'trim',
                'required' => true,
            ],
            'onChange' => 'reload',
        ],
        'configuration' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indexerconfigs.configuration',
            'config' => [
                'type' => 'text',
                'cols' => '200',
                'rows' => '50',
                // @see \DMK\Mksearch\Backend\Form\Element\IndexerConfigurationField
                'renderType' => 'indexerConfigurationField',
            ],
        ],
        'composites' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indexerconfigs.composites',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_mksearch_configcomposites',
                'foreign_table_where' => ' AND tx_mksearch_configcomposites.pid=###CURRENT_PID### ORDER BY tx_mksearch_configcomposites.title',
                'MM' => 'tx_mksearch_configcomposites_indexerconfigs_mm',
                'MM_opposite_field' => 'configs',
                'size' => 20,
                'minitems' => 0,
                'maxitems' => 100,
                'fieldControl' => ['editPopup' => true, 'addRecord' => true],
                'wizards' => Sys25\RnBase\Backend\Utility\TcaTool::getWizards(
                    'tx_mksearch_configcomposites',
                    ['add' => true, 'edit' => true, 'list' => true]
                ),
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'hidden, title, description, extkey, contenttype, configuration, composites'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
