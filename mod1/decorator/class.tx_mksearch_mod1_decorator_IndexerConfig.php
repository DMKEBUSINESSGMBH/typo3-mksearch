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
class tx_mksearch_mod1_decorator_IndexerConfig
{
    /**
     * @param Sys25\RnBase\Backend\Module\IModule $mod
     */
    public function __construct(protected $mod)
    {
    }

    /**
     * @param string                            $colName
     * @param array                             $record
     * @param tx_mksearch_model_internal_Config $item
     */
    public function format(string $value, $colName, $record, $item)
    {
        switch ($colName) {
            case 'title':
                $ret = '';
                $ret .= $value;
                if (!empty($item->getProperty('description'))) {
                    $ret .= '<br /><pre>'.$item->getProperty('description').'</pre>';
                }

                break;
            case 'contenttype':
                $ret = $item->getExtkey().'.'.$item->getContenttype();
                break;
            case 'composites':
                $composites = tx_mksearch_util_ServiceRegistry::getIntCompositeService()->getByConfiguration($item);
                /* @var $compositeDecorator tx_mksearch_mod1_decorator_Composite */
                $compositeDecorator = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_decorator_Composite', $this->mod);
                $ret = $compositeDecorator->getCompositeInfos($composites, ['includeIndex' => 1]);
                break;
            case 'actions':
                $formtool = $this->mod->getFormTool();
                $ret = '';
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
    public function getConfigInfos($items, $options = []): string
    {
        $ret = [];
        foreach ($items as $item) {
            $ret[] = $this->getConfigInfo($item, $options);
        }

        $ret = [] === $ret ? '###LABEL_NO_INDEXERCONFIGURATIONS###' : implode('</li><li class="hr"></li><li>', $ret);

        return '<ul><li>'.$ret.'</li></ul>';
    }

    /**
     * @param tx_mksearch_model_internal_Composite $item
     * @param array                                $options
     */
    public function getConfigInfo(tx_mksearch_model_internal_Config $item, $options = []): string
    {
        $formtool = $this->mod->getFormTool();

        $out = '';
        $out .= $formtool->createEditLink($item->getTableName(), $item->getUid(), '');
        $out .= $item->getTitle();

        // $out .= '<br />'; // @TODO: in indices und configs wahlweise mit ausgeben
        return '<div>'.$out.'</div>';
    }
}
