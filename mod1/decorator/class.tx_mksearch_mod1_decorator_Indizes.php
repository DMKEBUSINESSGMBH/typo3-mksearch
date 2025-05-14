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
 * Diese Klasse ist für die Darstellung von Indexer tabellen im Backend verantwortlich
 * Wird für die Indexing Wueue benötigt.
 */
class tx_mksearch_mod1_decorator_Indizes
{
    /**
     * @param Sys25\RnBase\Backend\Module\IModule $mod
     */
    public function __construct(protected $mod)
    {
    }

    /**
     * @param string $colName
     * @param array  $item
     */
    public function format(string $value, $colName, array $record, $item)
    {
        switch ($colName) {
            case 'name':
                $ret = '<label for="resetTables'.$record['name'].'">'.$value.'</label>';
                break;
            case 'reset':
                $ret = '<input type="checkbox" name="resetTables[]" value="'.$record['name'].'" id="resetTables'.$record['name'].'" />';
                break;
            case 'resetG':
                $ret = '<input type="checkbox" name="resetTablesG[]" value="'.$record['name'].'" id="resetTablesG'.$record['name'].'" />';
                break;
            case 'clear':
                $ret = '<input type="checkbox" name="clearTables[]" value="'.$record['name'].'" id="clearTables'.$record['name'].'" />';
                break;
            case 'queuecount':
                $oIntIndexSrv = tx_mksearch_util_ServiceRegistry::getIntIndexService();
                $ret = $oIntIndexSrv->countItemsInQueue($record['name']);
                break;
            default:
                $ret = $value;
                break;
        }

        return $ret;
    }
}
