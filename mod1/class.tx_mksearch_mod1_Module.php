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
 * Mksearch backend module.
 *
 * @author René Nitzsche
 * @author Michael Wagner
 */
class tx_mksearch_mod1_Module extends Sys25\RnBase\Backend\Module\BaseModule
{
    /**
     * Initializes the backend module by setting internal variables, initializing the menu.
     */
    public function init(): void
    {
        if (!isset($this->MCONF['name'])) {
            $this->MCONF = array_merge((array) $GLOBALS['MCONF'], [
                'name' => 'web_MksearchM1',
                'access' => 'user',
            ]);
        }

        $this->getLanguageService()->registerLangFile('EXT:mksearch/Resources/Private/Language/BackendModule/locallang.xlf');
        if (Sys25\RnBase\Utility\TYPO3::isTYPO130OrHigher()) {
            TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Backend\Module\ModuleProvider::class)->accessGranted(
                $this->MCONF['name']
            );
        } else {
            $this->getBackendUser()->modAccess($this->MCONF);
        }

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
        $modUrl = Sys25\RnBase\Backend\Utility\BackendUtility::getModuleUrl(
            'web_MksearchM1',
            [
                'id' => $this->getPid(),
            ]
        );

        return '<form action="'.$modUrl.'" method="POST" name="editform" id="editform">';
    }

    protected function moduleContent()
    {
        $ret = tx_mksearch_mod1_util_Misc::checkPid($this);
        if (null !== $ret && '' !== $ret && '0' !== $ret) {
            return $ret;
        }

        return parent::moduleContent();
    }

    protected function getModuleTemplate()
    {
        return 'EXT:mksearch/mod1/template.html';
    }

    public function getTitle()
    {
        return 'EXT:mksearch/Resources/Private/Language/BackendModule/locallang.xlf';
    }

    public function getRouteIdentifier()
    {
        return 'web_MksearchM1';
    }
}
