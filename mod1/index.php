<?php
/**
 *  Copyright notice.
 *
 *  (c) 2011 René Nitzsche <dev@dmk-ebusiness.de>
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
 */

// ////////////////////////////////////////////////////////// //
// deprecated, will be removed, when TYPO3 4.x support drops! //
// ////////////////////////////////////////////////////////// //

/**
 * Backend Modul für mksearch.
 *
 * @author René Nitzsche
 */
class tx_mksearch_module1 extends tx_mksearch_mod1_Module
{
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/index.php']) {
    include_once $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mksearch/mod1/index.php'];
}

/* @var $SOBE tx_mksearch_module1 */
$SOBE = tx_rnbase::makeInstance('tx_mksearch_module1');
$SOBE->__invoke();
