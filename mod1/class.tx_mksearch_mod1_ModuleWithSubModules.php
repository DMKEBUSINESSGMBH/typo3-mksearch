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
 * Hat nchts mehr speziell mit Solr zu tun,
 * wurde von der Namensgebung allerdings so beibehalten.
 *
 * @author René Nitzsche <dev@dmk-ebusiness.de>
 */
abstract class tx_mksearch_mod1_ModuleWithSubModules extends Sys25\RnBase\Backend\Module\ExtendedModFunc
{
    public function getPid()
    {
        return $this->getModule()->getPid();
    }

    protected function renderOutput()
    {
        $ret = tx_mksearch_mod1_util_Misc::checkPid($this->getModule());
        if (null !== $ret && '' !== $ret && '0' !== $ret) {
            return $ret;
        }

        return parent::renderOutput();
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
