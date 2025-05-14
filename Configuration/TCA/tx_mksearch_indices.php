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
        'title' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'default_sortby' => 'ORDER BY title',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:mksearch/Resources/Public/Icons/icon_tx_mksearch_indices.gif',
        'requestUpdate' => 'engine',
    ],
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices.hidden',
            'config' => [
                'type' => 'check',
                'default' => '0',
            ],
        ],
        'title' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices.title',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'max' => '100',
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'description' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices.description',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '3',
            ],
        ],
        'name' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices.name',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'max' => '255',
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'composites' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices.composites',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_mksearch_configcomposites',
                'foreign_table_where' => ' AND tx_mksearch_configcomposites.pid=###CURRENT_PID### ORDER BY tx_mksearch_configcomposites.title',
                'MM' => 'tx_mksearch_indices_configcomposites_mm',
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
        'engine' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices_engine',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices_engine_zendlucene', 'value' => 'zend_lucene'],
                    ['label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices_engine_solr', 'value' => 'solr'],
                    ['label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices_engine_elasticsearch', 'value' => 'elasticsearch'],
                ],
                'default' => 'zend_lucene',
                'required' => true,
            ],
            'onChange' => 'reload',
        ],
        'solrversion' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices_solrversion',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices_solrversion_35', 'value' => 35],
                    ['label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices_solrversion_40', 'value' => 40],
                ],
                'default' => 35,
            ],
            'displayCond' => 'FIELD:engine:=:solr',
        ],
        'configuration' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indexerconfigs.configuration',
            'config' => [
                'type' => 'text',
                'cols' => '200',
                'rows' => '50',
                'default' => '# Insert the default config for this index.'.PHP_EOL.PHP_EOL.'default {'.PHP_EOL.'	# insert default configuration for indexers here.'.PHP_EOL.'	# core.tt_content.lang = 1'.PHP_EOL.'}',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'hidden, title, description, engine, solrversion, configuration, name, composites'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
