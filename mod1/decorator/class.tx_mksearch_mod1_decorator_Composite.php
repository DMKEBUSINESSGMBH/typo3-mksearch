<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Michael Wagner <dev@dmk-ebusiness.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Diese Klasse ist für die Darstellung von Indexer tabellen im Backend verantwortlich.
 */
class tx_mksearch_mod1_decorator_Composite
{
    public function __construct($mod)
    {
        $this->mod = $mod;
    }

    /**
     * Returns the module.
     *
     * @return \Sys25\RnBase\Backend\Module\IModule
     */
    private function getModule()
    {
        return $this->mod;
    }

    /**
     * @param string $value
     * @param string $colName
     * @param array  $record
     * @param array  $item
     */
    public function format($value, $colName, $record, $item)
    {
        switch ($colName) {
            case 'title':
                $ret = '';
                $ret .= $value;
                if (!empty($record->getProperty('description'))) {
                    $ret .= '<br /><pre>'.$record->getProperty('description').'</pre>';
                }
                break;
            case 'indices':
                $indizes = tx_mksearch_util_ServiceRegistry::getIntIndexService()->getByComposite($item);
                /* @var $compositeDecorator tx_mksearch_mod1_decorator_Index */
                $indizesDecorator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_decorator_Index', $this->getModule());
                $ret = $indizesDecorator->getIndexInfos($indizes);
                break;
            case 'configs':
                $configs = tx_mksearch_util_ServiceRegistry::getIntConfigService()->getByComposite($item);
                /* @var $compositeDecorator tx_mksearch_mod1_decorator_IndexerConfig */
                $configsDecorator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_decorator_IndexerConfig', $this->getModule());
                $ret = $configsDecorator->getConfigInfos($configs);
                break;
            case 'actions':
                $formtool = $this->getModule()->getFormTool();
                $ret = '';
                // bearbeiten link
                $ret .= $formtool->createEditLink($item->getTableName(), $item->getUid(), '');
                // hide undhide link
                $ret .= $formtool->createHideLink($item->getTableName(), $item->getUid(), $item->getProperty('hidden'));
                // remove link
                $ret .= $formtool->createDeleteLink($item->getTableName(), $item->getUid(), '', ['confirm' => $GLOBALS['LANG']->getLL('confirmation_deletion')]);
                break;
            default:
                $ret = $value;
        }

        return $ret;
    }

    /**
     * @param array $items
     * @param array $options
     *
     * @return string
     */
    public function getCompositeInfos($items, $options = [])
    {
        foreach ($items as $item) {
            $ret[] = $this->getCompositeInfo($item, $options);
        }
        $ret = empty($ret) ? '###LABEL_NO_COMPOSITES###' : implode('</li><li class="hr"></li><li>', $ret);

        return '<ul><li>'.$ret.'</li></ul>';
    }

    /**
     * @param tx_mksearch_model_internal_Composite $item
     * @param array                                $options
     *
     * @return string
     */
    public function getCompositeInfo(tx_mksearch_model_internal_Composite $item, $options = [])
    {
        $formtool = $this->getModule()->getFormTool();

        $out = '';
        $out .= $formtool->createEditLink($item->getTableName(), $item->getUid(), '');
        $out .= $item->getTitle();
        if ($options['includeIndex']) {
            $indizes = tx_mksearch_util_ServiceRegistry::getIntIndexService()->getByComposite($item);
            /* @var $compositeDecorator tx_mksearch_mod1_decorator_Index */
            $indizesDecorator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_decorator_Index', $this->getModule());
            $out .= '<div><strong>###LABEL_TABLEHEADER_INDICES###:</strong></div>';
            $out .= '<div class="mkindent">';
            $out .= $indizesDecorator->getIndexInfos($indizes);
            $out .= '</div>';
        }
        if ($options['includeConfig']) {
            $configs = tx_mksearch_util_ServiceRegistry::getIntConfigService()->getByComposite($item);
            /* @var $compositeDecorator tx_mksearch_mod1_decorator_IndexerConfig */
            $configDecorator = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_decorator_IndexerConfig', $this->getModule());
            $out .= '<div ><strong>###LABEL_TABLEHEADER_CONFIGS###:</strong></div>';
            $out .= '<div class="mkindent">';
            $out .= $configDecorator->getConfigInfos($configs);
            $out .= '</div>';
        }

        // $out .= '<br />'; // @TODO: in indices und configs wahlweise mit ausgeben
        return '<div>'.$out.'</div>';
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/decorator/class.tx_mksearch_mod1_decorator_Composite.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/decorator/class.tx_mksearch_mod1_decorator_Composite.php'];
}
