<?php

/**
 * Die Klasse stellt Auswahlmenus zur Verfügung.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_util_Template
{
    public static function parseBasics($template, \Sys25\RnBase\Backend\Module\IModFunc $module)
    {
        $content = $template;
        $content = self::parseRootPage($content, $module);
        $content = self::handleAllowUrlFopenDeactivatedHint($content);

        // render commons
        $out = '';
        $out .= \Sys25\RnBase\Frontend\Marker\Templates::getSubpart($content, '###COMMON_START###');
        $out .= $content;
        $out .= \Sys25\RnBase\Frontend\Marker\Templates::getSubpart($content, '###COMMON_END###');

        // remove commons
        $out = \Sys25\RnBase\Frontend\Marker\Templates::substituteSubpart($out, '###COMMON_START###', '');
        $out = \Sys25\RnBase\Frontend\Marker\Templates::substituteSubpart($out, '###COMMON_END###', '');

        return $out;
    }

    private static function parseRootPage($template, \Sys25\RnBase\Backend\Module\IModFunc $module)
    {
        $out = $template;

        // rootpage marker hinzufügen
        if (!\Sys25\RnBase\Frontend\Marker\BaseMarker::containsMarker($out, 'ROOTPAGE_')) {
            return $out;
        }

        // Marker für Rootpage integrieren
        $rootPage = tx_mksearch_util_Indexer::getInstance()->getSiteRootPage($module->getPid());

        // keine rootpage, dann die erste seite im baum
        if (empty($rootPage)) {
            $rootPage = array_pop(tx_mksearch_util_Indexer::getInstance()->getRootlineByPid($module->getPid() ? $module->getPid() : 0));
        }

        $rootPage = is_array($rootPage) ? \Sys25\RnBase\Backend\Utility\BackendUtility::readPageAccess($rootPage['uid'], $GLOBALS['BE_USER']->getPagePermsClause(1)) : false;

        if (is_array($rootPage)) {
            // felder erzeugen
            foreach ($rootPage as $field => $value) {
                $markerArr['###ROOTPAGE_'.strtoupper($field).'###'] = $value;
            }

            $out = \Sys25\RnBase\Frontend\Marker\Templates::substituteMarkerArrayCached($out, $markerArr);
        } else {
            $out = \Sys25\RnBase\Frontend\Marker\Templates::substituteSubpart($out, '###ROOTPAGE###', '<pre>No page selected.</pre>');
        }

        return $out;
    }

    /**
     * @param string $template
     *
     * @return string
     */
    private static function handleAllowUrlFopenDeactivatedHint($template)
    {
        if (\Sys25\RnBase\Frontend\Marker\BaseMarker::containsMarker($template, 'ALLOW_URL_FOPEN_DEACTIVATED_HINT')) {
            $allowUrlFopen = ini_get('allow_url_fopen');
            $useCurlAsHttpTransport =
                \Sys25\RnBase\Configuration\Processor::getExtensionCfgValue('mksearch', 'useCurlAsHttpTransport');

            $markerArray = [];
            if (!$allowUrlFopen && !$useCurlAsHttpTransport) {
                $markerArray['###ALLOW_URL_FOPEN_DEACTIVATED_HINT###'] = $GLOBALS['LANG']->getLL('allow_url_fopen_deactivated_hint');
            } else {
                $markerArray['###ALLOW_URL_FOPEN_DEACTIVATED_HINT###'] = '';
            }

            $template = \Sys25\RnBase\Frontend\Marker\Templates::substituteMarkerArrayCached($template, $markerArray);
        }

        return $template;
    }

    /**
     * @param string                                 $template
     * @param \Sys25\RnBase\Backend\Module\IModule                  $mod
     * @param array                                  $markerArray
     * @param tx_mksearch_mod1_searcher_abstractBase $searcher
     * @param string                                 $marker
     *
     * @return string
     */
    public static function parseList($template, $mod, &$markerArray, $searcher, $marker)
    {
        $formTool = $mod->getFormTool();

        // die tabelle von der suchklasse besorgen (für die buttons)
        $table = $searcher->getService()->getSearcher()->getBaseTable();

        // Suchformular
        $markerArray['###'.$marker.'_SEARCHFORM###'] = $searcher->getSearchForm();
        // button für einen neuen Eintrag
        $markerArray['###BUTTON_'.$marker.'_NEW###'] = $formTool->createNewLink(
            $table,
            $mod->id,
            $GLOBALS['LANG']->getLL('label_add_'.strtolower($marker))
        );
        // ergebnisliste und pager
        $data = $searcher->getResultList();
        $markerArray['###'.$marker.'_LIST###'] = $data['table'];
        $markerArray['###'.$marker.'_SIZE###'] = $data['totalsize'];
        $markerArray['###'.$marker.'_PAGER###'] = $data['pager'];
        $out = \Sys25\RnBase\Frontend\Marker\Templates::substituteMarkerArrayCached($template, $markerArray);

        return $out;
    }

    /**
     * Setzt das Table Layout.
     * Im moment wird nur width bearbeidet.
     *
     * @param array                 $columns
     * @param \Sys25\RnBase\Backend\Module\IModule $mod
     *
     * @return columns
     */
    public static function getTableLayout(array $columns, \Sys25\RnBase\Backend\Module\IModule $mod)
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

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/util/class.tx_mksearch_mod1_util_Template.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/util/class.tx_mksearch_mod1_util_Template.php'];
}
