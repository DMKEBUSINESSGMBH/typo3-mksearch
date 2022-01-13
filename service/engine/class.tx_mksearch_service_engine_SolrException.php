<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 René Nitzche <dev@dmk-ebusiness.de>
 *  All rights reserved
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 ***************************************************************/

/**
 * Solr exception.
 */
class tx_mksearch_service_engine_SolrException extends \Sys25\RnBase\Exception\AdditionalException
{
    private $lastUrl = '';
    private $parent = false;

    /**
     * Erstellt eine neue Exeption.
     *
     * @param string $message
     * @param int    $code
     * @param mixed  $additional
     */
    public function __construct($message, $code = 0, $lastUrl = false, $parent = false)
    {
        parent::__construct($message, $code);
        $this->lastUrl = $lastUrl;
        $this->parent = $parent;
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

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/service/engine/class.tx_mksearch_service_engine_SolrException.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/service/engine/class.tx_mksearch_service_engine_SolrException.php'];
}
