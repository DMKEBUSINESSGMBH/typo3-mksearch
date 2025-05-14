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
 * The datamapper will find a lucene field type for a given fieldname.
 *
 * @author  René Nitzsche <rene.nitzsche@dmk-ebusiness.de>
 */
class tx_mksearch_service_engine_lucene_DataTypeMapper
{
    private static array $defaultKeywordFields = ['uid', 'extKey', 'contentType', 'tstamp', 'pid'];

    public function __construct(private $cfg = [])
    {
    }

    /**
     * Find a good datatype for this fieldname.
     *
     * @param string $fieldName
     *
     * @return string one of 'text', 'keyword', 'unindexed', 'unstored', 'binary'
     */
    public function getDataType($fieldName)
    {
        // Wurde was spezielles konfiguriert?
        $ret = $this->findFromCfg($fieldName);
        if (!$ret) {
            // Als default mal alles indexieren...
            $ret = 'text';
            if (in_array($fieldName, self::$defaultKeywordFields)) {
                $ret = 'keyword';
            } elseif ($this->endsWith($fieldName, '_i')) {
                $ret = 'keyword';
            }
        }

        return $ret;
    }

    /**
     * Try to find a specific type set by index config in lucene.schema.
     *
     * @return string or null
     */
    protected function findFromCfg(string $fieldName)
    {
        return $this->cfg['fields.'][$fieldName.'.']['type'] ?? null;
    }

    /**
     * test last part of a string.
     *
     * @param string $haystack
     */
    private function endsWith($haystack, string $needle): bool
    {
        return '' === $needle || str_ends_with($haystack, $needle);
    }
}
