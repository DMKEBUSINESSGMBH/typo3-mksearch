<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Lars Heber <dev@dmk-ebusiness.de>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

tx_rnbase::load('tx_rnbase_util_Network');
tx_rnbase::load('tx_rnbase_mod_BaseModFunc');

/**
 * Mksearch backend module.
 */
class tx_mksearch_mod1_ConfigIndizesDbList extends tx_rnbase_mod_BaseModFunc
{
    /**
     * Return function id (used in page typoscript etc.).
     *
     * @return string
     */
    protected function getFuncId()
    {
        return 'configindizesdblist';
    }

    /**
     * Return the actual html content.
     *
     * Actually, just the list view of the defined storage folder
     * is displayed within an iframe.
     *
     * @param string                    $template
     * @param tx_rnbase_configurations  $configurations
     * @param tx_rnbase_util_FormatUtil $formatter
     * @param tx_rnbase_util_FormTool   $formTool
     *
     * @return string
     */
    protected function getContent($template, &$configurations, &$formatter, $formTool)
    {
        $data = array();
        $storagePid = $this->getModule()->id;
        if ($storagePid) {
            $data['showerror'] = 0;
            $data['path'] = tx_rnbase_util_Network::locationHeaderUrl('/'.TYPO3_mainDir).'db_list.php?id='.$storagePid;
        } else {
            $data['showerror'] = 1;
            $data['path'] = 'about:blank';
        }

        $markerArray = $formatter->getItemMarkerArrayWrapped($data, $this->getConfId(), 0, '');

        return tx_rnbase_util_Templates::substituteMarkerArrayCached($template, $markerArray);
    }
}
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/class.tx_mksearch_mod1_ConfigIndizesDbList.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/class.tx_mksearch_mod1_ConfigIndizesDbList.php'];
}
