<?php

namespace DMK\Mksearch\ViewHelpers;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */
/*
 * DMK\Mksearch\ViewHelpers\Format$HtmlViewHelper.
 *
 * nähere Infos in Configuration/XClasses.php
 *
 * @author          Hannes Bochmann <hannes.bochmann@dmk-ebusiness.de>
 * @license         http://www.gnu.org/licenses/lgpl.html
 *                  GNU Lesser General Public License, version 3 or later
 */
class CObjectViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\CObjectViewHelper
{
    /**
     * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager)
    {
        parent::injectConfigurationManager($configurationManager);

        if (
            \tx_mksearch_service_internal_Index::isIndexingInProgress()
            && !empty($GLOBALS['TSFE'])
            && is_object($GLOBALS['TSFE'])
            && is_object($GLOBALS['TSFE']->tmpl)
        ) {
            $this->typoScriptSetup = $GLOBALS['TSFE']->tmpl->setup;
        }
    }

    /**
     * nähere Infos in Configuration/XClasses.php.
     */
    protected static function simulateFrontendEnvironment()
    {
        if (!\tx_mksearch_service_internal_Index::isIndexingInProgress()) {
            parent::simulateFrontendEnvironment();
        }
    }

    /**
     * @see simulateFrontendEnvironment()
     */
    protected static function resetFrontendEnvironment()
    {
        if (!\tx_mksearch_service_internal_Index::isIndexingInProgress()) {
            parent::resetFrontendEnvironment();
        }
    }
}
