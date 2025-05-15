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

use Psr\Http\Message\ServerRequestInterface;

/**
 * Mksearch backend module.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_ConfigIndizes extends tx_mksearch_mod1_ModuleWithSubModules
{
    /**
     * Return function id (used in page typoscript etc.).
     */
    protected function getFuncId(): string
    {
        return 'configindizes';
    }

    public function main(?ServerRequestInterface $request = null)
    {
        return tx_mksearch_mod1_util_Misc::getSubModuleContent(
            parent::main($request),
            $this
        );
    }

    /**
     * Liefert die Einträge für das Tab-Menü.
     */
    protected function getSubMenuItems(): array
    {
        return [
            TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_handler_Index'),
            TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_handler_Composite'),
            TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_handler_IndexerConfig'),
        ];
    }
}
