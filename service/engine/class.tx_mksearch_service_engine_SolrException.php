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
 * Solr exception.
 */
class tx_mksearch_service_engine_SolrException extends Sys25\RnBase\Exception\AdditionalException
{
    /**
     * Erstellt eine neue Exeption.
     *
     * @param string $message
     * @param int    $code
     */
    public function __construct($message, $code = 0, private $lastUrl = false, private $parent = false)
    {
        parent::__construct($message, $code);
    }

    public function getLastUrl()
    {
        return $this->lastUrl;
    }

    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Liefert zusätzliche Daten.
     *
     * @return mixed string or plain data
     */
    public function getAdditional($asString = true)
    {
        return $this->getLastUrl();
    }
}
