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
 * Detailseite eines beliebigen Datensatzes aus Momentan Lucene oder Solr.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 */
class tx_mksearch_action_CacheHandler extends tx_rnbase_action_CacheHandlerDefault
{
    /**
     * Generate a key used to store data to cache.
     */
    protected function getCacheKey(): string
    {
        $key = parent::getCacheKey();
        // Parameter cHash anhängen
        $key .= '_'.md5(serialize($this->getAllowedParameters()));

        return $key;
    }

    /**
     * Liefert alle erlaubten parameter,
     * welche zum erzeugen des CacheKeys verwendet werden.
     */
    private function getAllowedParameters(): array
    {
        $parameters = $this->getConfigurations()->getParameters();
        $params = [];
        $allowed = Sys25\RnBase\Utility\Strings::trimExplode(
            ',',
            $this->getConfigValue('params.allowed', '') ?? '',
            1
        );
        foreach ($allowed as $p) {
            $params[$p] = $parameters->get($p);
        }

        return $params;
    }
}
