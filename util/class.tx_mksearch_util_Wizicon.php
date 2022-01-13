<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2012 DMK E-Business GmbH
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
 * tx_mksearch_util_Wizicon.
 *
 * @author         Eric Hertwig <dev@dmk-ebusiness.de>
 * @license        http://www.gnu.org/licenses/lgpl.html
 *                 GNU Lesser General Public License, version 3 or later
 */
class tx_mksearch_util_Wizicon extends \Sys25\RnBase\Utility\WizIcon
{
    /**
     * @return array
     */
    protected function getPluginData()
    {
        return [
            'tx_mksearch' => [
                'icon' => /*\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('mksearch') .*/ 'EXT:mksearch/ext_icon.gif',
                'title' => 'plugin.mksearch.label',
                'description' => 'plugin.mksearch.description',
            ],
        ];
    }

    /**
     * @return string
     */
    protected function getLLFile()
    {
        return \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('mksearch').'locallang_db.xml';
    }
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_Wizicon.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/util/class.tx_mksearch_util_Wizicon.php'];
}
