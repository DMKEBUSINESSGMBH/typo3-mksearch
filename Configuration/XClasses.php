<?php

if (!defined('TYPO3_MODE')) {
    exit('Access denied.');
}

// folgendes Problem: in diesen ViewHelpern wird beim rendern im BE das TSFE zurückgesetzt
// und mit einer stdClass reinitialisiert. Das ist natürlich schlecht und führt u.U. zu
// Konflikten wenn wir den Seiteninhalt bei der Inizierung im BE rendern. Wenn z.B. ein gridelements
// Rasterelement indiziert wird, in dessen fluid Template ein cObj Viewhelper verwendet wird und
// das cObj ein LOAD_REGISTER enthält, dann kommt es zu einer PHP Warnung, die wir nicht wollen.
// Also verhindern wir das zurücksetzen des TSFE in diesen ViewHelpern während der Indizierungim BE.
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Fluid\\ViewHelpers\\CObjectViewHelper'] =
    ['className' => 'DMK\\Mksearch\\ViewHelpers\\CObjectViewHelper'];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Fluid\\ViewHelpers\\Format\\HtmlViewHelper'] =
    ['className' => 'DMK\\Mksearch\\ViewHelpers\\Format\\HtmlViewHelper'];
