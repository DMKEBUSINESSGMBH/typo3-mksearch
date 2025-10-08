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
class tx_mksearch_mod1_decorator_Composite
{
    /**
     * @param Sys25\RnBase\Backend\Module\IModule $mod
     */
    public function __construct(protected $mod)
    {
    }

    /**
     * @param string $colName
     * @param array  $record
     * @param array  $item
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
            case 'indices':
                $indizes = tx_mksearch_util_ServiceRegistry::getIntIndexService()->getByComposite($item);
                /* @var $compositeDecorator tx_mksearch_mod1_decorator_Index */
                $indizesDecorator = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_decorator_Index', $this->mod);
                $ret = $indizesDecorator->getIndexInfos($indizes);
                break;
            case 'configs':
                $configs = tx_mksearch_util_ServiceRegistry::getIntConfigService()->getByComposite($item);
                /* @var $compositeDecorator tx_mksearch_mod1_decorator_IndexerConfig */
                $configsDecorator = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_decorator_IndexerConfig', $this->mod);
                $ret = $configsDecorator->getConfigInfos($configs);
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
     */
    public function getCompositeInfos($items, array $options = []): string
    {
        $ret = [];
        foreach ($items as $item) {
            $ret[] = $this->getCompositeInfo($item, $options);
        }

        $ret = [] === $ret ? '###LABEL_NO_COMPOSITES###' : implode('</li><li class="hr"></li><li>', $ret);

        return '<ul><li>'.$ret.'</li></ul>';
    }

    public function getCompositeInfo(tx_mksearch_model_internal_Composite $item, array $options = []): string
    {
        $formtool = $this->mod->getFormTool();

        $out = '';
        $out .= $formtool->createEditLink($item->getTableName(), $item->getUid(), '');
        $out .= $item->getTitle();
        if ($options['includeIndex'] ?? false) {
            $indizes = tx_mksearch_util_ServiceRegistry::getIntIndexService()->getByComposite($item);
            /* @var $compositeDecorator tx_mksearch_mod1_decorator_Index */
            $indizesDecorator = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_decorator_Index', $this->mod);
            $out .= '<div><strong>###LABEL_TABLEHEADER_INDICES###:</strong></div>';
            $out .= '<div class="mkindent">';
            $out .= $indizesDecorator->getIndexInfos($indizes);
            $out .= '</div>';
        }

        if ($options['includeConfig'] ?? false) {
            $configs = tx_mksearch_util_ServiceRegistry::getIntConfigService()->getByComposite($item);
            /* @var $compositeDecorator tx_mksearch_mod1_decorator_IndexerConfig */
            $configDecorator = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_decorator_IndexerConfig', $this->mod);
            $out .= '<div ><strong>###LABEL_TABLEHEADER_CONFIGS###:</strong></div>';
            $out .= '<div class="mkindent">';
            $out .= $configDecorator->getConfigInfos($configs);
            $out .= '</div>';
        }

        // $out .= '<br />'; // @TODO: in indices und configs wahlweise mit ausgeben
        return '<div>'.$out.'</div>';
    }
}
