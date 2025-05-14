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

/**
 * Diese Klasse ist für die Darstellung von Indexer tabellen im Backend verantwortlich.
 */
class tx_mksearch_mod1_decorator_Index
{
    /**
     * @param Sys25\RnBase\Backend\Module\IModule $mod
     */
    public function __construct(protected $mod)
    {
    }

    /**
     * @param string                           $value
     * @param string                           $colName
     * @param array                            $record
     * @param tx_mksearch_model_internal_Index $item
     */
    public function format($value, $colName, $record, $item)
    {
        $ret = '';
        switch ($colName) {
            case 'core':
                $ret = '';
                $ret .= tx_mksearch_mod1_util_IndexStatusHandler::getInstance()->handleRequest4Index($item);
                if (!empty($item->getProperty('description'))) {
                    $ret .= '<br /><pre>'.$item->getProperty('description').'</pre>';
                }

                break;
            case 'engine':
                $ret = match ($value) {
                    'zend_lucene' => $GLOBALS['LANG']->sL('LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices_engine_zendlucene'),
                    'solr' => $GLOBALS['LANG']->sL('LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices_engine_solr'),
                    'elasticsearch' => $GLOBALS['LANG']->sL('LLL:EXT:mksearch/Resources/Private/Language/locallang_db.xlf:tx_mksearch_indices_engine_elasticsearch'),
                    default => $value,
                };
                break;
            case 'composites':
                $composites = tx_mksearch_util_ServiceRegistry::getIntCompositeService()->getByIndex($item);
                /* @var $compositeDecorator tx_mksearch_mod1_decorator_Composite */
                $compositeDecorator = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_decorator_Composite', $this->mod);
                $ret = $compositeDecorator->getCompositeInfos($composites, ['includeConfig' => 1]);
                break;
            case 'actions':
                $formtool = $this->mod->getFormTool();
                // bearbeiten link
                $ret .= $formtool->createEditLink($item->getTableName(), $item->getUid(), '');
                // hide undhide link
                $ret .= $formtool->createHideLink($item->getTableName(), $item->getUid(), $item->getProperty('hidden'));
                // remove link
                $ret .= $formtool->createDeleteLink($item->getTableName(), $item->getUid(), '', ['confirm' => $GLOBALS['LANG']->sL('LLL:EXT:mksearch/Resources/Private/Language/BackendModule/locallang.xlf:confirmation_deletion')]);
                break;
            default:
                $ret = $value;
        }

        return $ret;
    }

    /**
     * @param array $items
     * @param array $options
     */
    public function getIndexInfos($items, $options = []): string
    {
        $ret = [];
        foreach ($items as $item) {
            $ret[] = $this->getIndexInfo($item, $options);
        }

        $ret = [] === $ret ? '###LABEL_NO_INDIZES###' : implode('</li><li class="hr"></li><li>', $ret);

        return '<ul><li>'.$ret.'</li></ul>';
    }

    /**
     * @param tx_mksearch_model_internal_Composite $item
     * @param array                                $options
     */
    public function getIndexInfo(tx_mksearch_model_internal_Index $item, $options = []): string
    {
        $formtool = $this->mod->getFormTool();

        $out = '';
        $out .= $formtool->createEditLink($item->getTableName(), $item->getUid(), '');
        $out .= $item->getTitle();

        // $out .= '<br />'; // @TODO: verbundene tabellen anhand von options ausgeben
        return '<div>'.$out.'</div>';
    }
}
