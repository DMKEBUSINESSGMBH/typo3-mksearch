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
 * Hooks for search engine Zend_Lucene.
 */
class tx_mksearch_hooks_EngineZendLucene
{
    /**
     * Hook for converting fields before actual indexing.
     *
     * This method has to do some additional work the Zend Analyzer can't do.
     */
    public function convertFields($p): void
    {
        // Do some converting...
        // @see tx_mksearch_service_engine_ZendLucene::indexNew() - hooks
    }

    /**
     * Manipulate one single search term.
     *
     * This method can be used to normalize search terms
     * to match conditions of indexed data, e. g. adapt charse encoding.
     */
    public function manipulateSingleTerm(array $p): void
    {
        if (!isset($p['term'])) {
            throw new Exception('tx_mksearch_hooks_EngineZendLucene::manipulateSingleTerm(): No term given!');
        }

        // else
        $p['term'] = mb_strtolower($p['term'], 'utf-8');
    }
}
