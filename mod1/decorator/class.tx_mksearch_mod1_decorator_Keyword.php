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
 * Diese Klasse ist für die Darstellung von Indexer tabellen im Backend verantwortlich.
 */
class tx_mksearch_mod1_decorator_Keyword
{
    /**
     * @param Sys25\RnBase\Backend\Module\IModule $mod
     */
    public function __construct(protected $mod)
    {
    }

    /**
     * @param string $value
     * @param string $colName
     * @param array  $record
     * @param array  $item
     */
    public function format($value, $colName, $record, $item)
    {
        switch ($colName) {
            case 'link':
                $ret = $value;
                break;
            case 'actions':
                $formtool = $this->mod->getFormTool();
                $ret = '';
                // bearbeiten link
                $ret .= $formtool->createEditLink($item->getTableName(), $item->getUid(), '');
                // hide undhide link
                $ret .= $formtool->createHideLink($item->getTableName(), $item->getUid(), $item->getProperty('hidden'));
                // remove link
                $ret .= $formtool->createDeleteLink($item->getTableName(), $item->getUid(), '', ['confirm' => $GLOBALS['LANG']->sL('LLL:EXT:mksearch/Resources/Private/Language/BackendModule/locallang.xlf:confirmation_deletion')]);
                break;
            default:
                $ret = $value;
        }

        return $ret;
    }
}
