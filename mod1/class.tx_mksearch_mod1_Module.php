<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2016 DMK E-BUSINESS GmbH <dev@dmk-ebusiness.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Mksearch backend module.
 *
 * @author René Nitzsche
 * @author Michael Wagner
 */
class tx_mksearch_mod1_Module extends \Sys25\RnBase\Backend\Module\BaseModule
{
    /**
     * Initializes the backend module by setting internal variables, initializing the menu.
     */
    public function init()
    {
        $this->MCONF = [
            'name' => 'web_MksearchM1',
        ];

        $this->getLanguageService()->includeLLFile('EXT:mksearch/Resources/Private/Language/BackendModule/locallang.xlf');
        $this->getBackendUser()->modAccess($this->MCONF, 1);
        parent::init();
    }

    /**
     * Method to get the extension key.
     *
     * @return string Extension key
     */
    public function getExtensionKey()
    {
        return 'mksearch';
    }

    protected function getFormTag()
    {
        $modUrl = \Sys25\RnBase\Backend\Utility\BackendUtility::getModuleUrl(
            'web_MksearchM1',
            [
                'id' => $this->getPid(),
            ],
            ''
        );

        return '<form action="'.$modUrl.'" method="POST" name="editform" id="editform">';
    }

    public function moduleContent()
    {
        $ret = tx_mksearch_mod1_util_Misc::checkPid($this);
        if ($ret) {
            return $ret;
        }

        return parent::moduleContent();
    }
}
