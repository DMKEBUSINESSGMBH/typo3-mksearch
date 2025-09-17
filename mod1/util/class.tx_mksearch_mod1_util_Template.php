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
 * Die Klasse stellt Auswahlmenus zur Verfügung.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_util_Template
{
    public static function parseBasics($template, Sys25\RnBase\Backend\Module\IModFunc $module)
    {
        $content = $template;
        $content = self::parseRootPage($content, $module);
        $content = self::handleAllowUrlFopenDeactivatedHint($content);

        // render commons
        $out = '';
        $out .= Sys25\RnBase\Frontend\Marker\Templates::getSubpart($content, '###COMMON_START###');
        $out .= $content;
        $out .= Sys25\RnBase\Frontend\Marker\Templates::getSubpart($content, '###COMMON_END###');

        // remove commons
        $out = Sys25\RnBase\Frontend\Marker\Templates::substituteSubpart($out, '###COMMON_START###', '');

        return Sys25\RnBase\Frontend\Marker\Templates::substituteSubpart($out, '###COMMON_END###', '');
    }

    private static function parseRootPage($template, Sys25\RnBase\Backend\Module\IModFunc $module)
    {
        $out = $template;

        // rootpage marker hinzufügen
        if (!Sys25\RnBase\Frontend\Marker\BaseMarker::containsMarker($out, 'ROOTPAGE_')) {
            return $out;
        }

        // Marker für Rootpage integrieren
        $rootPage = tx_mksearch_util_Indexer::getInstance()->getSiteRootPage($module->getPid());

        // keine rootpage, dann die erste seite im baum
        if (empty($rootPage)) {
            $rootPage = array_pop(tx_mksearch_util_Indexer::getInstance()->getRootlineByPid($module->getPid() ?: 0));
        }

        $rootPage = is_array($rootPage) ? Sys25\RnBase\Backend\Utility\BackendUtility::readPageAccess($rootPage['uid'], $GLOBALS['BE_USER']->getPagePermsClause(1)) : false;

        if (is_array($rootPage)) {
            // felder erzeugen
            $markerArr = [];
            foreach ($rootPage as $field => $value) {
                $markerArr['###ROOTPAGE_'.strtoupper((string) $field).'###'] = $value;
            }

            return Sys25\RnBase\Frontend\Marker\Templates::substituteMarkerArrayCached($out, $markerArr);
        }

        return Sys25\RnBase\Frontend\Marker\Templates::substituteSubpart($out, '###ROOTPAGE###', '<pre>No page selected.</pre>');
    }

    /**
     * @param string $template
     *
     * @return string
     */
    private static function handleAllowUrlFopenDeactivatedHint($template)
    {
        if (Sys25\RnBase\Frontend\Marker\BaseMarker::containsMarker($template, 'ALLOW_URL_FOPEN_DEACTIVATED_HINT')) {
            $allowUrlFopen = ini_get('allow_url_fopen');
            $useCurlAsHttpTransport =
                Sys25\RnBase\Configuration\Processor::getExtensionCfgValue('mksearch', 'useCurlAsHttpTransport');

            $markerArray = [];
            if (('' === $allowUrlFopen || '0' === $allowUrlFopen || false === $allowUrlFopen) && !$useCurlAsHttpTransport) {
                $markerArray['###ALLOW_URL_FOPEN_DEACTIVATED_HINT###'] = $GLOBALS['LANG']->sL('LLL:EXT:mksearch/Resources/Private/Language/BackendModule/locallang.xlf:allow_url_fopen_deactivated_hint');
            } else {
                $markerArray['###ALLOW_URL_FOPEN_DEACTIVATED_HINT###'] = '';
            }

            $template = Sys25\RnBase\Frontend\Marker\Templates::substituteMarkerArrayCached($template, $markerArray);
        }

        return $template;
    }

    /**
     * @param string                                 $template
     * @param Sys25\RnBase\Backend\Module\IModule    $mod
     * @param tx_mksearch_mod1_searcher_abstractBase $searcher
     *
     * @return string
     */
    public static function parseList($template, $mod, ?array &$markerArray, $searcher, string $marker)
    {
        $formTool = $mod->getFormTool();

        // die tabelle von der suchklasse besorgen (für die buttons)
        $table = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            $searcher->getService()->getSearcher()->getWrapperClass(), []
        )->getTableName();

        // Suchformular
        $markerArray['###'.$marker.'_SEARCHFORM###'] = $searcher->getSearchForm();
        // button für einen neuen Eintrag
        $markerArray['###BUTTON_'.$marker.'_NEW###'] = $formTool->createNewLink(
            $table,
            $mod->getPid(),
            $formTool->getLanguageService()->getLL('label_add_'.strtolower($marker))
        );
        // ergebnisliste und pager
        $data = $searcher->getResultList();
        $markerArray['###'.$marker.'_LIST###'] = $data['table'];
        $markerArray['###'.$marker.'_SIZE###'] = $data['totalsize'];
        $markerArray['###'.$marker.'_PAGER###'] = $data['pager'];

        return Sys25\RnBase\Frontend\Marker\Templates::substituteMarkerArrayCached($template, $markerArray);
    }

    /**
     * Setzt das Table Layout.
     * Im moment wird nur width bearbeidet.
     *
     * @return columns
     */
    public static function getTableLayout(array $columns, Sys25\RnBase\Backend\Module\IModule $mod)
    {
        $aAllowed = ['width'];
        // default tablelayout of doc
        $aTableLayout = $mod->getDoc()->tableLayout; // typo3/template.php
        $iCol = 0;
        foreach ($columns as $column) {
            $aAddParams = [];
            foreach ($aAllowed as $sAllowed) {
                if (isset($column[$sAllowed])) {
                    $aAddParams[] = $sAllowed.'="'.intval($column[$sAllowed]).'%"';
                }
            }

            $aTableLayout[0][$iCol] = ['<td '.implode(' ', $aAddParams).'>', '</td>'];
            ++$iCol;
        }

        return $aTableLayout;
    }
}
