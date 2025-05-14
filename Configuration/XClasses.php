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

if (!defined('TYPO3')) {
    exit('Access denied.');
}

// folgendes Problem: in diesen ViewHelpern wird beim rendern im BE das TSFE zurückgesetzt
// und mit einer stdClass reinitialisiert. Das ist natürlich schlecht und führt u.U. zu
// Konflikten wenn wir den Seiteninhalt bei der Inizierung im BE rendern. Wenn z.B. ein gridelements
// Rasterelement indiziert wird, in dessen fluid Template ein cObj Viewhelper verwendet wird und
// das cObj ein LOAD_REGISTER enthält, dann kommt es zu einer PHP Warnung, die wir nicht wollen.
// Also verhindern wir das zurücksetzen des TSFE in diesen ViewHelpern während der Indizierung im BE.
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][TYPO3\CMS\Fluid\ViewHelpers\Format\HtmlViewHelper::class] =
    ['className' => DMK\Mksearch\ViewHelpers\Format\HtmlViewHelper::class];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][TYPO3\CMS\Fluid\ViewHelpers\CObjectViewHelper::class] =
    ['className' => DMK\Mksearch\ViewHelpers\CObjectViewHelper::class];
