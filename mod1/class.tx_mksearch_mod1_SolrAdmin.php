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
 * Hat nchts mehr speziell mit Solr zu tun,
 * wurde von der Namensgebung allerdings so beibehalten.
 *
 * @author René Nitzsche <dev@dmk-ebusiness.de>
 */
class tx_mksearch_mod1_SolrAdmin extends Sys25\RnBase\Backend\Module\ExtendedModFunc
{
    /**
     * Return function id (used in page typoscript etc.).
     *
     * @return string
     */
    protected function getFuncId()
    {
        return 'admin';
    }

    public function getPid()
    {
        return $this->getModule()->getPid();
    }

    public function main(?ServerRequestInterface $request = null)
    {
        return tx_mksearch_mod1_util_Misc::getSubModuleContent(
            parent::main($request),
            $this,
            'web_MksearchM1_solradmin'
        );
    }

    /**
     * Liefert die Einträge für das Tab-Menü.
     *
     * @return array
     */
    protected function getSubMenuItems()
    {
        return [
            TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_mksearch_mod1_handler_admin_Solr'),
        ];
    }

    protected function makeSubSelectors(&$selStr)
    {
        return false;
    }

    public function getModuleIdentifier()
    {
        return 'mksearch';
    }
}
