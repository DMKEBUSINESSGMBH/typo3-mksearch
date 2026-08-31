<?php

declare(strict_types=1);

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
 * Backend Modul Index.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_handler_Index extends tx_mksearch_mod1_handler_Base implements Sys25\RnBase\Backend\Module\IModHandler
{
    /**
     * Method to get a company searcher.
     *
     * @param array $options
     *
     * @return tx_mksearch_mod1_searcher_abstractBase
     */
    protected function getSearcher(Sys25\RnBase\Backend\Module\IModule $mod, &$options): object
    {
        $options['pid'] ??= $mod->getPid();

        return TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_searcher_Index', $mod, $options);
    }

    /**
     * Returns a unique ID for this handler. This is used to created the subpart in template.
     */
    public function getSubID(): string
    {
        return 'Index';
    }
}
