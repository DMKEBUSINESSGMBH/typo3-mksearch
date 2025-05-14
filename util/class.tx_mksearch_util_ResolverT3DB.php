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
 * Default resolver to load database records from standard TYPO3 database tables.
 */
class tx_mksearch_util_ResolverT3DB
{
    /**
     * Der Resolver lädt den zu indizierenden Datensatz auf der Datenbank. D.
     */
    public function getRecords(array $queueData)
    {
        $tableName = $queueData['tablename'];
        $recId = intval($queueData['recid']);
        if (!$tableName || 0 === $recId) {
            Sys25\RnBase\Utility\Logger::warn('[ResolverT3DB] Queue item ignored due to missing data.', 'mksearch', $queueData);
            throw new Exception('Queue item '.$queueData['uid'].' ignored due to missing data.');
        }

        $options['where'] = 'uid='.$recId;
        $options['enablefieldsoff'] = 1;
        $rows = Sys25\RnBase\Database\Connection::getInstance()->doSelect('*', $tableName, $options);
        if (0 === count($rows)) {
            // Datensatz vermutlich komplett gelöscht. Wir bereiten einen Minimal-Record für die Löschung vor
            $rows[] = ['uid' => $recId, 'deleted' => 1];
        }

        return $rows;
    }
}
