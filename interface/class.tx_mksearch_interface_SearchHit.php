<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2013 Michael Wagner <dev@dmk-ebusiness.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Interface for search engine services for the "mksearch" extension.
 *
 * @author Michael Wagner <dev@dmk-ebusiness.de>
 * @package TYPO3
 * @subpackage tx_mksearch
 */
interface tx_mksearch_interface_SearchHit
{

    /**
     * Returns the uid
     * @return int
     */
//    public function getUid(); // allready declared in tx_rnbase_IModel

    /**
     * Returns the data record as array
     * @return array
     */
//   public function getRecord();// allready declared in tx_rnbase_IModel
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/interface/class.tx_mksearch_interface_SearchHit.php']) {
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/interface/class.tx_mksearch_interface_SearchHit.php']);
}
